<?php

namespace App\Support;

use App\Models\Package;
use App\Models\Project;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Kuota paket (FLOVIG PRICE) per perusahaan: jumlah project, user & storage.
 *
 * Paket aktif = tier milik user pendaftar (is_registered) dengan urutan tertinggi; perusahaan
 * yang punya pendaftar tapi belum punya tier = Free. Perusahaan tanpa pendaftar (dibuat
 * superadmin / internal) dan super admin tidak dibatasi.
 */
class PlanLimits
{
    private static array $cache = [];

    private ?int $storageUsed = null;

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

    // ── Storage ────────────────────────────────────────────────────────────

    /** Batas storage dalam byte, dibaca dari storage_label paket ("1 GB", "1 TB"). null = unlimited / custom. */
    public function maxStorageBytes(): ?int
    {
        if ($this->unlimited || ! preg_match('/([\d.,]+)\s*(KB|MB|GB|TB)/i', (string) $this->plan->storage_label, $m)) {
            return null;
        }

        $power = ['KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4][strtoupper($m[2])];

        return (int) round((float) str_replace(',', '.', $m[1]) * 1024 ** $power);
    }

    /**
     * Total ukuran file upload milik perusahaan: lampiran project, task, komentar, chat, DM, forum,
     * KB, tiket, cover task, gambar project, cuti, reimburse & invoice. Data yang sudah dihapus
     * (soft delete) tidak dihitung. Avatar, logo & foto wajah absensi tidak masuk kuota.
     */
    public function storageUsedBytes(): int
    {
        return $this->storageUsed ??= array_sum($this->storageBreakdown());
    }

    /** Pemakaian storage per menu dalam byte, mis. ['Project Files' => 1048576, ...]. */
    public function storageBreakdown(): array
    {
        $cid      = $this->companyId;
        $projects = fn ($q) => $q->from('projects')->select('id')->where('company_id', $cid)->whereNull('deleted_at');
        $tasks    = fn ($q) => $q->from('tasks')->select('id')->whereIn('project_id', $projects)->whereNull('deleted_at');

        // File yang hanya disimpan path-nya (tanpa kolom ukuran) → ukuran dibaca dari disk.
        $disk      = Storage::disk('public');
        $diskBytes = fn ($paths) => collect($paths)->filter(fn ($p) => is_string($p) && $p !== '')
            ->sum(fn ($path) => (int) @filesize($disk->path($path)));

        return array_map('intval', [
            'Project Files'    => DB::table('project_files')->whereIn('project_id', $projects)->sum('size'),
            'Lampiran Task'    => DB::table('task_attachments')->where('type', 'file')->whereIn('task_id', $tasks)->sum('file_size'),
            'Komentar Task'    => DB::table('task_comment_attachments')->whereIn('task_comment_id', fn ($q) => $q->from('task_comments')->select('id')->whereIn('task_id', $tasks))->sum('file_size'),
            'Chat Proyek'      => DB::table('message_attachments')->whereIn('message_id', fn ($q) => $q->from('project_messages')->select('id')->whereIn('project_id', $projects)->whereNull('deleted_at'))->sum('file_size'),
            'Direct Message'   => DB::table('direct_message_attachments')->whereIn('message_id', fn ($q) => $q->from('direct_messages')->select('id')->whereNull('deleted_at')
                ->whereIn('conversation_id', fn ($q) => $q->from('conversations')->select('id')->where('company_id', $cid)))->sum('file_size'),
            'Forum'            => DB::table('forum_message_attachments')->whereIn('message_id', fn ($q) => $q->from('forum_messages')->select('id')->whereNull('deleted_at')
                ->whereIn('forum_id', fn ($q) => $q->from('forums')->select('id')->where('company_id', $cid)))->sum('file_size'),
            'Knowledge Base'   => DB::table('kb_article_attachments')->whereIn('article_id', fn ($q) => $q->from('kb_articles')->select('id')->whereIn('project_id', $projects)->whereNull('deleted_at'))->sum('size'),
            'Tiket'            => DB::table('ticket_attachments')->whereIn('ticket_id', fn ($q) => $q->from('bug_tickets')->select('id')->whereIn('project_id', $projects)->whereNull('deleted_at'))->sum('file_size'),
            'Cover Task'       => $diskBytes(DB::table('tasks')->whereIn('id', $tasks)->whereNotNull('cover_image_path')->pluck('cover_image_path')),
            'Gambar Project'   => $diskBytes(DB::table('projects')->where('company_id', $cid)->whereNull('deleted_at')->whereNotNull('images')->pluck('images')
                ->flatMap(fn ($json) => (array) json_decode($json, true))),
            'Cuti (HRIS)'      => $diskBytes(DB::table('leave_requests')->where('company_id', $cid)->whereNotNull('attachment')->pluck('attachment')),
            'Reimburse (HRIS)' => $diskBytes(DB::table('reimbursements')->where('company_id', $cid)->whereNotNull('receipt')->pluck('receipt')),
            'Invoice'          => $diskBytes(DB::table('invoices')->where('company_id', $cid)->whereNull('deleted_at')->whereNotNull('attachment')->pluck('attachment')),
        ]);
    }

    /** Label batas storage untuk tampilan: "1 GB", "Custom", "Unlimited". */
    public function storageLimitLabel(): string
    {
        $max = $this->maxStorageBytes();

        return $max !== null ? self::humanBytes($max) : ($this->plan?->storage_label ?: 'Unlimited');
    }

    public function canStore(int $bytes): bool
    {
        $max = $this->maxStorageBytes();

        return $max === null || $this->storageUsedBytes() + $bytes <= $max;
    }

    /** 1536 → "1.5 KB" */
    public static function humanBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i ? 1 : 0) . ' ' . $units[$i];
    }

    // ── Pesan upgrade ──────────────────────────────────────────────────────

    /** Payload flash 'plan_limit' yang ditampilkan sebagai popup upgrade di layout. */
    public function limitNotice(string $resource): array
    {
        if ($resource === 'storage') {
            $used = $this->storageUsedBytes();

            return [
                'title'   => 'Storage tidak cukup',
                'message' => "Storage paket {$this->planName()} Anda maksimal {$this->plan->storage_label} (terpakai " . self::humanBytes($used)
                    . ', sisa ' . self::humanBytes(max(0, $this->maxStorageBytes() - $used)) . '). File yang di-upload melebihi sisa kapasitas. '
                    . 'Hapus file lama atau upgrade paket untuk menambah kapasitas.',
                'url'     => route('billing.renew'),
            ];
        }

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
