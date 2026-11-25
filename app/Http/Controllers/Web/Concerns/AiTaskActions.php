<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Aksi ubah task lewat Runa (tandai selesai, pindah kolom, deadline, PIC,
 * prioritas, komentar).
 *
 * Kalimat perintah di-parse DETERMINISTIK pakai regex, bukan lewat tool-calling
 * model — llama3.2:1b terlalu sering salah isi argumen. Alurnya sama dengan
 * create_project/create_task: chat() cuma MENGUSULKAN aksi (kartu konfirmasi),
 * baru dieksekusi lewat executeAction() setelah user klik Confirm, dan di sana
 * akses + validasi dicek ulang (args dari client tidak dipercaya).
 *
 * Akses = sama dengan halaman web: siapa pun yang boleh view proyek (lihat
 * route group can:view,project → tasks.update / tasks.move / tasks.comment).
 */
trait AiTaskActions
{
    private const TASK_FIELDS = ['done', 'status', 'due_date', 'assignee', 'priority', 'comment'];

    private const PRIORITY_MAP = [
        'rendah' => 'low', 'low' => 'low',
        'sedang' => 'medium', 'medium' => 'medium', 'normal' => 'medium',
        'tinggi' => 'high', 'high' => 'high', 'penting' => 'high',
        'urgent' => 'urgent', 'mendesak' => 'urgent', 'darurat' => 'urgent', 'critical' => 'urgent', 'kritis' => 'urgent',
    ];

    private const PRIORITY_LABEL = ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'urgent' => 'Urgent'];

    /**
     * Kenali perintah ubah task di pesan terakhir user.
     * null = bukan perintah ubah task (lanjut ke jalur chat biasa);
     * selain itu array respons JSON: ['action' => ...] atau ['reply' => '...'].
     */
    private function proposeTaskAction(array $messages): ?array
    {
        $lastUser = collect($messages)->last(fn ($m) => ($m['role'] ?? null) === 'user');
        $text     = trim(preg_replace('/\s+/u', ' ', $lastUser['content'] ?? ''));
        $text     = rtrim($text, " .!?");
        if ($text === '') {
            return null;
        }

        $T      = '(?:task|tugas|card|kartu)';
        $to     = '(?:jadi|menjadi|ke|=)';
        $please = '^(?:tolong|mohon|coba|bisa|runa)?\s*,?\s*';

        $patterns = [
            // tandai task X selesai / task X sudah selesai / selesaikan task X
            ['done', "/{$please}(?:tandai|tandakan|set|centang)\\s+{$T}\\s+(?<t>.+?)\\s+(?:sebagai\\s+|jadi\\s+|menjadi\\s+)?(?:selesai|done|beres|complete[d]?)$/iu"],
            ['done', "/{$please}(?:selesaikan|complete|close|tutup)\\s+{$T}\\s+(?<t>.+)$/iu"],
            ['done', "/{$please}{$T}\\s+(?<t>.+?)\\s+(?:sudah|udah|telah|dah)\\s+(?:selesai|beres|done|kelar)$/iu"],
            // pindahkan task X ke <kolom> / ubah status task X jadi <kolom>
            ['status', "/{$please}(?:pindah\\w*|geser\\w*|move)\\s+{$T}\\s+(?<t>.+?)\\s+ke\\s+(?:kolom\\s+|bucket\\s+|status\\s+|list\\s+)?(?<v>.+)$/iu"],
            ['status', "/{$please}(?:ubah|ganti|set|update)\\s+status\\s+{$T}\\s+(?<t>.+?)\\s+{$to}\\s+(?<v>.+)$/iu"],
            // ubah deadline task X jadi <tanggal>
            ['due_date', "/{$please}(?:ubah|ganti|set|atur|update|undur\\w*|majukan|geser)\\s+(?:deadline|tenggat|due\\s*date|jatuh\\s+tempo|tanggal\\s+selesai)\\s+{$T}\\s+(?<t>.+?)\\s+{$to}\\s+(?:tanggal\\s+)?(?<v>.+)$/iu"],
            ['due_date', "/{$please}(?:set|atur)\\s+{$T}\\s+(?<t>.+?)\\s+deadline\\s+(?:nya\\s+)?(?<v>.+)$/iu"],
            // assign task X ke Budi / ganti PIC task X jadi Budi
            ['assignee', "/{$please}(?:assign|tugaskan|serahkan|berikan|kasih|limpahkan)\\s+{$T}\\s+(?<t>.+?)\\s+(?:ke|kepada|untuk|buat)\\s+(?<v>.+)$/iu"],
            ['assignee', "/{$please}(?:ubah|ganti|set|update)\\s+(?:pic|assignee|penanggung\\s*jawab|pj)\\s+{$T}\\s+(?<t>.+?)\\s+{$to}\\s+(?<v>.+)$/iu"],
            // ubah prioritas task X jadi tinggi
            ['priority', "/{$please}(?:ubah|ganti|set|update|naikkan|turunkan)\\s+(?:prioritas|priority)\\s+{$T}\\s+(?<t>.+?)\\s+{$to}\\s+(?<v>.+)$/iu"],
            // komentar di task X: isi komentar
            ['comment', "/{$please}(?:tambah\\w*\\s+|tulis\\w*\\s+|kasih\\s+|beri\\w*\\s+)?(?:komentar|comment|catatan)\\s+(?:di|ke|pada|untuk)\\s+{$T}\\s+(?<t>.+?)\\s*:\\s*(?<v>.+)$/isu"],
        ];

        foreach ($patterns as [$field, $re]) {
            if (preg_match($re, $text, $m)) {
                return $this->buildTaskProposal($field, $m['t'], $m['v'] ?? null);
            }
        }

        return null;
    }

    private function buildTaskProposal(string $field, string $rawTitle, ?string $rawValue): array
    {
        // "Desain login di proyek Kasir" → judul + petunjuk proyek
        $projectHint = null;
        if (preg_match('/^(?<t>.+?)\s+(?:di|pada|dari)\s+(?:proyek|projek|project)\s+(?<p>.+)$/iu', $rawTitle, $pm)) {
            $rawTitle    = $pm['t'];
            $projectHint = trim($pm['p'], " \"'“”");
        }
        $title = trim($rawTitle, " \"'“”");
        $value = $rawValue !== null ? trim($rawValue, " \"'“”") : null;

        $found = $this->findTaskForAction($title, $projectHint);
        if (is_string($found)) {
            return ['reply' => $found];
        }
        /** @var Task $task */
        $task    = $found;
        $project = $task->project;
        $where   = "task \"{$task->title}\" (proyek {$project->name})";

        switch ($field) {
            case 'done':
                if ($task->completed_at || $task->boardColumn?->is_done) {
                    return ['reply' => ucfirst($where) . ' sudah berstatus selesai.'];
                }
                return $this->taskActionResponse($task, 'done', true, "Tandai {$where} selesai", [
                    'Status sekarang' => $task->status,
                ]);

            case 'status':
                $column = $this->findColumn($project, $value);
                if (!$column) {
                    $names = BoardColumn::where('project_id', $project->id)->orderBy('sort_order')->pluck('name')->join(', ');
                    return ['reply' => "Kolom \"{$value}\" tidak ada di proyek {$project->name}." . ($names ? " Kolom yang tersedia: {$names}." : '')];
                }
                if ($task->board_column_id === $column->id) {
                    return ['reply' => ucfirst($where) . " sudah ada di kolom {$column->name}."];
                }
                return $this->taskActionResponse($task, 'status', $column->id, "Pindahkan {$where} ke kolom \"{$column->name}\"", [
                    'Dari' => $task->boardColumn->name ?? $task->status,
                    'Ke'   => $column->name,
                ]);

            case 'due_date':
                $date = $this->parseIndoDate($value);
                if (!$date) {
                    return ['reply' => "Saya tidak paham tanggal \"{$value}\". Coba tulis seperti \"30 Nov 2026\", \"2026-11-30\", \"besok\", atau \"minggu depan\"."];
                }
                return $this->taskActionResponse($task, 'due_date', $date->toDateString(), "Ubah deadline {$where}", [
                    'Deadline lama' => $task->due_date ? $task->due_date->translatedFormat('d M Y') : '-',
                    'Deadline baru' => self::indoDay($date) . ', ' . $date->translatedFormat('d M Y'),
                ]);

            case 'assignee':
                $user = $this->findCompanyUser($value, $project);
                if (is_string($user)) {
                    return ['reply' => $user];
                }
                if ($task->assigned_to === $user->id) {
                    return ['reply' => ucfirst($where) . " sudah di-assign ke {$user->name}."];
                }
                return $this->taskActionResponse($task, 'assignee', $user->id, "Assign {$where} ke {$user->name}", [
                    'PIC lama' => $task->assignee->name ?? '-',
                    'PIC baru' => $user->name,
                ]);

            case 'priority':
                $priority = self::PRIORITY_MAP[mb_strtolower($value)] ?? null;
                if (!$priority) {
                    return ['reply' => "Prioritas \"{$value}\" tidak dikenal. Pilihan: rendah, sedang, tinggi, urgent."];
                }
                return $this->taskActionResponse($task, 'priority', $priority, "Ubah prioritas {$where}", [
                    'Prioritas lama' => self::PRIORITY_LABEL[$task->priority] ?? ($task->priority ?: '-'),
                    'Prioritas baru' => self::PRIORITY_LABEL[$priority],
                ]);

            case 'comment':
                if ($value === null || $value === '' || mb_strlen($value) > 2000) {
                    return ['reply' => 'Isi komentarnya kosong atau terlalu panjang (maks 2000 karakter).'];
                }
                return $this->taskActionResponse($task, 'comment', $value, "Tambah komentar di {$where}", [
                    'Komentar' => $value,
                ]);
        }

        return ['reply' => 'Perintah tidak dikenali.'];
    }

    private function taskActionResponse(Task $task, string $field, mixed $value, string $label, array $details): array
    {
        return [
            'reply'  => '',
            'action' => [
                'tool'    => 'update_task',
                'label'   => $label,
                'args'    => ['task_id' => $task->id, 'field' => $field, 'value' => $value],
                'details' => collect($details)->map(fn ($v, $k) => ['label' => $k, 'value' => (string) $v])->values()->all(),
            ],
        ];
    }

    /** Task di proyek yang bisa diakses user, dicari dari judul. Task|string (pesan error/klarifikasi). */
    private function findTaskForAction(string $title, ?string $projectHint): Task|string
    {
        if (mb_strlen($title) < 2) {
            return 'Sebutkan judul task-nya, misalnya: tandai task "Desain login" selesai.';
        }

        $projects = $this->visibleProjectsQuery();
        if ($projectHint) {
            $projects->where('name', 'like', '%' . $projectHint . '%');
        }
        $projectIds = $projects->pluck('projects.id');
        if ($projectIds->isEmpty()) {
            return $projectHint
                ? "Proyek \"{$projectHint}\" tidak ditemukan atau Anda tidak punya akses ke proyek itu."
                : 'Anda belum punya akses ke proyek mana pun.';
        }

        $base  = Task::with(['project:id,name,company_id', 'boardColumn:id,name,is_done', 'assignee:id,name'])->whereIn('project_id', $projectIds);
        $exact = (clone $base)->where('title', $title)->get();
        $tasks = $exact->isNotEmpty() ? $exact : (clone $base)->where('title', 'like', '%' . $title . '%')->limit(6)->get();

        if ($tasks->isEmpty()) {
            return "Task \"{$title}\" tidak ditemukan" . ($projectHint ? " di proyek \"{$projectHint}\"" : ' di proyek yang bisa Anda akses') . '.';
        }

        // Kalau ada beberapa yang cocok, utamakan yang belum selesai & milik user — tetap minta pilih kalau masih >1.
        if ($tasks->count() > 1) {
            $mine = $tasks->filter(fn ($t) => !$t->completed_at && !$t->boardColumn?->is_done && $t->assigned_to === auth()->id());
            if ($mine->count() === 1) {
                return $mine->first();
            }
            $list = $tasks->take(5)->map(fn ($t) => "• {$t->title} (proyek {$t->project->name})")->join("\n");

            return "Ada beberapa task yang cocok dengan \"{$title}\":\n{$list}\n\nTulis judul lengkapnya, atau tambahkan \"di proyek <nama proyek>\".";
        }

        return $tasks->first();
    }

    private function findColumn(Project $project, ?string $name): ?BoardColumn
    {
        $name = mb_strtolower(trim((string) $name));
        if ($name === '') {
            return null;
        }
        $columns = BoardColumn::where('project_id', $project->id)->orderBy('sort_order')->get();

        $aliases = [
            'selesai' => 'done', 'beres' => 'done', 'done' => 'done', 'complete' => 'done', 'completed' => 'done',
            'dikerjakan' => 'in_progress', 'sedang dikerjakan' => 'in_progress', 'progress' => 'in_progress', 'in progress' => 'in_progress', 'proses' => 'in_progress',
            'todo' => 'todo', 'to do' => 'todo', 'belum' => 'todo', 'belum dikerjakan' => 'todo',
            'review' => 'review', 'direview' => 'review',
        ];

        return $columns->first(fn ($c) => mb_strtolower($c->name) === $name || mb_strtolower((string) $c->slug) === $name)
            ?? $columns->first(fn ($c) => str_contains(mb_strtolower($c->name), $name))
            ?? (($alias = $aliases[$name] ?? null) === 'done'
                ? $columns->firstWhere('is_done', true)
                : ($alias ? $columns->first(fn ($c) => in_array(str_replace('-', '_', (string) $c->slug), [$alias, str_replace('_', '', $alias)])) : null));
    }

    /** User aktif satu company (bukan client) yang cocok dengan nama. User|string. */
    private function findCompanyUser(?string $name, Project $project): User|string
    {
        $name = trim(preg_replace('/^(saya|aku|diri\s*saya|me)$/iu', auth()->user()->name, (string) $name));
        if (mb_strlen($name) < 2) {
            return 'Sebutkan nama orang yang akan di-assign.';
        }

        $users = User::where('company_id', $project->company_id)->where('is_active', true)
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'client'))
            ->where('name', 'like', '%' . $name . '%')->orderBy('name')->limit(6)->get(['id', 'name']);
        $exact = $users->filter(fn ($u) => mb_strtolower($u->name) === mb_strtolower($name));

        if ($exact->count() === 1) {
            return $exact->first();
        }
        if ($users->isEmpty()) {
            return "Anggota tim bernama \"{$name}\" tidak ditemukan di perusahaan Anda.";
        }
        if ($users->count() > 1) {
            return "Ada beberapa anggota yang cocok dengan \"{$name}\": " . $users->pluck('name')->join(', ') . '. Tulis nama lengkapnya.';
        }

        return $users->first();
    }

    private static function indoDay(Carbon $date): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$date->dayOfWeek];
    }

    /** "besok", "lusa", "minggu depan", "30 nov", "30 november 2026", "30/11/2026", "2026-11-30". */
    private function parseIndoDate(?string $value): ?Carbon
    {
        $v = mb_strtolower(trim((string) $value));
        $v = preg_replace('/^(hari\s+|tgl\.?\s*|tanggal\s+)/u', '', $v);

        $relative = [
            'hari ini' => 0, 'ini' => 0, 'today' => 0, 'besok' => 1, 'tomorrow' => 1, 'lusa' => 2,
            'minggu depan' => 7, 'pekan depan' => 7, 'seminggu lagi' => 7, 'bulan depan' => 30,
        ];
        if (isset($relative[$v])) {
            return today()->addDays($relative[$v]);
        }
        if (preg_match('/^(\d+)\s+hari\s+lagi$/u', $v, $m)) {
            return today()->addDays((int) $m[1]);
        }
        if (preg_match('/^(senin|selasa|rabu|kamis|jumat|jum\'at|sabtu|minggu)(\s+depan)?$/u', $v, $m)) {
            $days = ['senin' => 'monday', 'selasa' => 'tuesday', 'rabu' => 'wednesday', 'kamis' => 'thursday', 'jumat' => 'friday', "jum'at" => 'friday', 'sabtu' => 'saturday', 'minggu' => 'sunday'];
            return Carbon::parse('next ' . $days[$m[1]])->startOfDay();
        }

        $months = [
            'januari' => 'january', 'jan' => 'jan', 'februari' => 'february', 'feb' => 'feb', 'maret' => 'march', 'mar' => 'mar',
            'april' => 'april', 'apr' => 'apr', 'mei' => 'may', 'juni' => 'june', 'jun' => 'jun', 'juli' => 'july', 'jul' => 'jul',
            'agustus' => 'august', 'agu' => 'aug', 'agt' => 'aug', 'ags' => 'aug', 'september' => 'september', 'sep' => 'sep', 'sept' => 'sep',
            'oktober' => 'october', 'okt' => 'oct', 'november' => 'november', 'nov' => 'nov', 'desember' => 'december', 'des' => 'dec',
        ];

        try {
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v)) {
                return Carbon::createFromFormat('Y-m-d', $v)->startOfDay();
            }
            if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?$/', $v, $m)) {
                $year = isset($m[3]) ? (strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3]) : today()->year;
                $date = Carbon::createSafe($year, (int) $m[2], (int) $m[1])->startOfDay();
                return !isset($m[3]) && $date->lt(today()) ? $date->addYear() : $date;
            }
            if (preg_match('/^(\d{1,2})\s+([a-z]+)\.?(?:\s+(\d{4}))?$/u', $v, $m) && isset($months[$m[2]])) {
                $date = Carbon::parse("{$m[1]} {$months[$m[2]]} " . ($m[3] ?? today()->year))->startOfDay();
                return !isset($m[3]) && $date->lt(today()) ? $date->addYear() : $date;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /** Eksekusi update_task setelah user konfirmasi — akses & nilai dicek ulang dari nol. */
    private function executeUpdateTask(Request $request)
    {
        $args  = $request->args;
        $field = $args['field'] ?? null;
        if (!in_array($field, self::TASK_FIELDS, true) || empty($args['task_id'])) {
            return response()->json(['error' => 'Data aksi tidak valid.'], 422);
        }

        $task = Task::with('project', 'boardColumn')->find((int) $args['task_id']);
        // Project punya global scope company → proyek company lain = null.
        $project = $task?->project;
        if (!$task || !$project || !$this->visibleProjectsQuery()->whereKey($project->id)->exists()) {
            return response()->json(['error' => 'Task tidak ditemukan atau Anda tidak punya akses ke proyeknya.'], 403);
        }
        abort_unless(auth()->user()->can('view', $project), 403);

        $user      = auth()->user();
        $value     = $args['value'] ?? null;
        $oldStatus = $task->status;
        $message   = '';

        switch ($field) {
            case 'done':
                $doneCol = BoardColumn::where('project_id', $project->id)->where('is_done', true)->orderBy('sort_order')->first();
                $task->update(array_filter([
                    'completed_at'    => $task->completed_at ?? now(),
                    'board_column_id' => $doneCol?->id,
                    'status'          => $doneCol->slug ?? 'done',
                ], fn ($v) => $v !== null));
                $message = "Task \"{$task->title}\" ditandai selesai.";
                break;

            case 'status':
                $column = BoardColumn::where('project_id', $project->id)->find((int) $value);
                if (!$column) {
                    return response()->json(['error' => 'Kolom tujuan tidak ada di proyek ini.'], 422);
                }
                // Sama dengan TaskWebController::moveStatus — completed_at tidak disentuh.
                $task->update(['board_column_id' => $column->id, 'status' => $column->slug]);
                $message = "Task \"{$task->title}\" dipindah ke kolom {$column->name}.";
                break;

            case 'due_date':
                try {
                    $date = Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay();
                } catch (\Throwable) {
                    return response()->json(['error' => 'Tanggal tidak valid.'], 422);
                }
                if ($task->start_date && $date->lt($task->start_date)) {
                    return response()->json(['error' => 'Deadline tidak boleh sebelum tanggal mulai task (' . $task->start_date->translatedFormat('d M Y') . ').'], 422);
                }
                $task->update(['due_date' => $date->toDateString()]);
                if ($task->google_event_id) {
                    try {
                        app(GoogleCalendarService::class)->syncMeetingTime($task, $user);
                    } catch (\Throwable) {
                        // sama dengan web: gagal sync kalender tidak blokir update task
                    }
                }
                $message = "Deadline task \"{$task->title}\" diubah ke " . $date->translatedFormat('d M Y') . '.';
                break;

            case 'assignee':
                $assignee = User::where('company_id', $project->company_id)->where('is_active', true)
                    ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'client'))->find((int) $value);
                if (!$assignee) {
                    return response()->json(['error' => 'User tujuan tidak ditemukan di perusahaan ini.'], 422);
                }
                $task->members()->syncWithoutDetaching([$assignee->id]);
                $task->update(['assigned_to' => $assignee->id]);
                if ($assignee->id !== $user->id) {
                    $this->notifier->send($assignee->id, 'task_assigned', 'Task Baru',
                        "Task \"{$task->title}\" ditugaskan ke Anda oleh {$user->name} (lewat AI Assistant).", ['task_id' => $task->id]);
                }
                $message = "Task \"{$task->title}\" di-assign ke {$assignee->name}.";
                break;

            case 'priority':
                if (!in_array($value, ['low', 'medium', 'high', 'urgent'], true)) {
                    return response()->json(['error' => 'Prioritas tidak valid.'], 422);
                }
                $task->update(['priority' => $value]);
                $message = "Prioritas task \"{$task->title}\" diubah ke " . self::PRIORITY_LABEL[$value] . '.';
                break;

            case 'comment':
                $body = trim((string) $value);
                if ($body === '' || mb_strlen($body) > 2000) {
                    return response()->json(['error' => 'Komentar kosong atau terlalu panjang.'], 422);
                }
                $task->comments()->create(['user_id' => $user->id, 'body' => $body]);
                collect([$task->assigned_to, $task->created_by])->filter()->unique()->reject(fn ($id) => $id === $user->id)
                    ->each(fn ($uid) => $this->notifier->send($uid, 'task_comment_added', 'Komentar Baru',
                        "{$user->name} berkomentar di task \"{$task->title}\".", ['task_id' => $task->id]));
                $message = "Komentar ditambahkan ke task \"{$task->title}\".";
                break;
        }

        // Notifikasi perubahan status — sama dengan TaskWebController::moveStatus.
        if ($oldStatus !== $task->status) {
            $notify = $task->created_by ?? $project->manager_id;
            if ($notify && $notify !== $user->id) {
                $this->notifier->send($notify, 'task_status_changed', 'Status Task Berubah',
                    "Task \"{$task->title}\" berubah dari {$oldStatus} ke {$task->status}.", ['task_id' => $task->id]);
            }
            if ($task->fresh('boardColumn')->isDone()) {
                try {
                    app(TeamNotifier::class)->notify($project, '✅ Task Selesai', "\"{$task->title}\" ditandai selesai oleh {$user->name}.");
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return response()->json([
            'message'    => $message,
            'project_id' => $project->id,
            'task_id'    => $task->id,
            'url'        => route('tasks.show', [$project, $task]),
        ]);
    }
}
