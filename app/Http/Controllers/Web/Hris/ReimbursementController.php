<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Pph21Setting;
use App\Models\Reimbursement;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReimbursementController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $items = Reimbursement::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view reimbursement'), fn($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('expense_date')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('hris.reimburse.index', compact('items'));
    }

    public function create()
    {
        return view('hris.reimburse.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category'     => 'required|in:transport,makan,akomodasi,medis,pulsa,lainnya',
            'title'        => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount'       => 'required|numeric|min:1',
            'description'  => 'nullable|string|max:500',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $user = auth()->user();

        // Cegah double-submit (klik ganda / submit ulang).
        $duplicate = Reimbursement::where('user_id', $user->id)
            ->where('title', $request->title)
            ->where('amount', $request->amount)
            ->where('expense_date', $request->expense_date)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest()
            ->first();

        if ($duplicate) {
            return redirect()->route('hris.reimburse.index')->with('success', 'Pengajuan reimburse berhasil dikirim.');
        }

        $data = $request->only(['category', 'title', 'expense_date', 'amount', 'description']);
        $data['user_id']    = $user->id;
        $data['company_id'] = $user->company_id;

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $request->file('receipt')->store('reimburse-receipts', 'public');
        }

        $reimburse = Reimbursement::create($data);

        $needsApproval = Pph21Setting::forCompany($user->company_id)->reimbursement_needs_approval;

        if (!$needsApproval) {
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
                "{$user->name} mengajukan reimburse \"{$reimburse->title}\" sebesar Rp" . number_format($reimburse->amount, 0, ',', '.') . ".",
                ['reimbursement_id' => $reimburse->id],
                companyId: $user->company_id,
                excludeUserId: $user->id
            );
        }

        return redirect()->route('hris.reimburse.index')->with('success', 'Pengajuan reimburse berhasil dikirim.');
    }

    public function update(Request $request, Reimbursement $reimburse)
    {
        $this->authorize('update reimbursement');
        abort_if($reimburse->company_id !== auth()->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Hanya pengajuan berstatus Pending yang bisa diedit.');

        $request->validate([
            'category'     => 'required|in:transport,makan,akomodasi,medis,pulsa,lainnya',
            'title'        => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount'       => 'required|numeric|min:1',
            'description'  => 'nullable|string|max:500',
        ]);

        $reimburse->update($request->only(['category', 'title', 'expense_date', 'amount', 'description']));

        return back()->with('success', 'Pengajuan reimburse berhasil diperbarui.');
    }

    public function destroy(Reimbursement $reimburse)
    {
        $user           = auth()->user();
        $isOwnerPending = $reimburse->user_id === $user->id && $reimburse->status === 'pending';
        $canForceDelete = $user->can('delete reimbursement') && $reimburse->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        // Batalkan punya sendiri (masih pending) = soft cancel, bukan hard delete.
        if ($isOwnerPending && !$canForceDelete) {
            $reimburse->update(['status' => 'cancelled']);
            return back()->with('success', 'Pengajuan reimburse dibatalkan.');
        }

        $reimburse->delete();
        return back()->with('success', 'Data reimburse dihapus.');
    }

    public function approve(Reimbursement $reimburse)
    {
        $this->authorize('approve reimbursement');
        abort_if($reimburse->company_id !== auth()->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Status tidak valid.');
        $reimburse->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        $this->notifier->send(
            $reimburse->user_id,
            'reimbursement_approved',
            'Reimburse Disetujui',
            "Pengajuan reimburse \"{$reimburse->title}\" Anda disetujui oleh " . auth()->user()->name . ".",
            ['reimbursement_id' => $reimburse->id]
        );

        return back()->with('success', 'Reimburse disetujui.');
    }

    public function reject(Request $request, Reimbursement $reimburse)
    {
        $this->authorize('approve reimbursement');
        abort_if($reimburse->company_id !== auth()->user()->company_id, 403);
        abort_if($reimburse->status !== 'pending', 422, 'Status tidak valid.');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $reimburse->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $this->notifier->send(
            $reimburse->user_id,
            'reimbursement_rejected',
            'Reimburse Ditolak',
            "Pengajuan reimburse \"{$reimburse->title}\" Anda ditolak oleh " . auth()->user()->name . ". Alasan: {$request->rejection_reason}",
            ['reimbursement_id' => $reimburse->id]
        );

        return back()->with('success', 'Reimburse ditolak.');
    }
}
