<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\AiTaskActions;
use App\Models\Attendance;
use App\Models\BoardColumn;
use App\Models\BugTicket;
use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationService;
use App\Support\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AiAssistantWebController extends Controller
{
    use AiTaskActions;

    /**
     * llama3.2:1b, bukan 3b — di server CPU-only ini cold-load 1b cuma ~3
     * detik vs ~30-46 detik untuk 3b (2GB vs 1.3GB, tapi gap-nya jauh lebih
     * dari sekadar rasio ukuran), dan eval ~37% lebih cepat token/detik.
     * Kualitas jawaban & tool-calling untuk chat asisten umum masih cukup baik
     * (diuji langsung), jadi worth trade-off dibanding nuansa 3b yang sedikit
     * lebih detail. Ganti balik ke '3b' di sini + di WarmupAiAssistant (lihat
     * bootstrap/app.php) kalau kualitasnya ternyata kurang cocok.
     */
    public const MODEL = 'llama3.2:1b';
    public const KEEP_ALIVE = '60m';

    public function __construct(private NotificationService $notifier) {}

    /**
     * llama3.2:3b (model kecil, self-hosted) cenderung manggil tool yang
     * ditawarkan hampir di setiap pesan kalau tersedia — instruksi di system
     * prompt saja tidak cukup buat nahan itu. Makanya tool "create_project"
     * cuma ditawarkan ke model kalau pesan terakhir user memang kelihatan
     * minta dibuatkan proyek (ada kata kerja "buat/bikin/create/make/new"
     * berdekatan dengan kata "proyek/project"), bukan diputuskan oleh model
     * sendiri. Cukup longgar biar nangkep variasi kalimat ("bikinin proyek
     * dong", "tolong buatkan project baru"), tapi tetap butuh dua kata itu
     * berdekatan supaya "kenapa proyek saya belum muncul" tidak ketangkep.
     */
    private function looksLikeProjectRequest(array $messages): bool
    {
        $lastUser = collect($messages)->last(fn ($m) => ($m['role'] ?? null) === 'user');
        if (!$lastUser) {
            return false;
        }

        $text = strtolower($lastUser['content'] ?? '');

        return (bool) preg_match(
            '/\b(buat|buatkan|bikin|bikinkan|membuat|create|make|new)\w*\s+(\w+\s+){0,2}(proyek|projek|project)\b/u',
            $text
        );
    }

    /**
     * Sama seperti looksLikeProjectRequest(), tapi untuk "task/tugas". Permission
     * buat task itu per-proyek (lihat ProjectPolicy::view), bukan permission
     * global seperti "create project" — jadi di sini cuma dicek pola kalimatnya,
     * pengecekan akses ke proyek spesifik baru dilakukan pas eksekusi
     * (executeCreateTask), setelah nama proyeknya diketahui.
     */
    private function looksLikeTaskRequest(array $messages): bool
    {
        $lastUser = collect($messages)->last(fn ($m) => ($m['role'] ?? null) === 'user');
        if (!$lastUser) {
            return false;
        }

        $text = strtolower($lastUser['content'] ?? '');

        return (bool) preg_match(
            '/\b(buat|buatkan|bikin|bikinkan|membuat|tambah|tambahkan|create|make|new|add)\w*\s+(\w+\s+){0,2}(task|tugas)\b/u',
            $text
        );
    }

    /**
     * Daftar aksi yang boleh diusulkan AI. Setiap aksi TIDAK langsung dieksekusi
     * di sini — cuma diusulkan ke user, dieksekusi setelah user klik konfirmasi
     * lewat executeAction() (yang mengulangi validasi & permission check sendiri,
     * tidak percaya begitu saja pada apa yang "diusulkan" AI).
     */
    private function toolDefinitions(): array
    {
        return [
            'create_project' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'create_project',
                    'description' => 'Membuat proyek baru di ProjectHub. Client, manager, tanggal, dan budget bisa diatur user nanti lewat halaman edit proyek.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'        => ['type' => 'string', 'description' => 'Nama proyek'],
                            'description' => ['type' => 'string', 'description' => 'Deskripsi singkat proyek (opsional)'],
                        ],
                        'required'   => ['name'],
                    ],
                ],
            ],
            'create_task' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'create_task',
                    'description' => 'Menambahkan task baru ke sebuah proyek yang sudah ada. Butuh nama proyek yang sudah disebutkan/dikonfirmasi user, bukan proyek baru.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'project_name' => ['type' => 'string', 'description' => 'Nama proyek tempat task ini ditambahkan'],
                            'title'        => ['type' => 'string', 'description' => 'Judul task'],
                            'description'  => ['type' => 'string', 'description' => 'Deskripsi singkat task (opsional)'],
                        ],
                        'required'   => ['project_name', 'title'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data nyata milik user yang login, disisipkan ke system prompt supaya Runa
     * bisa jawab "siapa saja anggota tim", "proyek apa saja" dll tanpa mengarang
     * (model 1B pasti halu kalau datanya tidak ada). Dibatasi sesuai hak akses
     * yang sama dengan halaman web: daftar anggota cuma untuk yang punya
     * permission "access users", proyek cuma yang bisa dilihat user (company
     * scope + aturan sama seperti executeCreateTask). Dipotong jumlahnya supaya
     * prompt tetap pendek — prompt panjang = lambat di server CPU.
     */
    private function companyContext(): string
    {
        $user  = auth()->user();
        $lines = ['DATA PERUSAHAAN (akurat, dari database, per ' . now()->format('d M Y') . '):'];
        $lines[] = "User yang sedang chat: {$user->name} (role: " . ($user->getRoleNames()->join(', ') ?: '-') . ')';

        $members = $this->visibleMembers();
        if ($members === null) {
            $lines[] = 'ANGGOTA TIM: user ini tidak punya akses melihat daftar anggota (arahkan ke admin).';
        } else {
            $lines[] = "ANGGOTA TIM AKTIF (total {$members->count()} orang" . ($members->count() >= 50 ? ', hanya 50 pertama' : '') . '):';
            foreach ($members->values() as $i => $m) {
                $lines[] = ($i + 1) . ". {$m->name} — " . ($m->roles->pluck('name')->join('/') ?: '-');
            }
        }

        $projects = $this->visibleProjects();
        $lines[] = "PROYEK YANG BISA DIAKSES USER INI (total {$projects->count()}" . ($projects->count() >= 30 ? ', hanya 30 terbaru' : '') . '):';
        foreach ($projects->values() as $i => $p) {
            $lines[] = ($i + 1) . ". {$p->name} — status {$p->status}, progress " . (int) $p->progress . '%'
                . ($p->manager ? ", manager {$p->manager->name}" : '');
        }

        $openTasks = $this->myOpenTasksQuery()->count();
        $lateTasks = $this->myOpenTasksQuery()->whereNotNull('due_date')->whereDate('due_date', '<', today())->count();
        $lines[]   = "TASK USER INI: {$openTasks} task terbuka, {$lateTasks} terlambat.";

        $lines[] = 'ATURAN: kalau ditanya anggota tim / proyek, jawab HANYA dari daftar di atas dan sebutkan SEMUA '
            . 'item di daftar itu (jangan cuma satu), nama persis seperti tertulis. Kalau yang ditanya tidak ada di data itu, bilang datanya tidak '
            . 'tersedia dan arahkan ke menu terkait — JANGAN mengarang nama, jumlah, atau contoh.';

        return implode("\n", $lines);
    }

    /** Anggota aktif (non-client) satu company; null kalau user tidak punya permission "access users". */
    private function visibleMembers(): ?\Illuminate\Support\Collection
    {
        $user = auth()->user();
        if (!$user->company_id || !$user->can('access users')) {
            return null;
        }

        return \App\Models\User::with('roles:id,name')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'client'))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name']);
    }

    /**
     * Query proyek yang bisa dilihat user — sama persis dengan accessScope di
     * ProjectWebController::index & ProjectPolicy::view: client cuma proyek
     * miliknya, selain super admin (TERMASUK role admin) cuma proyek yang ia
     * pimpin / ia jadi anggota timnya. Company scope dari global scope Project.
     */
    private function visibleProjectsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user  = auth()->user();
        $query = Project::query();
        if ($user->hasRole('client')) {
            $query->where('client_id', $user->id);
        } elseif (!$user->is_super_admin) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('client_id', $user->id)
                    ->orWhereHas('members', fn ($q2) => $q2->where('user_id', $user->id));
            });
        }

        return $query;
    }

    private function visibleProjects(): \Illuminate\Support\Collection
    {
        return $this->visibleProjectsQuery()->with('manager:id,name')->latest()->limit(30)
            ->get(['id', 'name', 'status', 'progress', 'manager_id']);
    }

    /** Filter "task belum selesai" — sama dengan dashboard: belum dicentang & bukan di kolom done. */
    private function scopeOpenTasks($query)
    {
        return $query->whereNull('completed_at')
            ->whereNotIn('status', ['done', 'Completed'])
            ->whereDoesntHave('boardColumn', fn ($bc) => $bc->where('is_done', true));
    }

    /** Task yang di-assign ke user / user jadi member task, di proyek company-nya (global scope Project). */
    private function myOpenTasksQuery()
    {
        $uid = auth()->id();

        return $this->scopeOpenTasks(Task::query())
            ->where(fn ($q) => $q->where('assigned_to', $uid)->orWhereHas('members', fn ($m) => $m->where('user_id', $uid)))
            ->whereHas('project');
    }

    /** User boleh lihat data HRIS: paket HRIS aktif & bukan akun client/customer (sama dengan middleware package:hris). */
    private function canUseHris(): bool
    {
        $user = auth()->user();

        return $user->company_id
            && in_array('hris', $user->accessiblePackages())
            && !$user->hasRole(['client', 'customer']);
    }

    private const HRIS_DENIED = 'Maaf, data HRIS (absensi, cuti, lembur, reimburse) tidak tersedia untuk akun Anda — modul HRIS belum aktif atau akun Anda tidak punya akses.';

    private static function fmtDate($date): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') : '-';
    }

    private static function statusLabel(?string $status): string
    {
        return [
            'pending' => 'menunggu persetujuan', 'approved' => 'disetujui', 'rejected' => 'ditolak',
            'cancelled' => 'dibatalkan', 'paid' => 'dibayar',
        ][$status] ?? ($status ?: '-');
    }

    /**
     * Pertanyaan data yang jelas dijawab langsung dari database, tidak lewat
     * model — llama3.2:1b terbukti tidak konsisten (kadang cuma sebut 1 nama,
     * kadang bilang tidak tahu walau datanya ada di prompt). Setiap intent di
     * bawah memakai filter hak akses yang sama dengan halaman web terkait.
     * Pertanyaan "cara/bagaimana/tambah/mau ..." tetap ke model karena itu
     * minta panduan, bukan data. null = bukan pertanyaan data.
     */
    private function directAnswer(array $messages): ?string
    {
        $lastUser = collect($messages)->last(fn ($m) => ($m['role'] ?? null) === 'user');
        $text     = mb_strtolower($lastUser['content'] ?? '');

        if ($text === '') {
            return null;
        }
        // "bagaimana/gimana/kenapa" biasanya minta panduan → model, KECUALI yang
        // ditanya jelas status/progres/sisa data ("reimburse ku gimana statusnya").
        if (preg_match('/\b(bagaimana|gimana|kenapa|mengapa)\b/u', $text)
            && !preg_match('/\b(status\w*|progres\w*|progress|perkembangan|sisa|saldo|kabar\w*)\b/u', $text)) {
            return null;
        }
        if (preg_match('/\b(cara|tambah\w*|hapus\w*|undang\w*|invite|buat\w*|bikin\w*|edit|ubah|ganti|keluar\w*|ajukan|mengajukan)\b|\b(mau|ingin|pengen|pingin)\b(?!\s+(lihat|liat|tahu|tau|cek|check|tanya))/u', $text)) {
            return null;
        }

        $asksList = '(siapa|nama|daftar|list|sebut\w*|apa\s*(saja|aja)|mana\s*(saja|aja)|berapa|semua|lihat)';
        $me       = '\b(saya|aku|ku|gue|gw)\b|\w+ku\b';

        // 1. Yang perlu saya setujui (cek dulu, sebelum intent cuti/lembur/reimburse).
        if (preg_match('/\b(approval|approve|persetujuan)\b|\b(perlu|harus|menunggu)\s+(\w+\s+){0,2}(saya\s+)?(setujui|disetujui|approve|di-?approve)\b/u', $text)
            && !preg_match('/\b(cuti|izin|lembur|reimburse?\w*|klaim)\s+(saya|aku|ku)\b/u', $text)) {
            return $this->answerPendingApprovals();
        }

        // 2. HRIS.
        if (preg_match('/\b(sisa|saldo|jatah|kuota|quota)\b.*\bcuti\b|\bcuti\b.*\b(sisa|saldo|jatah|kuota|quota|tersisa)\b/u', $text)) {
            return $this->answerLeaveBalance();
        }
        if (preg_match('/\b(cuti|izin)\b/u', $text)) {
            return $this->answerMyRequests('leave');
        }
        if (preg_match('/\blembur\w*|\bovertime\b/u', $text)) {
            return $this->answerMyRequests('overtime');
        }
        if (preg_match('/\b(reimburse?\w*|reimbursement|klaim|penggantian biaya)\b/u', $text)) {
            return $this->answerMyRequests('reimbursement');
        }
        if (preg_match('/\b(absen\w*|absensi|kehadiran|presensi|check[\s-]?in|check[\s-]?out|clock[\s-]?in)\b/u', $text)) {
            return $this->answerAttendance();
        }

        // 3a. Aturan SLA tiket (per proyek kalau nama proyek disebut).
        if (preg_match('/\b(sla|prioritas tiket|target selesai)\b/u', $text)) {
            return $this->answerSlaRules($this->mentionedProject($text));
        }

        // 3. Tiket bug.
        if (preg_match('/\b(tiket|ticket|bug)\w*\b/u', $text)) {
            return $this->answerTickets((bool) preg_match("/{$me}/u", $text));
        }

        // 4. Detail satu proyek yang disebut namanya.
        if (preg_match('/\b(progres\w*|progress|status|detail|info\w*|perkembangan|sejauh|berapa|kapan|siapa|task|tugas|deadline|tenggat|anggota|ringkas\w*)\b/u', $text)
            && ($project = $this->mentionedProject($text))) {
            return $this->answerProjectDetail($project);
        }

        // 5. Tugas saya / deadline.
        if (preg_match('/\b(tugas|task|kerjaan|pekerjaan|to-?do|deadline|tenggat|jatuh tempo|terlambat|telat|overdue)\w*\b/u', $text)
            && (preg_match("/{$me}/u", $text) || preg_match('/\b(deadline|tenggat|jatuh tempo|terlambat|telat|overdue)\b/u', $text))) {
            $period = match (true) {
                (bool) preg_match('/\b(hari ini|today)\b/u', $text)          => 'today',
                (bool) preg_match('/\b(besok|tomorrow)\b/u', $text)          => 'tomorrow',
                (bool) preg_match('/\b(minggu|pekan)\s+ini\b/u', $text)      => 'week',
                (bool) preg_match('/\b(minggu|pekan)\s+depan\b/u', $text)    => 'next_week',
                (bool) preg_match('/\bbulan\s+ini\b/u', $text)               => 'month',
                (bool) preg_match('/\b(terlambat|telat|overdue|lewat)\b/u', $text) => 'overdue',
                default                                                     => null,
            };

            return $this->answerMyTasks($period);
        }

        // 6. Daftar anggota tim.
        if (preg_match("/\\b{$asksList}\\b.*\\b(tim|team|anggota|member|karyawan|pegawai|staff|staf)\\b|\\b(tim|team|anggota|member|karyawan|pegawai|staff|staf)\\w*\\b.*\\b{$asksList}\\b/u", $text)) {
            $members = $this->visibleMembers();
            if ($members === null) {
                return 'Maaf, akun Anda tidak punya akses untuk melihat daftar anggota tim. Silakan hubungi admin perusahaan Anda.';
            }
            if ($members->isEmpty()) {
                return 'Belum ada anggota tim aktif di perusahaan Anda.';
            }

            return "Anggota tim aktif di perusahaan Anda ({$members->count()} orang" . ($members->count() >= 50 ? ', 50 pertama' : '') . "):\n"
                . $members->values()->map(fn ($m, $i) => ($i + 1) . ". {$m->name} — " . ($m->roles->pluck('name')->join('/') ?: '-'))->join("\n")
                . "\n\nDetail lengkap bisa dilihat di menu Anggota Tim.";
        }

        // 7. Daftar proyek.
        if (preg_match("/\\b{$asksList}\\b.*\\b(proyek|projek|project)\\w*\\b|\\b(proyek|projek|project)\\w*\\b.*\\b{$asksList}\\b/u", $text)) {
            $projects = $this->visibleProjects();
            if ($projects->isEmpty()) {
                return 'Belum ada proyek yang bisa Anda akses.';
            }

            return "Proyek yang bisa Anda akses ({$projects->count()}" . ($projects->count() >= 30 ? ', 30 terbaru' : '') . "):\n"
                . $projects->values()->map(fn ($p, $i) => ($i + 1) . ". {$p->name} — status {$p->status}, progress " . (int) $p->progress . '%'
                    . ($p->manager ? ", manager {$p->manager->name}" : ''))->join("\n")
                . "\n\nDetail lengkap bisa dilihat di menu Proyek.";
        }

        return null;
    }

    /** $period: null (semua) | today | tomorrow | week | next_week | month | overdue — filter berdasarkan due_date. */
    private function answerMyTasks(?string $period = null): string
    {
        $base = $this->myOpenTasksQuery();
        [$from, $to, $label] = match ($period) {
            'today'     => [today(), today(), 'dengan deadline hari ini'],
            'tomorrow'  => [today()->addDay(), today()->addDay(), 'dengan deadline besok'],
            'week'      => [today()->startOfWeek(), today()->endOfWeek(), 'dengan deadline minggu ini'],
            'next_week' => [today()->addWeek()->startOfWeek(), today()->addWeek()->endOfWeek(), 'dengan deadline minggu depan'],
            'month'     => [today()->startOfMonth(), today()->endOfMonth(), 'dengan deadline bulan ini'],
            'overdue'   => [null, today()->subDay(), 'yang sudah lewat deadline'],
            default     => [null, null, ''],
        };
        if ($from) {
            $base->whereDate('due_date', '>=', $from);
        }
        if ($to) {
            $base->whereDate('due_date', '<=', $to);
        }

        $total = (clone $base)->count();
        if ($total === 0) {
            return $period
                ? "Tidak ada task terbuka {$label} yang di-assign ke Anda."
                    . (($all = $this->myOpenTasksQuery()->count()) ? " (Total task terbuka Anda: {$all} — tanya \"tugas saya\" untuk lihat semua.)" : '')
                : 'Tidak ada task terbuka yang di-assign ke Anda saat ini. 🎉';
        }

        $overdue = (clone $base)->whereNotNull('due_date')->whereDate('due_date', '<', today())->count();
        $tasks   = (clone $base)->with('project:id,name')
            ->orderByRaw('due_date is null')->orderBy('due_date')->limit(15)
            ->get(['id', 'project_id', 'title', 'status', 'priority', 'due_date']);

        $lines = $tasks->values()->map(function ($t, $i) {
            $due = '';
            if ($t->due_date) {
                $d   = (int) today()->diffInDays($t->due_date, false);
                $due = $d < 0 ? ', TERLAMBAT ' . abs($d) . ' hari' : ($d === 0 ? ', deadline HARI INI' : ', deadline ' . self::fmtDate($t->due_date) . " ({$d} hari lagi)");
            }

            return ($i + 1) . ". {$t->title} — proyek " . ($t->project->name ?? '-') . ", status {$t->status}"
                . ($t->priority ? ", prioritas {$t->priority}" : '') . $due;
        })->join("\n");

        return "Anda punya {$total} task terbuka" . ($label ? " {$label}" : '') . ($overdue && $period !== 'overdue' ? ", {$overdue} di antaranya terlambat" : '')
            . ($total > 15 ? ' (15 dengan deadline terdekat)' : '') . ":\n{$lines}\n\nDetail lengkap bisa dilihat di Dashboard atau halaman proyek masing-masing.";
    }

    /** Cari proyek (yang bisa diakses user) yang namanya disebut di pesan; ambil yang namanya paling panjang. */
    private function mentionedProject(string $text): ?Project
    {
        $match = $this->visibleProjectsQuery()->limit(300)->get(['id', 'name'])
            ->filter(fn ($p) => mb_strlen($p->name) >= 3 && str_contains($text, mb_strtolower($p->name)))
            ->sortByDesc(fn ($p) => mb_strlen($p->name))
            ->first();

        return $match ? $this->visibleProjectsQuery()->with('manager:id,name')->find($match->id) : null;
    }

    private function answerProjectDetail(Project $project): string
    {
        $tasks      = Task::where('project_id', $project->id);
        $total      = (clone $tasks)->count();
        $open       = $this->scopeOpenTasks(clone $tasks);
        $openCount  = (clone $open)->count();
        $overdue    = (clone $open)->whereNotNull('due_date')->whereDate('due_date', '<', today())->count();
        $members    = $project->members()->count();
        $tickets    = $project->tickets()->whereNotIn('status', ['resolved', 'closed'])->count();
        $nextTasks  = (clone $open)->whereNotNull('due_date')->with('assignee:id,name')
            ->orderBy('due_date')->limit(5)->get(['id', 'title', 'due_date', 'assigned_to']);
        $milestone  = $project->milestones()->where('status', '!=', 'completed')->whereNotNull('due_date')
            ->orderBy('due_date')->first(['id', 'title', 'due_date', 'status']);

        $lines = [
            "Proyek \"{$project->name}\":",
            "• Status: {$project->status}, progress " . (int) $project->progress . '%',
            '• Manager: ' . ($project->manager->name ?? '-'),
            '• Periode: ' . self::fmtDate($project->start_date) . ' s/d ' . self::fmtDate($project->end_date),
            "• Anggota: {$members} orang",
            "• Task: {$total} total, " . ($total - $openCount) . " selesai, {$openCount} belum selesai" . ($overdue ? " ({$overdue} terlambat)" : ''),
            "• Tiket bug terbuka: {$tickets}",
        ];
        if ($milestone) {
            $lines[] = "• Milestone berikutnya: {$milestone->title} — target " . self::fmtDate($milestone->due_date);
        }
        if ($nextTasks->isNotEmpty()) {
            $lines[] = "\nTask dengan deadline terdekat:";
            foreach ($nextTasks->values() as $i => $t) {
                $lines[] = ($i + 1) . ". {$t->title} — " . self::fmtDate($t->due_date) . ($t->assignee ? ", PIC {$t->assignee->name}" : '')
                    . ($t->due_date->lt(today()) ? ' (TERLAMBAT)' : '');
            }
        }
        $lines[] = "\nDetail lengkap bisa dilihat di halaman proyek.";

        return implode("\n", $lines);
    }

    private function answerSlaRules(?Project $project): string
    {
        $rules = app(\App\Services\SlaService::class)->rulesForProject($project?->id);
        $lines = [($project ? "Aturan SLA tiket proyek \"{$project->name}\"" : 'Aturan SLA tiket (default)')
            . ' — target dihitung sejak tiket dibuat:'];
        foreach ($rules as $key => $r) {
            $meta    = \App\Models\SlaPolicy::PRIORITIES[$key];
            $lines[] = "• {$meta['label']}: respons ≤ " . \App\Models\SlaPolicy::humanMinutes($r['response'])
                . ', selesai ≤ ' . \App\Models\SlaPolicy::humanMinutes($r['resolution'])
                . ($r['business_hours'] ? ' (jam kerja)' : '') . " — {$meta['desc']}";
        }
        $lines[] = $project
            ? "\nAturan bisa dilihat & diubah (manager proyek/admin) di tab Tickets proyek."
            : "\nTiap proyek bisa punya aturan sendiri di tab Tickets proyek — sebut nama proyeknya untuk melihat aturan proyek itu.";

        return implode("\n", $lines);
    }

    private function answerTickets(bool $mineOnly): string
    {
        $uid  = auth()->id();
        $base = BugTicket::whereHas('project', fn ($p) => $p->whereIn('projects.id', $this->visibleProjectsQuery()->select('projects.id')))
            ->whereNotIn('status', ['resolved', 'closed']);
        $mine = (clone $base)->where(fn ($q) => $q->where('assignee_id', $uid)->orWhereHas('assignees', fn ($a) => $a->where('users.id', $uid)));

        $list = ($mineOnly ? $mine : $base)->with('project:id,name')
            ->orderByRaw("field(priority, 'critical', 'urgent', 'high', 'medium', 'low')")->latest()
            ->limit(10)->get(['id', 'project_id', 'title', 'priority', 'status', 'sla_due_at', 'sla_breached', 'sla_paused', 'sla_paused_at', 'sla_policy_id', 'created_at', 'resolved_at', 'closed_at', 'updated_at']);
        $count = ($mineOnly ? (clone $mine) : (clone $base))->count();

        if ($count === 0) {
            return $mineOnly ? 'Tidak ada tiket bug terbuka yang di-assign ke Anda.' : 'Tidak ada tiket bug terbuka di proyek yang bisa Anda akses.';
        }

        return ($mineOnly ? 'Tiket bug terbuka yang di-assign ke Anda' : 'Tiket bug terbuka di proyek yang bisa Anda akses')
            . " ({$count}" . ($count > 10 ? ', 10 teratas' : '') . "):\n"
            . $list->values()->map(fn ($t, $i) => ($i + 1) . ". {$t->title} — proyek " . ($t->project->name ?? '-')
                . ", prioritas {$t->priority}, status {$t->status}"
                . ($t->sla_due_at ? ', target selesai ' . $t->sla_due_at->format('d M Y H:i') . ' (' . BugTicket::SLA_STATES[$t->slaState()]['label'] . ')' : ''))->join("\n")
            . "\n\nDetail lengkap bisa dilihat di menu Bug Tickets.";
    }

    private function answerAttendance(): string
    {
        if (!$this->canUseHris()) {
            return self::HRIS_DENIED;
        }

        $uid   = auth()->id();
        $today = Attendance::where('user_id', $uid)->whereDate('date', today())->first();
        $month = Attendance::where('user_id', $uid)->whereYear('date', today()->year)->whereMonth('date', today()->month)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $lines = ['Absensi Anda hari ini (' . self::fmtDate(today()) . '):'];
        if (!$today || !$today->check_in) {
            $lines[] = '• Belum check-in.';
        } else {
            $lines[] = '• Check-in: ' . substr($today->check_in, 0, 5) . ($today->location_in ? " ({$today->location_in})" : '');
            $lines[] = '• Check-out: ' . ($today->check_out ? substr($today->check_out, 0, 5) : 'belum');
            if ($today->check_in_2) {
                $lines[] = '• Sesi 2: ' . substr($today->check_in_2, 0, 5) . ' – ' . ($today->check_out_2 ? substr($today->check_out_2, 0, 5) : 'belum check-out');
            }
            $lines[] = "• Status: {$today->status}";
        }

        $lines[] = "\nRekap bulan " . today()->translatedFormat('F Y') . ':';
        if ($month->isEmpty()) {
            $lines[] = '• Belum ada catatan absensi bulan ini.';
        } else {
            foreach ($month as $status => $total) {
                $lines[] = "• {$status}: {$total} hari";
            }
        }
        $lines[] = "\nDetail lengkap bisa dilihat di menu Absensi.";

        return implode("\n", $lines);
    }

    private function answerLeaveBalance(): string
    {
        if (!$this->canUseHris()) {
            return self::HRIS_DENIED;
        }

        $user     = auth()->user();
        $year     = now()->year;
        $types    = \App\Models\LeaveType::where('company_id', $user->company_id)->where('is_active', true)
            ->where('has_balance', true)->orderBy('sort_order')->get();
        $balances = \App\Models\LeaveBalance::where('user_id', $user->id)->where('year', $year)->get()->keyBy('leave_type_id');

        if ($types->isEmpty()) {
            return 'Belum ada jenis cuti berkuota yang diatur perusahaan Anda. Silakan hubungi admin/HR.';
        }

        $lines = $types->values()->map(function ($t, $i) use ($balances, $user) {
            $b     = $balances[$t->id] ?? null;
            $quota = $b ? $b->quota + $b->carried_over : (int) $t->default_quota;
            $used  = $b ? (int) $b->used : 0;
            $note  = $t->isEligible($user) ? '' : ' (belum bisa diambil' . (($msg = $t->tenureBlockedMessage($user)) ? ": {$msg}" : '') . ')';

            return ($i + 1) . ". {$t->name}: sisa " . ($quota - $used) . " dari {$quota} hari (terpakai {$used})" . $note;
        })->join("\n");

        return "Saldo cuti Anda tahun {$year}:\n{$lines}\n\nDetail & pengajuan bisa lewat menu Cuti & Izin.";
    }

    /** Riwayat pengajuan milik user sendiri (cuti / lembur / reimburse). */
    private function answerMyRequests(string $type): string
    {
        if (!$this->canUseHris()) {
            return self::HRIS_DENIED;
        }

        $uid = auth()->id();
        [$model, $label, $menu] = match ($type) {
            'leave'         => [\App\Models\LeaveRequest::class, 'cuti/izin', 'Cuti & Izin'],
            'overtime'      => [\App\Models\Overtime::class, 'lembur', 'Lembur'],
            'reimbursement' => [\App\Models\Reimbursement::class, 'reimburse', 'Reimburse'],
        };

        $base    = $model::where('user_id', $uid)->where('company_id', auth()->user()->company_id);
        $pending = (clone $base)->where('status', 'pending')->count();
        $items   = (clone $base)->latest()->limit(5)->get();

        if ($items->isEmpty()) {
            return "Anda belum pernah mengajukan {$label}.";
        }

        $lines = $items->values()->map(function ($r, $i) use ($type) {
            $desc = match ($type) {
                'leave'         => ($r->leaveType->name ?? 'Cuti') . ' ' . self::fmtDate($r->start_date) . ' s/d ' . self::fmtDate($r->end_date) . " ({$r->total_days} hari)",
                'overtime'      => self::fmtDate($r->date) . ' ' . substr((string) $r->start_time, 0, 5) . '–' . substr((string) $r->end_time, 0, 5) . " ({$r->total_hours} jam)",
                'reimbursement' => "{$r->title} — Rp " . number_format((float) $r->amount, 0, ',', '.') . ' (' . self::fmtDate($r->expense_date) . ')',
            };

            return ($i + 1) . ". {$desc} — " . self::statusLabel($r->status)
                . ($r->status === 'rejected' && $r->rejection_reason ? " (alasan: {$r->rejection_reason})" : '');
        })->join("\n");

        return "Pengajuan {$label} terakhir Anda" . ($pending ? " ({$pending} masih menunggu persetujuan)" : '') . ":\n{$lines}\n\nDetail lengkap bisa dilihat di menu {$menu}.";
    }

    /** Pengajuan yang menunggu persetujuan user ini — hanya jenis yang memang boleh dia approve. */
    private function answerPendingApprovals(): string
    {
        $user  = auth()->user();
        $cid   = $user->company_id;
        $lines = [];
        $total = 0;

        if ($this->canUseHris()) {
            $sources = [
                'approve leave'         => [\App\Models\LeaveRequest::class, 'Cuti/izin', fn ($r) => ($r->leaveType->name ?? 'Cuti') . ' ' . self::fmtDate($r->start_date) . " ({$r->total_days} hari)"],
                'approve overtime'      => [\App\Models\Overtime::class, 'Lembur', fn ($r) => self::fmtDate($r->date) . " ({$r->total_hours} jam)"],
                'approve reimbursement' => [\App\Models\Reimbursement::class, 'Reimburse', fn ($r) => "{$r->title} Rp " . number_format((float) $r->amount, 0, ',', '.')],
            ];
            foreach ($sources as $perm => [$model, $label, $fmt]) {
                if (!$user->can($perm)) {
                    continue;
                }
                $q     = $model::where('company_id', $cid)->where('status', 'pending')->where('user_id', '!=', $user->id);
                $count = (clone $q)->count();
                if ($count === 0) {
                    continue;
                }
                $total  += $count;
                $lines[] = "{$label} ({$count}):";
                foreach ((clone $q)->with('user:id,name')->oldest()->limit(5)->get() as $r) {
                    $lines[] = '  • ' . ($r->user->name ?? '-') . ' — ' . $fmt($r);
                }
            }
        }

        $roles   = $user->getRoleNames()->toArray();
        $generic = \App\Models\Approval::where('status', 'pending')
            ->whereHas('requester', fn ($q) => $q->where('company_id', $cid))
            ->whereHas('steps', fn ($q) => $q->where('status', 'pending')->whereIn('approver_role', $roles))
            ->count();
        if ($generic) {
            $total  += $generic;
            $lines[] = "Approval lain (proyek/tiket): {$generic} — buka menu Approvals.";
        }

        if ($total === 0) {
            return 'Tidak ada pengajuan yang menunggu persetujuan Anda saat ini.';
        }

        return "Ada {$total} pengajuan yang menunggu persetujuan Anda:\n" . implode("\n", $lines)
            . "\n\nSetujui/tolak lewat menu terkait (Cuti & Izin, Lembur, Reimburse, atau Approvals).";
    }

    public function chat(Request $request)
    {
        $request->validate([
            'messages'            => 'required|array|min:1|max:20',
            'messages.*.role'     => 'required|in:user,assistant',
            'messages.*.content'  => 'required|string|max:4000',
        ]);

        $messages = [
            ['role' => 'system', 'content' => config('ai_assistant.system_prompt') . "\n\n" . $this->companyContext()],
            ...$request->messages,
        ];

        // Perintah ubah task ("tandai task X selesai", "pindahkan task X ke Review",
        // "ubah deadline task X jadi besok", ...) di-parse langsung tanpa model →
        // usulan aksi yang dikonfirmasi user (lihat AiTaskActions).
        if (($proposal = $this->proposeTaskAction($request->messages)) !== null) {
            return response()->json($proposal);
        }

        // Cuma tawarkan tool ke model kalau pesan user memang kelihatan minta
        // aksi itu — supaya AI tidak mengusulkan sesuatu yang tidak diminta
        // (lihat komentar di looksLikeProjectRequest/looksLikeTaskRequest).
        $available = $this->toolDefinitions();
        $tools     = [];
        if (auth()->user()->can('create project') && $this->looksLikeProjectRequest($request->messages)) {
            $tools[] = $available['create_project'];
        }
        if ($this->looksLikeTaskRequest($request->messages)) {
            $tools[] = $available['create_task'];
        }

        // Kalau ada tool yang mungkin dipanggil, kita butuh isi pesan LENGKAP dulu
        // buat deteksi tool_calls sebelum tahu mau kirim teks biasa atau usulan
        // aksi ke user — jadi jalur ini tetap non-streaming (dan jarang kepakai,
        // cuma saat pesan user kelihatan minta buat proyek/task).
        if (!empty($tools)) {
            return $this->chatWithTools($messages, $tools);
        }

        // Pertanyaan daftar tim/proyek → jawab langsung dari DB (format sama
        // dengan jalur streaming: text/plain), tidak lewat model.
        if (($direct = $this->directAnswer($request->messages)) !== null) {
            return response($direct, 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-cache']);
        }

        // Jalur normal (mayoritas pesan): streaming token-per-token supaya user
        // mulai lihat balasan muncul begitu model mulai generate, bukan nunggu
        // seluruh balasan selesai (bisa puluhan detik di CPU tanpa GPU).
        return $this->chatStreaming($messages);
    }

    /**
     * Streaming reply lewat Ollama /api/chat (stream:true) — tiap baris respons
     * Ollama adalah satu objek JSON berisi potongan teks (message.content), di-
     * forward apa adanya (plain text, bukan dibungkus JSON) ke browser begitu
     * diterima lewat response()->stream(). keep_alive dilonggarkan supaya model
     * tidak ke-unload dari memory tiap idle >5 menit (default Ollama), yang
     * kalau kejadian bikin request berikutnya kena cold-start >90 detik.
     */
    private function chatStreaming(array $messages)
    {
        return response()->stream(function () use ($messages) {
            try {
                $client = new \GuzzleHttp\Client();
                $res = $client->post('http://127.0.0.1:11434/api/chat', [
                    'json' => [
                        'model'      => self::MODEL,
                        'messages'   => $messages,
                        'stream'     => true,
                        'keep_alive' => self::KEEP_ALIVE,
                    ],
                    'stream'  => true,
                    'timeout' => 110,
                ]);

                $body   = $res->getBody();
                $buffer = '';
                while (!$body->eof()) {
                    $buffer .= $body->read(1024);
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line   = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);
                        if ($line === '') {
                            continue;
                        }

                        $chunk = json_decode($line, true);
                        $piece = $chunk['message']['content'] ?? '';
                        if ($piece !== '') {
                            echo $piece;
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                        }
                        if (!empty($chunk['done'])) {
                            return;
                        }
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                echo 'AI Assistant sedang sibuk/lambat merespons. Coba lagi sesaat lagi.';
                flush();
            }
        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'X-Accel-Buffering' => 'no',
            'Cache-Control'     => 'no-cache',
        ]);
    }

    /**
     * llama3.2:1b kadang tidak mengisi field "tool_calls" resmi dari Ollama
     * sama sekali dan malah nge-echo balik SKEMA tool (bukan pemanggilan
     * sungguhan) sebagai teks biasa di message.content — persis kayak JSON
     * yang kita kirim di toolDefinitions(), lengkap dengan "parameters"/
     * "properties"/"required" tapi TANPA argumen yang benar-benar diisi.
     * Dibedakan dari tool call sungguhan (yang punya "arguments" terisi)
     * lewat cek ini, supaya JSON mentah itu tidak pernah nyasar ke user.
     */
    private function extractLeakedToolCall(string $content): ?array
    {
        $trimmed = trim($content);
        if ($trimmed === '' || $trimmed[0] !== '{') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return null;
        }

        $fn   = $decoded['function'] ?? $decoded;
        $name = $fn['name'] ?? null;
        $args = $fn['arguments'] ?? null;

        if (!$name || !is_array($args)) {
            return null;
        }

        return ['name' => $name, 'arguments' => $args];
    }

    private function looksLikeRawJson(string $content): bool
    {
        $trimmed = trim($content);
        return $trimmed !== '' && $trimmed[0] === '{' && json_decode($trimmed) !== null;
    }

    /**
     * Jalur tool-calling (non-streaming) — sama seperti sebelumnya, ditambah
     * keep_alive supaya konsisten dengan jalur streaming (lihat chatStreaming()).
     */
    private function chatWithTools(array $messages, array $tools)
    {
        try {
            // Http::post melempar ConnectionException (bukan response gagal biasa)
            // kalau timeout/koneksi putus, jadi WAJIB ditangkap di sini — kalau tidak,
            // request berakhir sebagai 500 mentah (bukan JSON) dan bikin frontend
            // nampilin "Gagal terhubung ke AI Assistant" tanpa alasan yang jelas.
            $response = Http::timeout(110)->post('http://127.0.0.1:11434/api/chat', [
                'model'      => self::MODEL,
                'messages'   => $messages,
                'tools'      => $tools,
                'stream'     => false,
                'keep_alive' => self::KEEP_ALIVE,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            report($e);
            return response()->json(['error' => 'AI Assistant sedang sibuk/lambat merespons. Coba lagi sesaat lagi.'], 504);
        }

        if (!$response->successful()) {
            return response()->json(['error' => 'AI Assistant sedang tidak tersedia.'], 502);
        }

        $toolCalls = $response->json('message.tool_calls', []);
        $content   = $response->json('message.content', '');

        if (empty($toolCalls)) {
            $leaked = $this->extractLeakedToolCall($content);
            if ($leaked) {
                $toolCalls = [['function' => $leaked]];
            } elseif ($this->looksLikeRawJson($content)) {
                $content = 'Maaf, saya kurang paham maksudnya. Bisa tolong diulang dengan kalimat lain?';
            }
        }

        if (!empty($toolCalls)) {
            $call = $toolCalls[0]['function'] ?? null;
            $args = $call['arguments'] ?? [];

            // Model (llama3.2:3b) kadang halu manggil tool tanpa alasan jelas (mis.
            // buat sapaan biasa) dan ngasih argumen kosong — jangan tampilkan usulan
            // aksi kalau argumen wajibnya tidak ada, biar tidak nyasar ke user.
            if ($call && $call['name'] === 'create_project') {
                $name = trim($args['name'] ?? '') ?: null;
                if ($name) {
                    return response()->json([
                        'reply'  => '',
                        'action' => [
                            'tool'  => 'create_project',
                            'label' => 'Buat proyek baru',
                            'args'  => [
                                'name'        => $name,
                                'description' => $args['description'] ?? '',
                            ],
                        ],
                    ]);
                }
            }

            if ($call && $call['name'] === 'create_task') {
                $title       = trim($args['title'] ?? '') ?: null;
                $projectName = trim($args['project_name'] ?? '') ?: null;
                if ($title && $projectName) {
                    return response()->json([
                        'reply'  => '',
                        'action' => [
                            'tool'  => 'create_task',
                            'label' => "Buat task \"{$title}\" di proyek \"{$projectName}\"",
                            'args'  => [
                                'project_name' => $projectName,
                                'title'        => $title,
                                'description'  => $args['description'] ?? '',
                            ],
                        ],
                    ]);
                }
            }
        }

        // Model manggil tool tapi argumen wajibnya kosong (mis. "buatkan proyek dong"
        // tanpa nama) → tool call dibuang di atas dan content-nya kosong, yang di
        // frontend jadi "(no response)". Ulangi sekali TANPA tools supaya model
        // menjawab biasa (biasanya nanya balik detail yang kurang).
        if (trim($content) === '') {
            try {
                $content = Http::timeout(110)->post('http://127.0.0.1:11434/api/chat', [
                    'model'      => self::MODEL,
                    'messages'   => $messages,
                    'stream'     => false,
                    'keep_alive' => self::KEEP_ALIVE,
                ])->json('message.content', '');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                report($e);
            }
        }

        return response()->json([
            'reply' => trim($content) !== '' && !$this->looksLikeRawJson($content)
                ? $content
                : 'Bisa tolong sebutkan detailnya? Misalnya nama proyek, atau judul task dan nama proyeknya.',
        ]);
    }

    /**
     * Eksekusi aksi yang sudah dikonfirmasi user. Validasi & permission dicek ulang
     * dari nol di sini — tidak mempercayai apa pun yang dikirim balik dari chat().
     */
    public function executeAction(Request $request)
    {
        $request->validate([
            'tool' => 'required|string|in:create_project,create_task,update_task',
            'args' => 'required|array',
        ]);

        return match ($request->tool) {
            'create_project' => $this->executeCreateProject($request),
            'create_task'    => $this->executeCreateTask($request),
            'update_task'    => $this->executeUpdateTask($request),
        };
    }

    private function executeCreateProject(Request $request)
    {
        abort_unless(auth()->user()->can('create project'), 403);

        try {
            $validated = validator($request->args, [
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Nama proyek tidak valid: ' . $e->getMessage()], 422);
        }

        $limits = PlanLimits::current();
        if (! $limits->canAddProject()) {
            return response()->json(['error' => $limits->limitMessage('project')], 403);
        }

        $project = Project::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => 'draft',
        ]);

        // Pembuat otomatis masuk tim (sama seperti buat proyek biasa) supaya tetap punya akses.
        \App\Models\ProjectMember::firstOrCreate(['project_id' => $project->id, 'user_id' => auth()->id()]);

        $this->notifier->notifyProjectTeam(
            $project,
            'new_project',
            'Proyek Baru Dibuat',
            "Proyek \"{$project->name}\" baru saja dibuat oleh " . auth()->user()->name . " (lewat AI Assistant).",
            ['project_id' => $project->id],
        );

        return response()->json([
            'message' => "Proyek \"{$project->name}\" berhasil dibuat.",
            'project_id' => $project->id,
            'url'     => route('projects.show', $project),
        ]);
    }

    private function executeCreateTask(Request $request)
    {
        try {
            $validated = validator($request->args, [
                'project_name' => 'required|string|max:255',
                'title'        => 'required|string|max:255',
                'description'  => 'nullable|string|max:2000',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Data task tidak valid: ' . $e->getMessage()], 422);
        }

        // Proyek yang boleh ditembak lewat nama cuma yang memang bisa dilihat user
        // ini (lihat ProjectPolicy::view) — bukan seluruh proyek di company, biar
        // AI Assistant tidak jadi jalan pintas buat nambah task ke proyek orang lain.
        $user     = auth()->user();
        $projects = $this->visibleProjectsQuery()->where('name', 'like', '%' . $validated['project_name'] . '%')->get();

        if ($projects->isEmpty()) {
            return response()->json(['error' => "Proyek \"{$validated['project_name']}\" tidak ditemukan atau Anda tidak punya akses ke proyek itu."], 422);
        }

        if ($projects->count() > 1) {
            $names = $projects->pluck('name')->join('", "');
            return response()->json(['error' => "Ada beberapa proyek yang cocok dengan \"{$validated['project_name']}\": \"{$names}\". Sebutkan nama proyeknya lebih spesifik."], 422);
        }

        $project = $projects->first();
        abort_unless($user->can('view', $project), 403);

        $todoColumn = BoardColumn::where('project_id', $project->id)
            ->where('is_done', false)
            ->orderBy('sort_order')
            ->first();

        $task = $project->tasks()->create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status'      => $todoColumn->slug ?? 'todo',
            'board_column_id' => $todoColumn->id ?? null,
            'created_by'  => $user->id,
        ]);

        if ($project->manager_id && $project->manager_id !== $user->id) {
            $this->notifier->send(
                $project->manager_id,
                'new_task',
                'Task Baru di Proyek',
                $user->name . " menambahkan task \"{$task->title}\" di proyek \"{$project->name}\" (lewat AI Assistant).",
                ['task_id' => $task->id, 'project_id' => $project->id]
            );
        }

        return response()->json([
            'message' => "Task \"{$task->title}\" berhasil ditambahkan ke proyek \"{$project->name}\".",
            'project_id' => $project->id,
            'task_id'    => $task->id,
            'url'     => route('tasks.show', [$project, $task]),
        ]);
    }
}
