<?php

namespace App\Support;

use App\Models\Package;
use App\Models\Project;
use App\Models\SubscriptionOrder;
use App\Models\User;

/**
 * Kuota paket (FLOVIG PRICE) per perusahaan: jumlah project & user.
 *
 * Paket aktif = tier milik user pendaftar (is_registered) dengan urutan tertinggi; perusahaan
 * yang punya pendaftar tapi belum punya tier = Free. Perusahaan tanpa pendaftar (dibuat
 * superadmin / internal) dan super admin tidak dibatasi.
 */
class PlanLimits
{
    private static array $cache = [];

    private function __construct(private ?int $companyId, private ?Package $plan, private bool $unlimited)
    {
    }

    public static function forCompany(?int $companyId): self
    {
        if (! $companyId) {
            return new self(null, null, true);
        }

        return self::$cache[$companyId] ??= self::resolve($companyId);
    }

    public static function forUser(?User $user): self
    {
        return $user && ! $user->is_super_admin ? self::forCompany($user->company_id) : new self(null, null, true);
    }

    public static function current(): self
    {
        return self::forUser(auth()->user());
    }

    /** Dipanggil setelah paket perusahaan berubah (mis. pembayaran sukses). */
    public static function flush(?int $companyId = null): void
    {
        $companyId ? self::$cache = array_diff_key(self::$cache, [$companyId => true]) : self::$cache = [];
    }

    private static function resolve(int $companyId): self
    {
        $registrant = User::where('company_id', $companyId)->where('is_registered', true)->orderBy('id')->first();
        if (! $registrant) {
            return new self($companyId, null, true);
        }

        $plan = $registrant->packages()->where('type', 'tier')->orderByDesc('sort_order')->first()
            ?? Package::tiers()->where('slug', 'free')->first();

        return new self($companyId, $plan, $plan === null);
    }

    public function plan(): ?Package
    {
        return $this->plan;
    }

    public function planName(): string
    {
        return $this->plan?->name ?? 'Free';
    }

    // ── Project ────────────────────────────────────────────────────────────

    /** null = unlimited */
    public function maxProjects(): ?int
    {
        return $this->unlimited ? null : $this->plan->max_projects;
    }

    public function projectCount(): int
    {
        return Project::withoutGlobalScopes()->where('company_id', $this->companyId)->whereNull('deleted_at')->count();
    }

    public function canAddProject(): bool
    {
        $max = $this->maxProjects();

        return $max === null || $this->projectCount() < $max;
    }

    // ── User ───────────────────────────────────────────────────────────────

    /**
     * Batas user: jumlah user yang dibayar di order lunas terakhir untuk paket ini,
     * kalau tidak ada pakai batas atas paket. null = unlimited.
     */
    public function maxUsers(): ?int
    {
        if ($this->unlimited) {
            return null;
        }

        $paid = SubscriptionOrder::where('company_id', $this->companyId)->where('package_id', $this->plan->id)
            ->where('status', 'paid')->latest('paid_at')->value('user_count');

        return $paid ?: $this->plan->max_users;
    }

    /** User aktif perusahaan, tanpa akun client (portal klien tidak dihitung). */
    public function userCount(): int
    {
        return User::where('company_id', $this->companyId)->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))
            ->count();
    }

    public function remainingUsers(): ?int
    {
        $max = $this->maxUsers();

        return $max === null ? null : max(0, $max - $this->userCount());
    }

    public function canAddUser(int $count = 1): bool
    {
        $remaining = $this->remainingUsers();

        return $remaining === null || $remaining >= $count;
    }

    // ── Pesan upgrade ──────────────────────────────────────────────────────

    /** Payload flash 'plan_limit' yang ditampilkan sebagai popup upgrade di layout. */
    public function limitNotice(string $resource): array
    {
        [$label, $max] = $resource === 'user'
            ? ['user', $this->maxUsers()]
            : ['project', $this->maxProjects()];

        return [
            'title'   => 'Batas paket tercapai',
            'message' => "Paket {$this->planName()} Anda maksimal {$max} {$label}. Upgrade paket untuk menambah {$label} lagi.",
            'url'     => route('billing.renew'),
        ];
    }

    public function limitMessage(string $resource): string
    {
        return $this->limitNotice($resource)['message'];
    }
}
