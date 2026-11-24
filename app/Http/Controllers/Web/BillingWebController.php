<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\PpnRate;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\DokuService;
use App\Services\SubscriptionCheckoutService;
use App\Support\PlanLimits;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $registrant = $user->companyOwner();

        $packages = Package::tiers()->active()
            ->where('cta_type', 'register')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->orderBy('sort_order')
            ->get();
        $contactPackage = Package::tiers()->active()->where('cta_type', 'contact')->orderBy('sort_order')->first();
        $ownedSlugs = $registrant?->packages->pluck('slug')->toArray() ?? [];
        $limits     = PlanLimits::forUser($user);

        return view('billing.renew', [
            'packages'       => $packages,
            'ppnRate'        => PpnRate::activeOn(),
            'contactPackage' => $contactPackage,
            'ownedSlugs'     => $ownedSlugs,
            'registrant'     => $registrant,
            'userCount'      => $registrant ? $this->companyUserCount($registrant->company_id) : 1,
            'limits'         => $limits,
        ]);
    }

    /** Jumlah user aktif perusahaan — default jumlah user yang ditagih. */
    private function companyUserCount(?int $companyId): int
    {
        // Sama dengan hitungan kuota: semua user (aktif/nonaktif) tanpa client.
        return max(1, PlanLimits::forCompany($companyId)->userCount());
    }

    /** GET /billing/history — Subscribe & Usage: pemakaian kuota paket + riwayat pembayaran perusahaan. */
    public function history(Request $request)
    {
        abort_unless(Auth::user()->can('access billing'), 403);

        $orders = SubscriptionOrder::with('package')
            ->where('company_id', Auth::user()->company_id)
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $limits    = PlanLimits::forUser(Auth::user());
        $breakdown = collect($limits->storageBreakdown())->filter()->sortDesc();

        return view('billing.history', compact('orders', 'limits', 'breakdown'));
    }

    /** GET /billing/history/{order}/invoice — invoice PDF untuk satu order langganan. */
    public function invoice(SubscriptionOrder $order)
    {
        abort_unless(Auth::user()->can('access billing'), 403);
        abort_unless($order->company_id === Auth::user()->company_id, 403);

        $order->load(['company', 'user', 'package']);

        return Pdf::loadView('billing.invoice-pdf', ['order' => $order])
            ->stream("invoice-{$order->order_number}.pdf");
    }

    /** GET /billing/history/{order}/pay — lanjutkan pembayaran order pending lewat link DOKU yang tersimpan. */
    public function pay(SubscriptionOrder $order)
    {
        abort_unless(Auth::user()->can('access billing'), 403);
        abort_unless($order->company_id === Auth::user()->company_id, 403);

        if (! $order->isPayable()) {
            return redirect()->route('billing.history')
                ->with('error', "Order {$order->order_number} sudah tidak bisa dibayar. Silakan buat pembayaran baru.");
        }

        return redirect()->away($order->payment_url);
    }

    /** GET /billing/checkout/{package}?users=&cycle=&method= — pilih jumlah user, siklus & metode bayar; rincian harga + PPN + biaya layanan. */
    public function checkoutForm(Request $request, Package $package)
    {
        $registrant = Auth::user()->companyOwner();
        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active && $package->price > 0, 404);

        $users = $request->integer('users') ?: $this->companyUserCount($registrant->company_id);
        $quote = $package->quote($users, (string) $request->query('cycle', 'monthly'), $registrant->company_id);

        $methods = PaymentMethod::active()->ordered()->get();

        return view('billing.checkout', [
            'package' => $package,
            'quote'   => $quote,
            'methods' => $methods,
            'fees'    => $methods->mapWithKeys(fn ($m) => [$m->code => $m->feeFor($quote['total'])]),
        ]);
    }

    /** POST /billing/checkout/{package} — buat transaksi DOKU Checkout & redirect ke halaman pembayaran. */
    public function checkout(Request $request, Package $package)
    {
        $user       = Auth::user();
        $registrant = $user->companyOwner();

        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active && $package->price > 0, 404);

        $request->validate([
            'users' => 'required|integer|min:1',
            'cycle'          => 'required|in:' . implode(',', array_keys(Package::CYCLE_MONTHS)),
            'payment_method' => 'required|string',
        ], ['payment_method.required' => 'Pilih metode pembayaran terlebih dahulu.']);

        $method = PaymentMethod::active()->where('code', $request->input('payment_method'))->first();
        if (! $method) {
            return back()->withErrors(['payment_method' => 'Metode pembayaran tidak tersedia.']);
        }

        try {
            $order = app(SubscriptionCheckoutService::class)
                ->checkout($registrant, $package, $request->integer('users'), $request->input('cycle'), $method);
        } catch (\Throwable $e) {
            Log::error('Checkout DOKU gagal', ['package' => $package->slug, 'error' => $e->getMessage()]);

            return back()->with('error', 'Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        return redirect()->away($order->payment_url);
    }

    /** GET /billing/finish — halaman kembali setelah user menyelesaikan/membatalkan pembayaran di DOKU. */
    public function finish(Request $request, ?string $orderNumber = null)
    {
        $order = SubscriptionOrder::where('order_number', $orderNumber ?? $request->query('order_id'))->first();

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
            // Catat metode yang dipilih customer di halaman DOKU (mis. VIRTUAL_ACCOUNT_BRI).
            $channel = data_get($payload, 'channel.id') ?? data_get($payload, 'service.id');

            $order->update([
                'payment_method_code'     => $order->payment_method_code ?? $channel,
                'payment_method_name'     => $order->payment_method_name ?? ($channel ? (PaymentMethod::where('code', $channel)->value('name') ?? Str::headline(Str::lower($channel))) : null),
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

                // Satu perusahaan hanya punya satu tier aktif: lepas tier lama saat ganti/upgrade paket.
                if ($order->package?->type === 'tier') {
                    $registrant->packages()->detach(
                        Package::tiers()->whereKeyNot($order->package_id)->pluck('id')->all()
                    );
                }

                $registrant->packages()->syncWithoutDetaching($packageIds);
                PlanLimits::flush($order->company_id);
            }
        });

        return response()->json(['message' => 'OK']);
    }
}
