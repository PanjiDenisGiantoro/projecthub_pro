<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AiAssistantWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    /**
     * Daftar aksi yang boleh diusulkan AI. Setiap aksi TIDAK langsung dieksekusi
     * di sini — cuma diusulkan ke user, dieksekusi setelah user klik konfirmasi
     * lewat executeAction() (yang mengulangi validasi & permission check sendiri,
     * tidak percaya begitu saja pada apa yang "diusulkan" AI).
     */
    /**
     * llama3.2:3b (model kecil, self-hosted) cenderung manggil tool yang
     * ditawarkan hampir di setiap pesan kalau tersedia — instruksi di system
     * prompt saja tidak cukup buat nahan itu. Makanya tool "create_project"
     * cuma ditawarkan ke model kalau pesan terakhir user memang kelihatan
     * minta dibuatkan proyek, bukan diputuskan oleh model sendiri.
     */
    private function looksLikeProjectRequest(array $messages): bool
    {
        $lastUser = collect($messages)->last(fn ($m) => ($m['role'] ?? null) === 'user');
        if (!$lastUser) {
            return false;
        }

        $text     = strtolower($lastUser['content'] ?? '');
        $keywords = [
            'buat proyek', 'bikin proyek', 'proyek baru', 'buatkan proyek',
            'membuat proyek', 'create a project', 'create project', 'new project',
            'make a project',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function toolDefinitions(): array
    {
        return [
            [
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

        // Cuma tawarkan tool ke model kalau user memang punya izin buat aksinya —
        // supaya AI tidak mengusulkan sesuatu yang bakal ditolak pas konfirmasi.
        $tools = [];
        if (auth()->user()->can('create project') && $this->looksLikeProjectRequest($request->messages)) {
            $tools = $this->toolDefinitions();
        }

        try {
            // Ollama jalan di CPU (tanpa GPU) — cold start (model belum ke-load
            // di memory) bisa makan >90 detik, makanya timeout dilonggarkan.
            // Http::post melempar ConnectionException (bukan response gagal biasa)
            // kalau timeout/koneksi putus, jadi WAJIB ditangkap di sini — kalau tidak,
            // request berakhir sebagai 500 mentah (bukan JSON) dan bikin frontend
            // nampilin "Gagal terhubung ke AI Assistant" tanpa alasan yang jelas.
            $response = Http::timeout(110)->post('http://127.0.0.1:11434/api/chat', [
                'model'    => 'llama3.2:3b',
                'messages' => $messages,
                'tools'    => $tools,
                'stream'   => false,
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
            $name = trim($call['arguments']['name'] ?? '') ?: null;
            // Model (llama3.2:3b) kadang halu manggil tool tanpa alasan jelas (mis.
            // buat sapaan biasa) dan ngasih nama proyek kosong — jangan tampilkan
            // usulan aksi kalau nama proyeknya tidak ada, biar tidak nyasar ke user.
            if ($call && $call['name'] === 'create_project' && $name) {
                $args = $call['arguments'] ?? [];
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
            'tool' => 'required|string|in:create_project',
            'args' => 'required|array',
        ]);

        return match ($request->tool) {
            'create_project' => $this->executeCreateProject($request),
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
}
