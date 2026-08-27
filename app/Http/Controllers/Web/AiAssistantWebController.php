<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AiAssistantWebController extends Controller
{
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

    public function chat(Request $request)
    {
        $request->validate([
            'messages'            => 'required|array|min:1|max:20',
            'messages.*.role'     => 'required|in:user,assistant',
            'messages.*.content'  => 'required|string|max:4000',
        ]);

        $messages = [
            ['role' => 'system', 'content' => config('ai_assistant.system_prompt')],
            ...$request->messages,
        ];

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
                        'model'      => 'llama3.2:3b',
                        'messages'   => $messages,
                        'stream'     => true,
                        'keep_alive' => '30m',
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
                'model'      => 'llama3.2:3b',
                'messages'   => $messages,
                'tools'      => $tools,
                'stream'     => false,
                'keep_alive' => '30m',
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            report($e);
            return response()->json(['error' => 'AI Assistant sedang sibuk/lambat merespons. Coba lagi sesaat lagi.'], 504);
        }

        if (!$response->successful()) {
            return response()->json(['error' => 'AI Assistant sedang tidak tersedia.'], 502);
        }

        $toolCalls = $response->json('message.tool_calls', []);

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

        return response()->json([
            'reply' => $response->json('message.content', ''),
        ]);
    }

    /**
     * Eksekusi aksi yang sudah dikonfirmasi user. Validasi & permission dicek ulang
     * dari nol di sini — tidak mempercayai apa pun yang dikirim balik dari chat().
     */
    public function executeAction(Request $request)
    {
        $request->validate([
            'tool' => 'required|string|in:create_project,create_task',
            'args' => 'required|array',
        ]);

        return match ($request->tool) {
            'create_project' => $this->executeCreateProject($request),
            'create_task'    => $this->executeCreateTask($request),
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

        $project = Project::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => 'draft',
        ]);

        $this->notifier->notifyManagers(
            'new_project',
            'Proyek Baru Dibuat',
            "Proyek \"{$project->name}\" baru saja dibuat oleh " . auth()->user()->name . " (lewat AI Assistant).",
            ['project_id' => $project->id],
            push: true,
            companyId: $project->company_id
        );

        return response()->json([
            'message' => "Proyek \"{$project->name}\" berhasil dibuat.",
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
        $user  = auth()->user();
        $query = Project::query();
        if (!$user->hasRole(['admin', 'member'])) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('client_id', $user->id)
                    ->orWhereHas('members', fn ($q2) => $q2->where('user_id', $user->id));
            });
        }

        $projects = $query->where('name', 'like', '%' . $validated['project_name'] . '%')->get();

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
            'url'     => route('tasks.show', [$project, $task]),
        ]);
    }
}
