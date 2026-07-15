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
        if (auth()->user()->can('create project')) {
            $tools = $this->toolDefinitions();
        }

        $response = Http::timeout(90)->post('http://127.0.0.1:11434/api/chat', [
            'model'    => 'llama3.2:3b',
            'messages' => $messages,
            'tools'    => $tools,
            'stream'   => false,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'AI Assistant sedang tidak tersedia.'], 502);
        }

        $toolCalls = $response->json('message.tool_calls', []);

        if (!empty($toolCalls)) {
            $call = $toolCalls[0]['function'] ?? null;
            if ($call && $call['name'] === 'create_project') {
                $args = $call['arguments'] ?? [];
                return response()->json([
                    'reply'  => '',
                    'action' => [
                        'tool'  => 'create_project',
                        'label' => 'Buat proyek baru',
                        'args'  => [
                            'name'        => $args['name'] ?? '',
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
