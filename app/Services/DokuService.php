<?php

namespace App\Services;

use App\Models\PpnRate;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Integrasi DOKU Checkout (non-SNAP): user diarahkan ke halaman pembayaran DOKU,
 * status pembayaran dikirim balik lewat HTTP Notification ke /billing/notification.
 */
class DokuService
{
    protected const CHECKOUT_TARGET = '/checkout/v1/payment';

    protected function baseUrl(): string
    {
        return config('services.doku.is_production')
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    protected function clientId(): string
    {
        return (string) config('services.doku.client_id');
    }

    protected function secretKey(): string
    {
        return (string) config('services.doku.secret_key');
    }

    /**
     * Buat sesi DOKU Checkout dan kembalikan ['url' => ..., 'token_id' => ..., 'raw' => ...].
     */
    public function createPayment(SubscriptionOrder $order): array
    {
        $body = json_encode([
            'order' => [
                'amount'         => $order->amount,
                'invoice_number' => $order->order_number,
                'currency'       => 'IDR',
                'callback_url'   => route('billing.finish', ['order_id' => $order->order_number]),
                'line_items'     => $this->lineItems($order),
            ],
            'payment' => array_filter([
                'payment_due_date'     => (int) config('services.doku.payment_due_minutes', 60),
                // Kunci halaman DOKU ke metode yang dipilih customer (biaya layanan dihitung per metode).
                'payment_method_types' => $order->payment_method_code ? [$order->payment_method_code] : null,
            ]),
            'customer' => [
                'id'    => (string) $order->user_id,
                'name'  => $order->user->name,
                'email' => $order->user->email,
            ],
        ]);

        $requestId = (string) Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

        $response = Http::withHeaders([
                'Client-Id'         => $this->clientId(),
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $this->signature($requestId, $timestamp, self::CHECKOUT_TARGET, $body),
            ])
            ->acceptJson()
            ->withBody($body, 'application/json')
            ->post($this->baseUrl() . self::CHECKOUT_TARGET);

        $url = $response->json('response.payment.url');

        if ($response->failed() || ! $url) {
            Log::error('DOKU createPayment gagal', [
                'order_number' => $order->order_number,
                'status'       => $response->status(),
                'body'         => $response->body(),
            ]);

            throw new RuntimeException('Gagal membuat transaksi DOKU: ' . $response->body());
        }

        return [
            'url'      => $url,
            'token_id' => $response->json('response.payment.token_id'),
            'raw'      => $response->json(),
        ];
    }

    /** Total line_items harus sama dengan order.amount (paket + PPN + biaya layanan). */
    protected function lineItems(SubscriptionOrder $order): array
    {
        $items = [[
            'id'       => (string) $order->package_id,
            'name'     => $order->package_name,
            'price'    => $order->subtotal ?? $order->amount,
            'quantity' => 1,
        ]];

        if ($order->ppn_amount > 0) {
            $items[] = [
                'id'       => 'PPN',
                'name'     => 'PPN ' . PpnRate::formatRate($order->ppn_rate),
                'price'    => $order->ppn_amount,
                'quantity' => 1,
            ];
        }

        if ($order->fee_amount > 0) {
            $items[] = [
                'id'       => 'FEE',
                'name'     => 'Biaya layanan ' . $order->payment_method_name,
                'price'    => $order->fee_amount,
                'quantity' => 1,
            ];
        }

        return $items;
    }

    /**
     * Verifikasi signature HTTP Notification dari DOKU (ada di header, dihitung dari raw body).
     */
    public function isValidNotification(Request $request): bool
    {
        $clientId  = (string) $request->header('Client-Id');
        $requestId = (string) $request->header('Request-Id');
        $timestamp = (string) $request->header('Request-Timestamp');
        $signature = (string) $request->header('Signature');

        if ($signature === '' || $requestId === '' || ! hash_equals($this->clientId(), $clientId)) {
            return false;
        }

        $expected = $this->signature($requestId, $timestamp, '/' . ltrim($request->path(), '/'), $request->getContent());

        return hash_equals($expected, $signature);
    }

    protected function signature(string $requestId, string $timestamp, string $target, ?string $body): string
    {
        $component = 'Client-Id:' . $this->clientId() . "\n"
            . 'Request-Id:' . $requestId . "\n"
            . 'Request-Timestamp:' . $timestamp . "\n"
            . 'Request-Target:' . $target;

        if ($body !== null && $body !== '') {
            $component .= "\n" . 'Digest:' . base64_encode(hash('sha256', $body, true));
        }

        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $this->secretKey(), true));
    }
}
