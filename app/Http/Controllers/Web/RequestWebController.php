<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerRequest;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RequestWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = CustomerRequest::with(['project', 'customer', 'approver'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id));

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        }

        $requests = $query->latest()->paginate(20);
        $projects = $user->hasRole('customer')
            ? Project::where('client_id', $user->id)->get()
            : Project::get(['id', 'name']);

        return view('requests.index', compact('requests', 'projects'));
    }

    public function create()
    {
        $projects = Project::where('client_id', auth()->id())->get();
        return view('requests.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'type'        => 'in:feature_request,bug_report,change_request,general_inquiry',
            'priority'    => 'in:low,medium,high,urgent',
        ]);

        $cr = CustomerRequest::create([
            ...$request->only('project_id', 'title', 'description', 'type', 'priority'),
            'customer_id' => auth()->id(),
            'status'      => 'submitted',
        ]);

        $this->notifier->notifyByRole('marketing', 'new_customer_request', 'Request Customer Baru', "Customer mengajukan: {$cr->title}", ['request_id' => $cr->id]);

        return redirect()->route('requests.show', $cr)->with('success', 'Request berhasil dikirim.');
    }

    public function show(CustomerRequest $request)
    {
        $request->load(['project', 'customer', 'reviewer', 'approver']);
        return view('requests.show', ['customerRequest' => $request]);
    }

    public function review(Request $request, CustomerRequest $customerRequest)
    {
        $customerRequest->update(['status' => 'under_review', 'marketing_notes' => $request->marketing_notes, 'reviewed_by' => auth()->id()]);
        $this->notifier->notifyByRole('manager', 'request_needs_approval', 'Request Perlu Approval', "Request siap di-approve: {$customerRequest->title}", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request diteruskan ke Manager.');
    }

    public function approve(Request $request, CustomerRequest $customerRequest)
    {
        $customerRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->notifier->send($customerRequest->customer_id, 'request_approved', 'Request Disetujui', "Request Anda \"{$customerRequest->title}\" disetujui.", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request disetujui.');
    }

    public function reject(Request $request, CustomerRequest $customerRequest)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        $customerRequest->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason, 'approved_by' => auth()->id()]);
        $this->notifier->send($customerRequest->customer_id, 'request_rejected', 'Request Ditolak', "Request Anda \"{$customerRequest->title}\" ditolak. Alasan: {$request->rejection_reason}", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request ditolak.');
    }
}
