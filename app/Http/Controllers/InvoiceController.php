<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user      = $request->user();
        $baseQuery = Invoice::query();

        if ($user->hasRole('client')) {
            $baseQuery->where('client_id', $user->id);
        }

        // Ringkasan KPI (sama dengan halaman web /invoices), dihitung sebelum filter status/search.
        $stats = [
            'total_amount'   => (float) (clone $baseQuery)->sum('total'),
            'paid_amount'    => (float) (clone $baseQuery)->where('status', 'paid')->sum('total'),
            'pending_amount' => (float) (clone $baseQuery)->whereIn('status', ['sent', 'draft'])->sum('total'),
            'overdue_amount' => (float) (clone $baseQuery)->where('status', 'overdue')->sum('total'),
            'total_count'    => (clone $baseQuery)->count(),
            'paid_count'     => (clone $baseQuery)->where('status', 'paid')->count(),
            'pending_count'  => (clone $baseQuery)->whereIn('status', ['sent', 'draft'])->count(),
            'overdue_count'  => (clone $baseQuery)->where('status', 'overdue')->count(),
        ];

        $invoices = (clone $baseQuery)->with(['project', 'client'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->search, function ($q) use ($request) {
                $term = $request->search;
                $q->where(function ($sub) use ($term) {
                    $sub->where('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('project', fn($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request, 15))
            ->withQueryString();

        return response()->json(array_merge($invoices->toArray(), [
            'stats'  => $stats,
            'can'    => [
                'create' => $user->can('create invoice'),
                'update' => $user->can('update invoice'),
            ],
        ]));
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
        $this->authorizeClient($invoice);

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
        $this->authorizeClient($invoice);

        $invoice->load(['project', 'client', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /** Client hanya boleh melihat invoice miliknya sendiri. */
    private function authorizeClient(Invoice $invoice): void
    {
        $user = auth()->user();
        abort_if($user->hasRole('client') && $invoice->client_id !== $user->id, 403);
    }
}
