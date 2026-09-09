<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\CustomerRequest;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RequestWebController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = CustomerRequest::with(['project', 'customer', 'approver'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id));

        if ($user->hasRole('client')) {
            $query->where('customer_id', $user->id);
        }

        $requests = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $projects = $user->hasRole('client')
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
            'status'      => 'waiting_approval',
        ]);

        $this->notifier->notifyByRole('member', 'request_needs_approval', 'Request Needs Approval', "Customer submitted: {$cr->title}", ['request_id' => $cr->id], companyId: $cr->project->company_id);

        return redirect()->route('requests.show', $cr)->with('success', 'Request submitted successfully.');
    }

    public function show(CustomerRequest $customerRequest)
    {
        abort_if(auth()->user()->hasRole('client') && $customerRequest->customer_id !== auth()->id(), 403);

        $customerRequest->load(['project', 'customer', 'reviewer', 'approver']);
        return view('requests.show', compact('customerRequest'));
    }

    public function approve(Request $request, CustomerRequest $customerRequest)
    {
        if ($customerRequest->status !== 'waiting_approval') {
            return back()->with('error', 'Request is not waiting for approval.');
        }
        $customerRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->notifier->send($customerRequest->customer_id, 'request_approved', 'Request Approved', "Your request \"{$customerRequest->title}\" has been approved.", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request approved.');
    }

    public function reject(Request $request, CustomerRequest $customerRequest)
    {
        if ($customerRequest->status !== 'waiting_approval') {
            return back()->with('error', 'Request is not waiting for approval.');
        }
        $request->validate(['rejection_reason' => 'required|string']);
        $customerRequest->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason, 'approved_by' => auth()->id()]);
        $this->notifier->send($customerRequest->customer_id, 'request_rejected', 'Request Rejected', "Your request \"{$customerRequest->title}\" has been rejected. Reason: {$request->rejection_reason}", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request rejected.');
    }

    public function complete(CustomerRequest $customerRequest)
    {
        if ($customerRequest->status !== 'approved') {
            return back()->with('error', 'Request must be approved before being marked as done.');
        }
        $customerRequest->update(['status' => 'done', 'completed_by' => auth()->id(), 'completed_at' => now()]);
        $this->notifier->send($customerRequest->customer_id, 'request_done', 'Request Completed', "Your request \"{$customerRequest->title}\" has been completed.", ['request_id' => $customerRequest->id]);
        return back()->with('success', 'Request marked as done.');
    }
}
