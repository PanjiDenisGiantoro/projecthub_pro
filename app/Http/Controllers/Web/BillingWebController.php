<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\SubscriptionOrder;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingWebController extends Controller
{
    public function __construct(protected MidtransService $midtrans)
    {
    }

    /** GET /billing/renew — halaman daftar paket untuk perpanjangan mandiri. */
    public function renew(Request $request)
    {
        $user       = Auth::user();
        $registrant = $user->companyRegistrant();

        $packages = Package::active()->where('slug', 'pro')->orderBy('price')->get();
        $ownedSlugs = $registrant?->packages->pluck('slug')->toArray() ?? [];

        return view('billing.renew', [
            'packages'   => $packages,
            'ownedSlugs' => $ownedSlugs,
            'registrant' => $registrant,
        ]);
    }

    /** POST /billing/checkout/{package} — buat transaksi Midtrans & redirect ke halaman pembayaran. */
    public function checkout(Request $request, Package $package)
    {
        $user       = Auth::user();
        $registrant = $user->companyRegistrant();

        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active, 404);

        $order = SubscriptionOrder::create([
            'order_number'  => 'SUB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'user_id'       => $registrant->id,
            'company_id'    => $registrant->company_id,
            'package_id'    => $package->id,
            'package_name'  => $package->name,
            'amount'        => $package->price,
            'duration_days' => $package->duration_days,
            'status'        => 'pending',
        ]);

        try {
            $result = $this->midtrans->createTransaction($order);
        } catch (\Throwable $e) {
            Log::error('Checkout Midtrans gagal', ['order_number' => $order->order_number, 'error' => $e->getMessage()]);

            return back()->with('error', 'Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        $order->update(['snap_token' => $result['token'] ?? null]);

        return redirect()->away($result['redirect_url']);
    }

    /** GET /billing/finish — halaman kembali setelah user menyelesaikan/membatalkan pembayaran di Midtrans. */
    public function finish(Request $request)
    {
        $order = SubscriptionOrder::where('order_number', $request->query('order_id'))->first();

        return view('billing.finish', ['order' => $order]);
    }

    /** POST /billing/notification — webhook server-to-server dari Midtrans. */
    public function notification(Request $request)
    {
        $payload = $request->all();

        if (! $this->midtrans->isValidSignature($payload)) {
            Log::warning('Midtrans notification: signature tidak valid', ['payload' => $payload]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = SubscriptionOrder::where('order_number', $payload['order_id'] ?? null)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->isPaid()) {
            return response()->json(['message' => 'Already processed']);
        }

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus        = $payload['fraud_status'] ?? null;

        $newStatus = match (true) {
            in_array($transactionStatus, ['capture', 'settlement']) && $fraudStatus !== 'challenge' => 'paid',
            in_array($transactionStatus, ['deny', 'cancel', 'expire']) => 'failed',
            default => 'pending',
        };

        DB::transaction(function () use ($order, $newStatus, $payload) {
            $order->update([
                'status'                  => $newStatus,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $order->midtrans_transaction_id,
                'raw_notification'        => $payload,
                'paid_at'                 => $newStatus === 'paid' ? now() : $order->paid_at,
            ]);

            if ($newStatus === 'paid') {
                $registrant = $order->user;
                $base = $registrant->active_until && $registrant->active_until->isFuture()
                    ? $registrant->active_until
                    : now();

                $registrant->update(['active_until' => $base->copy()->addDays($order->duration_days)]);

                // Paket "Pro" adalah bundle yang mencakup semua modul (HRIS & Task Management).
                $packageIds = [$order->package_id];
                if ($order->package?->slug === 'pro') {
                    $packageIds = array_merge(
                        $packageIds,
                        Package::whereIn('slug', ['hris', 'task_management'])->pluck('id')->all()
                    );
                }

                $registrant->packages()->syncWithoutDetaching($packageIds);
            }
        });

        return response()->json(['message' => 'OK']);
    }
}
