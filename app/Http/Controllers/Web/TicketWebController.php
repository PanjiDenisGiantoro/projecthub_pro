<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\Project;
use App\Models\TicketAttachment;
use App\Models\TicketHistory;
use App\Models\TicketLink;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use App\Services\SlaService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketWebController extends Controller
{
    use HasPerPage;

    public function __construct(private SlaService $sla, private NotificationService $notifier, private TeamNotifier $teamNotifier) {}

    public function allTickets(Request $request)
    {
        $authUser  = auth()->user();
        $companyId = $authUser->company_id;

        $companyScope = function ($q) use ($authUser, $companyId) {
            if (! $authUser->is_super_admin && $companyId) {
                $q->whereHas('project', fn($p) => $p->where('company_id', $companyId));
            }
        };

        $query = BugTicket::with(['project', 'reporter', 'assignee'])
            ->tap($companyScope)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority));

        if ($authUser->hasRole('client')) {
            $query->where('reporter_id', $authUser->id);
        }

        $tickets   = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $slaReport = [
            'total'    => BugTicket::tap($companyScope)->count(),
            'breached' => BugTicket::tap($companyScope)->where('sla_breached', true)->count(),
            'open'     => BugTicket::tap($companyScope)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
        ];
        $project = null;
        return view('tickets.index', compact('project', 'tickets', 'slaReport'));
    }

    public function index(Request $request, Project $project)
    {
        $query = $project->tickets()->with(['reporter', 'assignee'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority));

        if (auth()->user()->hasRole('client')) {
            $query->where('reporter_id', auth()->id());
        }

        $tickets    = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $slaReport  = [
            'total'    => $project->tickets()->count(),
            'breached' => $project->tickets()->where('sla_breached', true)->count(),
            'open'     => $project->tickets()->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
        ];
        return view('tickets.index', compact('project', 'tickets', 'slaReport'));
    }

    public function create(Project $project)
    {
        return view('tickets.create', compact('project'));
    }

    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'type'           => 'in:bug,issue,enhancement,security,performance',
            'error_category' => 'nullable|in:frontend,backend,database,api,infrastructure,integration,configuration,other',
            'priority'       => 'in:critical,high,medium,low',
            'attachments'    => 'nullable|array|max:5',
            'attachments.*'  => 'file|max:10240',
        ]);

        // Cegah double-submit (klik ganda / submit ulang): kalau tiket persis sama
        // baru saja dibuat reporter yang sama di proyek ini, anggap itu duplikat.
        $duplicate = $project->tickets()
            ->where('reporter_id', auth()->id())
            ->where('title', $request->title)
            ->where('description', $request->description)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest()
            ->first();

        if ($duplicate) {
            return redirect()->route('tickets.show', $duplicate);
        }

        $ticket = $project->tickets()->create([
            ...$request->only('title', 'description', 'type', 'error_category', 'priority'),
            'reporter_id' => auth()->id(),
            'status'      => 'open',
        ]);

        $this->storeAttachments($ticket, $request);

        $this->sla->applyPolicy($ticket);
        $this->notifier->notifyManagers('new_ticket', 'Tiket Baru', "Tiket {$ticket->priority}: {$ticket->title}", ['ticket_id' => $ticket->id], companyId: $project->company_id);
        $this->teamNotifier->notify($project, '🐞 Tiket Baru', "[{$ticket->priority}] \"{$ticket->title}\" dilaporkan oleh " . auth()->user()->name . '.');

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForBugTicket($ticket, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Tiket berhasil dibuat.');
    }

    public function createMeeting(BugTicket $ticket, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForBugTicket($ticket, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }

    public function show(BugTicket $ticket)
    {
        $ticket->load([
            'project', 'reporter', 'assignee', 'slaPolicy', 'comments.user', 'histories.actor', 'tasks', 'attachments.uploader',
            'outgoingLinks.targetTicket', 'incomingLinks.sourceTicket',
        ]);
        $developers = User::role('member')->where('is_active', true)->where('company_id', $ticket->project->company_id)->get();
        $relatableTickets = $ticket->project->tickets()->where('id', '!=', $ticket->id)->orderByDesc('id')->get(['id', 'title']);
        return view('tickets.show', compact('ticket', 'developers', 'relatableTickets'));
    }

    public function updateDetails(Request $request, BugTicket $ticket)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'member']), 403);

        $request->validate([
            'error_category' => 'nullable|in:frontend,backend,database,api,infrastructure,integration,configuration,other',
            'solution'       => 'nullable|string|max:5000',
            'attachments'    => 'nullable|array|max:5',
            'attachments.*'  => 'file|max:10240',
        ]);

        foreach (['error_category', 'solution'] as $field) {
            if ($request->has($field) && $ticket->$field !== $request->$field) {
                TicketHistory::create([
                    'ticket_id'     => $ticket->id,
                    'actor_id'      => auth()->id(),
                    'field_changed' => $field,
                    'old_value'     => $ticket->$field,
                    'new_value'     => $request->$field,
                ]);
            }
        }

        $ticket->update($request->only('error_category', 'solution'));
        $this->storeAttachments($ticket, $request);

        return back()->with('success', 'Detail tiket diperbarui.');
    }

    public function linkTicket(Request $request, BugTicket $ticket)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'member']), 403);

        $request->validate([
            'target_ticket_id' => 'required|exists:bug_tickets,id',
            'link_type'        => 'required|in:blocks,blocked_by,duplicates,duplicated_by,relates_to,caused_by,causes',
        ]);

        if ((int) $request->target_ticket_id === $ticket->id) {
            return back()->withErrors(['Tidak bisa mereferensikan tiket ke dirinya sendiri.']);
        }

        $inverseMap = [
            'blocks'        => 'blocked_by',
            'blocked_by'    => 'blocks',
            'duplicates'    => 'duplicated_by',
            'duplicated_by' => 'duplicates',
            'caused_by'     => 'causes',
            'causes'        => 'caused_by',
            'relates_to'    => 'relates_to',
        ];

        TicketLink::firstOrCreate(
            ['source_ticket_id' => $ticket->id, 'target_ticket_id' => $request->target_ticket_id, 'link_type' => $request->link_type],
            ['created_by' => auth()->id()]
        );

        TicketLink::firstOrCreate(
            ['source_ticket_id' => $request->target_ticket_id, 'target_ticket_id' => $ticket->id, 'link_type' => $inverseMap[$request->link_type]],
            ['created_by' => auth()->id()]
        );

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => auth()->id(),
            'field_changed' => 'linked_ticket',
            'old_value'     => null,
            'new_value'     => $request->target_ticket_id,
            'description'   => "Direferensikan sebagai '{$request->link_type}' ke tiket #{$request->target_ticket_id}",
        ]);

        return back()->with('success', 'Tiket referensi ditambahkan.');
    }

    public function unlinkTicket(BugTicket $ticket, TicketLink $link)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'member']), 403);
        abort_unless($link->source_ticket_id === $ticket->id, 404);

        $inverseMap = [
            'blocks'        => 'blocked_by',
            'blocked_by'    => 'blocks',
            'duplicates'    => 'duplicated_by',
            'duplicated_by' => 'duplicates',
            'caused_by'     => 'causes',
            'causes'        => 'caused_by',
            'relates_to'    => 'relates_to',
        ];

        TicketLink::where('source_ticket_id', $link->target_ticket_id)
            ->where('target_ticket_id', $link->source_ticket_id)
            ->where('link_type', $inverseMap[$link->link_type])
            ->delete();

        $link->delete();

        return back()->with('success', 'Referensi tiket dihapus.');
    }

    public function deleteAttachment(BugTicket $ticket, TicketAttachment $attachment)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'member']), 403);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran dihapus.');
    }

    private function storeAttachments(BugTicket $ticket, Request $request): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store("ticket-attachments/{$ticket->id}", 'public');
            $ticket->attachments()->create([
                'uploaded_by' => auth()->id(),
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
            ]);
        }
    }

    public function update(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'status'      => 'nullable|in:open,assigned,in_progress,pending_review,resolved,closed,reopened',
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $ticket->project->company_id)],
        ]);

        $oldStatus   = $ticket->status;
        $oldAssignee = $ticket->assignee_id;
        $updates     = $request->only('status', 'assignee_id');

        if (($updates['status'] ?? null) === 'resolved') $updates['resolved_at'] = now();
        if (($updates['status'] ?? null) === 'closed')   $updates['closed_at']   = now();

        $ticket->update($updates);

        if ($request->filled('status') && $oldStatus !== $ticket->status) {
            TicketHistory::create(['ticket_id' => $ticket->id, 'actor_id' => auth()->id(), 'field_changed' => 'status', 'old_value' => $oldStatus, 'new_value' => $ticket->status]);
        }

        if ($request->filled('assignee_id') && $oldAssignee !== $ticket->assignee_id) {
            TicketHistory::create(['ticket_id' => $ticket->id, 'actor_id' => auth()->id(), 'field_changed' => 'assignee_id', 'old_value' => $oldAssignee, 'new_value' => $ticket->assignee_id]);
            $this->notifier->send($ticket->assignee_id, 'ticket_assigned', 'Tiket Ditugaskan', "Tiket \"{$ticket->title}\" ditugaskan ke Anda.", ['ticket_id' => $ticket->id]);
        }

        return back()->with('success', 'Tiket diperbarui.');
    }

    public function addComment(Request $request, BugTicket $ticket)
    {
        $request->validate(['body' => 'required|string']);
        $ticket->comments()->create(['user_id' => auth()->id(), 'body' => $request->body]);
        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function reopen(Request $request, BugTicket $ticket)
    {
        $request->validate(['reason' => 'required|string']);
        if ($ticket->status !== 'closed') return back()->withErrors(['Tiket belum closed.']);
        if ($ticket->closed_at && $ticket->closed_at->diffInDays(now()) > 7) return back()->withErrors(['Masa reopen sudah habis (7 hari).']);
        $ticket->update(['status' => 'reopened', 'closed_at' => null]);
        $ticket->comments()->create(['user_id' => auth()->id(), 'body' => 'Reopened: ' . $request->reason]);
        return back()->with('success', 'Tiket dibuka kembali.');
    }
}
