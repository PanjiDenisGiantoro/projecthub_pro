<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PpnRate;
use App\Models\SubscriptionOrder;
use App\Services\DokuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingWebController extends Controller
{
    use HasPerPage;

    public function __construct(protected DokuService $doku)
    {
    }

    /** GET /billing/renew — halaman daftar paket untuk perpanjangan mandiri. */
    public function renew(Request $request)
    {
        $user       = Auth::user();
        $registrant = $user->companyRegistrant();

        $packages = Package::tiers()->active()
            ->where('cta_type', 'register')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->orderBy('price')
            ->get();
        $contactPackage = Package::tiers()->active()->where('cta_type', 'contact')->orderBy('sort_order')->first();
        $ownedSlugs = $registrant?->packages->pluck('slug')->toArray() ?? [];

        return view('billing.renew', [
            'packages'       => $packages,
            'ppnRate'        => PpnRate::activeOn(),
            'contactPackage' => $contactPackage,
            'ownedSlugs'     => $ownedSlugs,
            'registrant'     => $registrant,
        ]);
    }

    /** GET /billing/history — riwayat pembayaran perusahaan. */
    public function history(Request $request)
    {
        abort_unless(Auth::user()->can('access billing'), 403);

        $orders = SubscriptionOrder::with('package')
            ->where('company_id', Auth::user()->company_id)
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('billing.history', compact('orders'));
    }

    /** POST /billing/checkout/{package} — buat transaksi DOKU Checkout & redirect ke halaman pembayaran. */
    public function checkout(Request $request, Package $package)
    {
        $user       = Auth::user();
        $registrant = $user->companyRegistrant();

        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active, 404);

        $price = PpnRate::breakdown((int) $package->price);

        $order = SubscriptionOrder::create([
            'order_number'  => 'SUB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'user_id'       => $registrant->id,
            'company_id'    => $registrant->company_id,
            'package_id'    => $package->id,
            'package_name'  => $package->name,
            'subtotal'      => $price['subtotal'],
            'ppn_rate'      => $price['rate'],
            'ppn_amount'    => $price['ppn'],
            'amount'        => $price['total'],
            'duration_days' => $package->duration_days,
            'status'        => 'pending',
        ]);

        try {
            $result = $this->doku->createPayment($order);
        } catch (\Throwable $e) {
            Log::error('Checkout DOKU gagal', ['order_number' => $order->order_number, 'error' => $e->getMessage()]);

            return back()->with('error', 'Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        $order->update(['snap_token' => $result['token_id'] ?? null]);

        return redirect()->away($result['url']);
    }

    /** GET /billing/finish — halaman kembali setelah user menyelesaikan/membatalkan pembayaran di DOKU. */
    public function finish(Request $request)
    {
        $order = SubscriptionOrder::where('order_number', $request->query('order_id'))->first();

        return view('billing.finish', ['order' => $order]);
    }

    /** GET /billing/status/{order} — polling status order dari halaman finish (untuk VA yang baru settle via webhook belakangan). */
    public function status(Request $request, SubscriptionOrder $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        return response()->json(['status' => $order->status]);
    }

    /** POST /billing/notification — HTTP Notification server-to-server dari DOKU. */
    public function notification(Request $request)
    {
        $payload = $request->json()->all();

        if (! $this->doku->isValidNotification($request)) {
            Log::warning('DOKU notification: signature tidak valid', ['payload' => $payload]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = SubscriptionOrder::where('order_number', data_get($payload, 'order.invoice_number'))->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->isPaid()) {
            return response()->json(['message' => 'Already processed']);
        }

        if ((int) data_get($payload, 'order.amount') !== $order->amount) {
            Log::warning('DOKU notification: nominal tidak cocok', ['order_number' => $order->order_number, 'payload' => $payload]);

            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        $newStatus = match (data_get($payload, 'transaction.status')) {
            'SUCCESS' => 'paid',
            'FAILED'  => 'failed',
            default   => 'pending',
        };

        DB::transaction(function () use ($order, $newStatus, $payload) {
            $order->update([
                'status'                  => $newStatus,
                'midtrans_transaction_id' => data_get($payload, 'transaction.original_request_id', $order->midtrans_transaction_id),
                'raw_notification'        => $payload,
                'paid_at'                 => $newStatus === 'paid' ? now() : $order->paid_at,
            ]);

            if ($newStatus === 'paid') {
                $registrant = $order->user;
                $base = $registrant->active_until && $registrant->active_until->isFuture()
                    ? $registrant->active_until
                    : now();

                $registrant->update(['active_until' => $base->copy()->addDays($order->duration_days)]);

                // Semua paket tier (kartu harga) adalah bundle yang mencakup semua modul (HRIS & Task Management).
                $packageIds = [$order->package_id];
                if ($order->package?->type === 'tier') {
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
