<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\PpnRate;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\SubscriptionCheckoutService;
use App\Support\PlanLimits;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API langganan (mobile / integrasi) — padanan BillingWebController.
 * Prefix: /api/billing, auth:sanctum.
 */
class BillingController extends Controller
{
    use HasPerPage;

    public const STATUS_LABELS = [
        'pending' => 'Menunggu Pembayaran',
        'paid'    => 'Lunas',
        'failed'  => 'Gagal',
        'expired' => 'Kedaluwarsa',
    ];

    /** GET /api/billing/subscription — status langganan perusahaan saat ini. */
    public function subscription(Request $request)
    {
        $user       = $request->user();
        $registrant = $user->companyOwner();
        $limits     = PlanLimits::forUser($user);
        $plan       = $limits->plan();

        return response()->json([
            'package'        => $plan ? ['id' => $plan->id, 'slug' => $plan->slug, 'name' => $plan->name] : null,
            'plan_name'      => $limits->planName(),
            'active_until'   => $registrant?->active_until?->toIso8601String(),
            'is_lifetime'    => $registrant ? $registrant->isLifetime() : false,
            'is_expired'     => $user->isCompanyExpired(),
            'days_remaining' => $user->companyDaysRemaining(),
            'limits'         => [
                'max_users'     => $limits->maxUsers(),
                'user_count'    => $limits->userCount(),
                'max_projects'  => $limits->maxProjects(),
                'project_count' => $limits->projectCount(),
                'max_storage_bytes'  => $limits->maxStorageBytes(),
                'storage_used_bytes' => $limits->storageUsedBytes(),
            ],
        ]);
    }

    /** GET /api/billing/usage — pemakaian kuota paket: project, user & storage (rincian per menu). null = unlimited. */
    public function usage(Request $request)
    {
        $this->authorizeBilling($request);

        $limits    = PlanLimits::forUser($request->user());
        $percent   = fn (int $used, ?int $max) => $max ? min(100, round($used / $max * 100, 1)) : null;
        $users     = $limits->userCount();
        $projects  = $limits->projectCount();
        $storage   = $limits->storageUsedBytes();
        $maxUsers  = $limits->maxUsers();
        $maxProj   = $limits->maxProjects();
        $maxBytes  = $limits->maxStorageBytes();

        return response()->json([
            'plan_name' => $limits->planName(),
            'project'   => [
                'used'      => $projects,
                'limit'     => $maxProj,
                'remaining' => $maxProj === null ? null : max(0, $maxProj - $projects),
                'percent'   => $percent($projects, $maxProj),
                'can_add'   => $limits->canAddProject(),
            ],
            'user'      => [
                'used'      => $users,
                'limit'     => $maxUsers,
                'remaining' => $limits->remainingUsers(),
                'percent'   => $percent($users, $maxUsers),
                'can_add'   => $limits->canAddUser(),
                'note'      => 'User aktif & nonaktif dihitung; akun client tidak dihitung.',
            ],
            'storage'   => [
                'used_bytes'      => $storage,
                'used_label'      => PlanLimits::humanBytes($storage),
                'limit_bytes'     => $maxBytes,
                'limit_label'     => $limits->storageLimitLabel(),
                'remaining_bytes' => $maxBytes === null ? null : max(0, $maxBytes - $storage),
                'percent'         => $percent($storage, $maxBytes),
                'breakdown'       => collect($limits->storageBreakdown())->filter()->sortDesc()
                    ->map(fn (int $bytes, string $menu) => ['menu' => $menu, 'bytes' => $bytes, 'label' => PlanLimits::humanBytes($bytes)])
                    ->values(),
            ],
        ]);
    }

    /** GET /api/billing/packages — paket yang bisa dibeli mandiri (sama dengan halaman billing.renew). */
    public function packages(Request $request)
    {
        $registrant = $request->user()->companyOwner();
        $ownedSlugs = $registrant?->packages->pluck('slug')->all() ?? [];

        $packages = Package::tiers()->active()
            ->where('cta_type', 'register')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        $contact = Package::tiers()->active()->where('cta_type', 'contact')->orderBy('sort_order')->first();
        $ppn     = PpnRate::activeOn();

        return response()->json([
            'data' => $packages->map(fn (Package $p) => $this->packageJson($p) + [
                'is_owned' => in_array($p->slug, $ownedSlugs, true),
            ])->values(),
            'contact_package'    => $contact ? $this->packageJson($contact) : null,
            'ppn_rate'           => $ppn?->rate ?? 0,
            'default_user_count' => $this->companyUserCount($registrant?->company_id),
            'cycles'             => array_keys(Package::CYCLE_MONTHS),
        ]);
    }

    /** GET /api/billing/payment-methods — metode pembayaran aktif. */
    public function paymentMethods()
    {
        return response()->json([
            'data' => PaymentMethod::active()->ordered()->get()->map(fn (PaymentMethod $m) => [
                'code'        => $m->code,
                'name'        => $m->name,
                'group'       => $m->group,
                'fee_flat'    => $m->fee_flat,
                'fee_percent' => (float) $m->fee_percent,
            ])->values(),
        ]);
    }

    /** GET /api/billing/packages/{package}/quote?users=&cycle= — rincian harga + PPN + biaya layanan per metode. */
    public function quote(Request $request, Package $package)
    {
        $registrant = $request->user()->companyOwner();
        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active && $package->price > 0, 404);

        $request->validate([
            'users' => 'nullable|integer|min:1',
            'cycle' => 'nullable|in:' . implode(',', array_keys(Package::CYCLE_MONTHS)),
        ]);

        $users = $request->integer('users') ?: $this->companyUserCount($registrant->company_id);
        $quote = $package->quote($users, (string) $request->query('cycle', 'monthly'), $registrant->company_id);

        return response()->json([
            'package' => $this->packageJson($package),
            'quote'   => $this->quoteJson($quote),
            'fees'    => PaymentMethod::active()->ordered()->get()->map(fn (PaymentMethod $m) => [
                'code'  => $m->code,
                'name'  => $m->name,
                'fee'   => $fee = $m->feeFor($quote['total']),
                'total' => $quote['total'] + $fee,
            ])->values(),
        ]);
    }

    /** POST /api/billing/checkout/{package} — buat order & transaksi DOKU; client membuka payment_url. */
    public function checkout(Request $request, Package $package, SubscriptionCheckoutService $checkout)
    {
        $registrant = $request->user()->companyOwner();
        abort_unless($registrant, 404, 'Data pendaftar perusahaan tidak ditemukan.');
        abort_unless($package->is_active && $package->price > 0, 404);

        $data = $request->validate([
            'users'          => 'required|integer|min:1',
            'cycle'          => 'required|in:' . implode(',', array_keys(Package::CYCLE_MONTHS)),
            'payment_method' => 'required|string',
        ], ['payment_method.required' => 'Pilih metode pembayaran terlebih dahulu.']);

        $method = PaymentMethod::active()->where('code', $data['payment_method'])->first();
        if (! $method) {
            return response()->json([
                'message' => 'Metode pembayaran tidak tersedia.',
                'errors'  => ['payment_method' => ['Metode pembayaran tidak tersedia.']],
            ], 422);
        }

        try {
            $order = $checkout->checkout($registrant, $package, (int) $data['users'], $data['cycle'], $method);
        } catch (\Throwable $e) {
            Log::error('Checkout DOKU gagal (API)', ['package' => $package->slug, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi.'], 502);
        }

        return response()->json([
            'message'     => 'Order dibuat. Lanjutkan pembayaran melalui payment_url.',
            'payment_url' => $order->payment_url,
            'data'        => $this->orderJson($order->load('package')),
        ], 201);
    }

    /** GET /api/billing/orders — riwayat transaksi langganan perusahaan. */
    public function orders(Request $request)
    {
        $this->authorizeBilling($request);

        $orders = SubscriptionOrder::with('package')
            ->where('company_id', $request->user()->company_id)
            ->latest()
            ->paginate($this->perPage($request));

        $orders->getCollection()->transform(fn (SubscriptionOrder $o) => $this->orderJson($o));

        return response()->json($orders);
    }

    /** GET /api/billing/orders/{order_number} — detail satu order (juga untuk polling status setelah bayar). */
    public function show(Request $request, SubscriptionOrder $order)
    {
        $this->authorizeOrder($request, $order);

        return response()->json(['data' => $this->orderJson($order->load('package'))]);
    }

    /** GET /api/billing/orders/{order_number}/pay — link DOKU untuk melanjutkan pembayaran order pending. */
    public function pay(Request $request, SubscriptionOrder $order)
    {
        $this->authorizeOrder($request, $order);

        if (! $order->isPayable()) {
            return response()->json([
                'message' => "Order {$order->order_number} sudah tidak bisa dibayar. Silakan buat pembayaran baru.",
            ], 422);
        }

        return response()->json([
            'payment_url'        => $order->payment_url,
            'payment_expires_at' => $order->paymentExpiresAt()->toIso8601String(),
        ]);
    }

    /** GET /api/billing/orders/{order_number}/invoice — invoice PDF. */
    public function invoice(Request $request, SubscriptionOrder $order)
    {
        $this->authorizeOrder($request, $order);

        $order->load(['company', 'user', 'package']);

        return Pdf::loadView('billing.invoice-pdf', ['order' => $order])
            ->download("invoice-{$order->order_number}.pdf");
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function authorizeBilling(Request $request): void
    {
        abort_unless($request->user()->can('access billing'), 403);
    }

    private function authorizeOrder(Request $request, SubscriptionOrder $order): void
    {
        $this->authorizeBilling($request);
        abort_unless($order->company_id === $request->user()->company_id, 403);
    }

    private function companyUserCount(?int $companyId): int
    {
        // Sama dengan hitungan kuota: semua user (aktif/nonaktif) tanpa client.
        return max(1, PlanLimits::forCompany($companyId)->userCount());
    }

    private function packageJson(Package $p): array
    {
        return [
            'id'                    => $p->id,
            'slug'                  => $p->slug,
            'name'                  => $p->name,
            'tagline'               => $p->tagline,
            'description'           => $p->description,
            'price_per_user'        => $p->price,
            'price_per_user_yearly' => $p->price_yearly,
            'yearly_saving_percent' => $p->yearlySavingPercent(),
            'min_users'             => $p->min_users,
            'max_users'             => $p->max_users,
            'max_projects'          => $p->max_projects,
            'storage_label'         => $p->storage_label,
            'is_popular'            => (bool) $p->is_popular,
            'cta_type'              => $p->cta_type,
            'features'              => $p->relationLoaded('features') ? $p->features->pluck('label')->filter()->values() : [],
        ];
    }

    private function quoteJson(array $q): array
    {
        return [
            'users'          => $q['users'],
            'cycle'          => $q['cycle'],
            'months'         => $q['months'],
            'price_per_user' => $q['price_per_user'],
            'gross'          => $q['gross'],
            'promo'          => $q['promo'] ? ['id' => $q['promo']->id, 'name' => $q['promo']->name] : null,
            'discount'       => $q['discount'],
            'subtotal'       => $q['subtotal'],
            'ppn_rate'       => $q['rate'],
            'ppn'            => $q['ppn'],
            'total'          => $q['total'],
            'duration_days'  => $q['duration_days'],
        ];
    }

    /** Bentuk satu baris riwayat: No. Order, Paket, Jumlah (rincian), Status, Tanggal Dibuat/Dibayar, Aksi. */
    private function orderJson(SubscriptionOrder $o): array
    {
        $status = $o->displayStatus();

        return [
            'id'                  => $o->id,
            'order_number'        => $o->order_number,
            'package'             => [
                'id'   => $o->package_id,
                'slug' => $o->package?->slug,
                'name' => $o->package_name ?? $o->package?->name,
            ],
            'billing_cycle'       => $o->billing_cycle,
            'user_count'          => $o->user_count,
            'cycle_label'         => $o->cycleLabel(),
            'amount'              => $o->amount,
            'amount_label'        => Package::rupiah($o->amount),
            'breakdown'           => [
                'gross'           => $o->grossAmount(),
                'promo_name'      => $o->promo_name,
                'discount'        => (int) $o->discount_amount,
                'subtotal'        => (int) ($o->subtotal ?? $o->amount),
                'ppn_rate'        => $o->ppn_rate,
                'ppn'             => (int) $o->ppn_amount,
                'fee'             => (int) $o->fee_amount,
                'total'           => $o->amount,
            ],
            'payment_method'      => $o->payment_method_code ? ['code' => $o->payment_method_code, 'name' => $o->payment_method_name] : null,
            'status'              => $status,
            'status_label'        => self::STATUS_LABELS[$status] ?? $status,
            'is_payable'          => $o->isPayable(),
            'duration_days'       => $o->duration_days,
            'created_at'          => $o->created_at?->toIso8601String(),
            'paid_at'             => $o->paid_at?->toIso8601String(),
            'payment_expires_at'  => $o->status === 'pending' ? $o->paymentExpiresAt()->toIso8601String() : null,
            'invoice_url'         => url("/api/billing/orders/{$o->order_number}/invoice"),
        ];
    }
}
