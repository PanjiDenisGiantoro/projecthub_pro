<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Invoice::with(['project', 'client'])
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($user->hasRole('customer')) {
            $query->where('client_id', $user->id);
        }

        $invoices = $query->latest()->paginate(15);
        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $cid      = $this->tenantId();
        $projects = Project::where('status', 'active')->with('client')->get();
        $clients  = User::role('customer')->where('is_active', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->get();
        return view('invoices.create', compact('projects', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id'              => 'required|exists:projects,id',
            'client_id'               => 'required|exists:users,id',
            'issue_date'              => 'required|date',
            'due_date'                => 'required|date|after_or_equal:issue_date',
            'items'                   => 'required|array|min:1',
            'items.*.description'     => 'required|string',
            'items.*.quantity'        => 'required|numeric|min:0',
            'items.*.unit_price'      => 'required|numeric|min:0',
        ]);

        // lockForUpdate() di Invoice::generateNumber() cuma efektif di dalam transaction.
        // Attempt ke-3 di DB::transaction() bikin Laravel otomatis retry kalau kena
        // deadlock/serialization-failure MySQL. Retry manual di luar jaga-jaga kalau
        // race tetap kejadian pas invoice pertama bulan itu (belum ada baris buat di-lock,
        // jadi bisa kena duplicate-key bukan deadlock).
        $maxAttempts = 3;
        for ($attempt = 1; ; $attempt++) {
            try {
                $invoice = DB::transaction(function () use ($request) {
                    $invoice = Invoice::create([
                        ...$request->only('project_id', 'client_id', 'issue_date', 'due_date', 'notes'),
                        'invoice_number' => Invoice::generateNumber(),
                        'status'         => 'draft',
                        'tax'            => $request->get('tax', 0),
                        'subtotal'       => 0,
                        'total'          => 0,
                    ]);

                    foreach ($request->items as $item) {
                        $invoice->items()->create([...$item, 'total' => $item['quantity'] * $item['unit_price']]);
                    }

                    $invoice->recalculate();

                    return $invoice;
                }, 3);
                break;
            } catch (QueryException $e) {
                $isDuplicateNumber = $e->errorInfo[1] === 1062 && str_contains($e->getMessage(), 'invoice_number');
                if (!$isDuplicateNumber || $attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice dibuat.');
    }

    public function show(Invoice $invoice)
    {
        abort_if(auth()->user()->hasRole('customer') && $invoice->client_id !== auth()->id(), 403);

        $invoice->load(['project', 'client', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    public function send(Request $request, Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);
        $this->notifier->send($invoice->client_id, 'invoice_sent', 'Invoice Baru', "Invoice {$invoice->invoice_number} telah diterbitkan. Jatuh tempo: {$invoice->due_date->format('d M Y')}", ['invoice_id' => $invoice->id]);
        return back()->with('success', 'Invoice dikirim ke client.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', 'Invoice ditandai lunas.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        abort_if(auth()->user()->hasRole('customer') && $invoice->client_id !== auth()->id(), 403);

        $invoice->load(['project', 'client', 'items']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
