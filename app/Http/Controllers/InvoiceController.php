<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Invoice::with(['project', 'client'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id));

        if ($user->hasRole('customer')) {
            $query->where('client_id', $user->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:users,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = Invoice::create([
            ...$request->only('project_id', 'client_id', 'issue_date', 'due_date', 'notes'),
            'invoice_number' => Invoice::generateNumber(),
            'status' => 'draft',
            'tax' => $request->get('tax', 0),
            'subtotal' => 0,
            'total' => 0,
        ]);

        foreach ($request->items as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $invoice->items()->create([...$item, 'total' => $total]);
        }

        $invoice->recalculate();

        return response()->json($invoice->load(['project', 'client', 'items']), 201);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['project', 'client', 'items']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Cannot edit a paid invoice.'], 422);
        }

        $invoice->update($request->only('due_date', 'notes', 'tax'));

        if ($request->items) {
            $invoice->items()->delete();
            foreach ($request->items as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $invoice->items()->create([...$item, 'total' => $total]);
            }
        }

        $invoice->recalculate();

        return response()->json($invoice->fresh()->load(['project', 'client', 'items']));
    }

    public function send(Request $request, Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);

        $this->notifier->send(
            $invoice->client_id,
            'invoice_sent',
            'New Invoice',
            "Invoice {$invoice->invoice_number} has been issued. Due: {$invoice->due_date->format('d M Y')}",
            ['invoice_id' => $invoice->id]
        );

        return response()->json($invoice->fresh());
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json($invoice->fresh());
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['project', 'client', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
