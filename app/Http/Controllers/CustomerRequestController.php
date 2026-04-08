<?php

namespace App\Http\Controllers;

use App\Models\CustomerRequest;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = CustomerRequest::with(['project', 'customer', 'approver'])
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'in:feature_request,bug_report,change_request,general_inquiry',
            'priority' => 'in:low,medium,high,urgent',
        ]);

        $customerRequest = CustomerRequest::create([
            ...$request->only('project_id', 'title', 'description', 'type', 'priority', 'attachment_path'),
            'customer_id' => $request->user()->id,
            'status' => 'submitted',
        ]);

        // Notify marketing team
        $this->notifier->notifyByRole(
            'marketing',
            'new_customer_request',
            'New Customer Request',
            "Customer submitted: {$customerRequest->title}",
            ['request_id' => $customerRequest->id]
        );

        return response()->json($customerRequest->load(['project', 'customer']), 201);
    }

    public function show(CustomerRequest $customerRequest)
    {
        return response()->json($customerRequest->load(['project', 'customer', 'reviewer', 'approver']));
    }

    public function review(Request $request, CustomerRequest $customerRequest)
    {
        $request->validate(['marketing_notes' => 'nullable|string']);

        $customerRequest->update([
            'status' => 'under_review',
            'marketing_notes' => $request->marketing_notes,
            'reviewed_by' => $request->user()->id,
        ]);

        // Notify managers
        $this->notifier->notifyByRole(
            'manager',
            'request_needs_approval',
            'Request Needs Approval',
            "Customer request ready for your approval: {$customerRequest->title}",
            ['request_id' => $customerRequest->id]
        );

        return response()->json($customerRequest->fresh()->load(['reviewer']));
    }

    public function approve(Request $request, CustomerRequest $customerRequest)
    {
        if ($customerRequest->status !== 'under_review' && $customerRequest->status !== 'submitted') {
            return response()->json(['message' => 'Request cannot be approved in its current state.'], 422);
        }

        $customerRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->notifier->send(
            $customerRequest->customer_id,
            'request_approved',
            'Your Request Has Been Approved',
            "Your request \"{$customerRequest->title}\" has been approved and will be worked on.",
            ['request_id' => $customerRequest->id]
        );

        return response()->json($customerRequest->fresh()->load(['approver']));
    }

    public function reject(Request $request, CustomerRequest $customerRequest)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $customerRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => $request->user()->id,
        ]);

        $this->notifier->send(
            $customerRequest->customer_id,
            'request_rejected',
            'Your Request Has Been Rejected',
            "Your request \"{$customerRequest->title}\" was rejected. Reason: {$request->rejection_reason}",
            ['request_id' => $customerRequest->id]
        );

        return response()->json($customerRequest->fresh());
    }
}
