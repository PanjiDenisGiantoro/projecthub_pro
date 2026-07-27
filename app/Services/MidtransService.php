<?php

namespace App\Services;

use App\Models\SubscriptionOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransService
{
    protected function baseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    protected function serverKey(): string
    {
        return (string) config('services.midtrans.server_key');
    }

    /**
     * Buat transaksi Snap dan kembalikan ['token' => ..., 'redirect_url' => ...].
     */
    public function createTransaction(SubscriptionOrder $order): array
    {
        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->baseUrl() . '/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id'     => $order->order_number,
                    'gross_amount' => $order->amount,
                ],
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email'      => $order->user->email,
                ],
                'item_details' => [[
                    'id'       => (string) $order->package_id,
                    'price'    => $order->amount,
                    'quantity' => 1,
                    'name'     => $order->package_name,
                ]],
                'callbacks' => [
                    'finish' => route('billing.finish'),
                ],
            ]);

        if ($response->failed()) {
            Log::error('Midtrans createTransaction gagal', [
                'order_number' => $order->order_number,
                'status'       => $response->status(),
                'body'         => $response->body(),
            ]);

            throw new RuntimeException('Gagal membuat transaksi Midtrans: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Verifikasi signature notification webhook Midtrans.
     */
    public function isValidSignature(array $payload): bool
    {
        $orderId     = $payload['order_id'] ?? '';
        $statusCode  = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signature   = $payload['signature_key'] ?? '';

        if ($orderId === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey());

        return hash_equals($expected, $signature);
    }
}
