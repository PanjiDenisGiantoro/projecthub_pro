<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientPortalToken;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\NotificationService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;

class ClientPortalWebController extends Controller
{
    public function __construct(private NotificationService $notifier, private TeamNotifier $teamNotifier) {}

    // Manage tokens (admin/manager)
    public function index(Project $project)
    {
        $tokens = $project->portalTokens()->with('clientUser', 'creator')->orderByDesc('id')->get();
        return view('portal.manage', compact('project', 'tokens'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'label'      => 'nullable|string|max:100',
            'can_comment'=> 'boolean',
            'can_approve'=> 'boolean',
            'show_budget'=> 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $data['project_id'] = $project->id;
        $data['token']      = ClientPortalToken::generateToken();
        $data['created_by'] = auth()->id();

        $token = ClientPortalToken::create($data);

        return back()->with('success', 'Link portal dibuat.')->with('new_token', $token->token);
    }

    public function destroy(Project $project, ClientPortalToken $portalToken)
    {
        $portalToken->delete();
        return back()->with('success', 'Token dicabut.');
    }

    // Public portal (no auth required — token in URL)
    public function view(string $token)
    {
        $pt = ClientPortalToken::where('token', $token)->with('project')->firstOrFail();

        if ($pt->isExpired()) {
            return view('portal.expired');
        }

        $pt->update(['last_accessed_at' => now()]);

        $project = $pt->project->load([
            'milestones' => fn($q) => $q->orderBy('due_date'),
            'tasks' => fn($q) => $q->with('assignee')->limit(50),
            'manager',
        ]);

        return view('portal.view', compact('pt', 'project'));
    }

    public function approveMilestone(string $token, Milestone $milestone)
    {
        $pt = ClientPortalToken::where('token', $token)->with('project')->firstOrFail();

        if ($pt->isExpired() || ! $pt->can_approve) {
            abort(403);
        }

        // Milestone must belong to the project this token was issued for — a client
        // must never be able to approve another project's milestone by guessing an id.
        if ($milestone->project_id !== $pt->project_id) {
            abort(404);
        }

        if (! $milestone->isClientApproved()) {
            $milestone->update([
                'client_approved_at' => now(),
                'client_approved_via_token_id' => $pt->id,
            ]);

            $notify = $pt->project->manager_id;
            if ($notify) {
                $this->notifier->send($notify, 'milestone_client_approved', 'Milestone Disetujui Klien',
                    "Milestone \"{$milestone->title}\" disetujui oleh klien lewat Client Portal.", ['milestone_id' => $milestone->id]);
            }
            $this->teamNotifier->notify($pt->project, '✅ Milestone Disetujui Klien', "\"{$milestone->title}\" disetujui oleh klien.");
        }

        return back()->with('success', 'Milestone disetujui.');
    }

    public function comment(Request $request, string $token)
    {
        $pt = ClientPortalToken::where('token', $token)->firstOrFail();

        if ($pt->isExpired() || !$pt->can_comment) {
            abort(403);
        }

        // Store as a ticket comment or customer request comment — simplified here
        $request->validate(['message' => 'required|string|max:2000']);

        // For MVP: create a customer request tagged as portal feedback
        $pt->project->customerRequests()->create([
            'title'       => '[Portal Feedback] ' . str($request->message)->limit(80),
            'description' => $request->message,
            'status'      => 'waiting_approval',
            'type'        => 'general_inquiry',
            'priority'    => 'medium',
            'customer_id' => $pt->client_user_id ?? $pt->project->client_id,
        ]);

        return back()->with('success', 'Komentar terkirim.');
    }
}
