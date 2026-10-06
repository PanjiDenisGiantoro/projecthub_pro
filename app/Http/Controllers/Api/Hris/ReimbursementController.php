<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Controller;
use App\Models\Pph21Setting;
use App\Models\Reimbursement;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Reimburse untuk aplikasi mobile. Aturan sama dengan Web\Hris\ReimbursementController. */
class ReimbursementController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Reimbursement::with(['user:id,name', 'approver:id,name'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view reimbursement'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('expense_date')
            ->limit(200)
            ->get();

        return response()->json($items->map(fn ($r) => $this->json($r))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category'     => 'required|in:transport,makan,akomodasi,medis,pulsa,lainnya',
            'title'        => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount'       => 'required|numeric|min:1',
            'description'  => 'nullable|string|max:500',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $user = $request->user();

        $duplicate = Reimbursement::where('user_id', $user->id)
            ->where('title', $request->title)
            ->where('amount', $request->amount)
            ->where('expense_date', $request->expense_date)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest()
            ->first();

        if ($duplicate) {
            return response()->json($this->json($duplicate->load(['user', 'approver'])), 201);
        }

        $data = $request->only(['category', 'title', 'expense_date', 'amount', 'description']);
        $data['user_id']    = $user->id;
        $data['company_id'] = $user->company_id;

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $request->file('receipt')->store('reimburse-receipts', 'public');
        }

        $reimburse = Reimbursement::create($data);

        if (!Pph21Setting::forCompany($user->company_id)->reimbursement_needs_approval) {
            $reimburse->update(['status' => 'approved', 'approved_at' => now()]);
            $this->notifier->send(
                $user->id,
                'reimbursement_approved',
                'Reimburse Disetujui',
                "Pengajuan reimburse \"{$reimburse->title}\" Anda disetujui otomatis.",
                ['reimbursement_id' => $reimburse->id]
            );
        } else {
            $this->notifier->notifyByPermission(
                'approve reimbursement',
                'reimbursement_submitted',
                'Pengajuan Reimburse Baru',
                "{$user->name} mengajukan reimburse \"{$reimburse->title}\" sebesar Rp" . number_format($reimburse->amount, 0, ',', '.') . '.',
                ['reimbursement_id' => $reimburse->id],
                companyId: $user->company_id,
                excludeUserId: $user->id
            );
        }

        return response()->json($this->json($reimburse->fresh(['user', 'approver'])), 201);
    }

    public function update(Request $request, Reimbursement $reimburse): JsonResponse
    {
        $this->authorize('update reimbursement');
        abort_if($reimburse->company_id !== $request->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Hanya pengajuan berstatus Pending yang bisa diedit.');

        $request->validate([
            'category'     => 'required|in:transport,makan,akomodasi,medis,pulsa,lainnya',
            'title'        => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount'       => 'required|numeric|min:1',
            'description'  => 'nullable|string|max:500',
        ]);

        $reimburse->update($request->only(['category', 'title', 'expense_date', 'amount', 'description']));

        return response()->json($this->json($reimburse->fresh(['user', 'approver'])));
    }

    public function destroy(Request $request, Reimbursement $reimburse): JsonResponse
    {
        $user           = $request->user();
        $isOwnerPending = $reimburse->user_id === $user->id && $reimburse->status === 'pending';
        $canForceDelete = $user->can('delete reimbursement') && $reimburse->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        if ($isOwnerPending && !$canForceDelete) {
            $reimburse->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Pengajuan reimburse dibatalkan.', 'reimbursement' => $this->json($reimburse->fresh(['user', 'approver']))]);
        }

        $reimburse->delete();
        return response()->json(['message' => 'Data reimburse dihapus.', 'reimbursement' => null]);
    }

    public function approve(Request $request, Reimbursement $reimburse): JsonResponse
    {
        $this->authorize('approve reimbursement');
        abort_if($reimburse->company_id !== $request->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Status tidak valid.');
        $reimburse->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        $this->notifier->send(
            $reimburse->user_id,
            'reimbursement_approved',
            'Reimburse Disetujui',
            "Pengajuan reimburse \"{$reimburse->title}\" Anda disetujui oleh {$request->user()->name}.",
            ['reimbursement_id' => $reimburse->id]
        );

        return response()->json($this->json($reimburse->fresh(['user', 'approver'])));
    }

    public function reject(Request $request, Reimbursement $reimburse): JsonResponse
    {
        $this->authorize('approve reimbursement');
        abort_if($reimburse->company_id !== $request->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Status tidak valid.');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $reimburse->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $this->notifier->send(
            $reimburse->user_id,
            'reimbursement_rejected',
            'Reimburse Ditolak',
            "Pengajuan reimburse \"{$reimburse->title}\" Anda ditolak oleh {$request->user()->name}. Alasan: {$request->rejection_reason}",
            ['reimbursement_id' => $reimburse->id]
        );

        return response()->json($this->json($reimburse->fresh(['user', 'approver'])));
    }

    private function json(Reimbursement $r): array
    {
        return [
            'id'               => $r->id,
            'user_id'          => $r->user_id,
            'user_name'        => $r->user?->name,
            'category'         => $r->category,
            'title'            => $r->title,
            'description'      => $r->description,
            'expense_date'     => $r->expense_date?->toDateString(),
            'amount'           => (float) $r->amount,
            'receipt_url'      => $r->receipt ? asset('storage/' . $r->receipt) : null,
            'status'           => $r->status,
            'approved_by'      => $r->approved_by,
            'approved_by_name' => $r->approver?->name,
            'approved_at'      => $r->approved_at?->toIso8601String(),
            'rejection_reason' => $r->rejection_reason,
            'in_payroll'       => $r->payroll_id !== null,
        ];
    }
}
