<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    protected $description = 'Mark overdue invoices and send reminder notifications';

    public function handle(NotificationService $notifier): void
    {
        $overdue = Invoice::where('status', 'sent')
            ->where('due_date', '<', today())
            ->get();

        foreach ($overdue as $invoice) {
            $invoice->update(['status' => 'overdue']);

            $notifier->send(
                $invoice->client_id,
                'invoice_overdue',
                'Invoice Overdue',
                "Invoice {$invoice->invoice_number} is overdue. Please make payment.",
                ['invoice_id' => $invoice->id]
            );
        }

        $this->info("Marked {$overdue->count()} invoice(s) as overdue.");
    }
}
