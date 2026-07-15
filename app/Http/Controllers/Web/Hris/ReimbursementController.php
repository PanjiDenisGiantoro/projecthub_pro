<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReimbursementController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $items = Reimbursement::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('manage reimbursement'), fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('expense_date')
            ->paginate(20);

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

        $this->notifier->notifyByPermission(
            'manage reimbursement',
            'reimbursement_submitted',
            'Pengajuan Reimburse Baru',
            "{$user->name} mengajukan reimburse \"{$reimburse->title}\" sebesar Rp" . number_format($reimburse->amount, 0, ',', '.') . ".",
            ['reimbursement_id' => $reimburse->id],
            companyId: $user->company_id,
            excludeUserId: $user->id
        );

        return redirect()->route('hris.reimburse.index')->with('success', 'Pengajuan reimburse berhasil dikirim.');
    }

    public function destroy(Reimbursement $reimburse)
    {
        abort_if($reimburse->user_id !== auth()->id() || $reimburse->status !== 'pending', 403);
        $reimburse->delete();
        return back()->with('success', 'Pengajuan dihapus.');
    }

    public function approve(Reimbursement $reimburse)
    {
        $this->authorize('manage reimbursement');
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
}
