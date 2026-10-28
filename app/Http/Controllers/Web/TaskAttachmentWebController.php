<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Support\PlanLimits;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskAttachmentWebController extends Controller
{
    // Upload file besar dikirim per potongan (lihat uploadAttachmentFile di _task_modal) supaya
    // tiap request kecil: tidak kena max_input_time/post_max_size & bisa diulang kalau koneksi putus.
    public const CHUNK_SIZE    = 2 * 1024 * 1024;   // samakan dengan CHUNK_SIZE di JS
    public const MAX_FILE_SIZE = 200 * 1024 * 1024; // samakan dengan MAX_FILE_SIZE di JS

    /** Folder sementara potongan (disk local), dibersihkan oleh attachments:purge-chunks. */
    public const CHUNK_DIR = 'upload-chunks';

    public function store(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'type' => 'required|in:file,link',
        ]);

        if ($request->type === 'file') {
            $request->validate([
                'file' => 'required|file|max:51200', // 50MB max
            ]);

            $file = $request->file('file');
            $path = $file->store("task-attachments/{$task->id}", 'public');

            $attachment = $task->attachments()->create([
                'type'       => 'file',
                'file_name'  => $file->getClientOriginalName(),
                'file_path'  => $path,
                'mime_type'  => $file->getMimeType(),
                'file_size'  => $file->getSize(),
                'created_by' => auth()->id(),
            ]);
        } else {
            $request->validate([
                'url'          => 'required|url|max:2000',
                'display_name' => 'nullable|string|max:500',
            ]);

            $attachment = $task->attachments()->create([
                'type'       => 'link',
                'file_name'  => $request->display_name ?: $request->url,
                'url'        => $request->url,
                'created_by' => auth()->id(),
            ]);
        }

        return $this->attachmentCreated($task, $attachment);
    }

    /** Terima satu potongan file. Idempoten per index, jadi aman diulang saat koneksi putus. */
    public function storeChunk(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $data = $request->validate([
            'upload_id' => 'required|uuid',
            'total'     => 'required|integer|min:1|max:' . (int) ceil(self::MAX_FILE_SIZE / self::CHUNK_SIZE),
            'index'     => 'required|integer|min:0|lt:total',
            'file_size' => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
            'chunk'     => 'required|file|max:' . (self::CHUNK_SIZE / 1024),
        ], [
            'file_size.max' => 'Ukuran file maksimal ' . PlanLimits::humanBytes(self::MAX_FILE_SIZE) . '.',
        ]);

        // Middleware kuota hanya melihat ukuran potongan, jadi cek ukuran file utuh di awal.
        if ((int) $data['index'] === 0 && ($quota = $this->quotaExceeded($request, (int) $data['file_size']))) {
            return $quota;
        }

        $request->file('chunk')->storeAs($this->chunkDir($data['upload_id']), (string) (int) $data['index'], 'local');

        return response()->json(['ok' => true]);
    }

    /** Index potongan yang sudah diterima, untuk melanjutkan upload setelah halaman di-refresh. */
    public function chunkStatus(Project $project, Task $task, string $uploadId)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || ! Str::isUuid($uploadId), 404);

        $uploaded = collect(Storage::disk('local')->files($this->chunkDir($uploadId)))
            ->map(fn ($path) => basename($path))
            ->filter(fn ($name) => ctype_digit($name))
            ->map(fn ($name) => (int) $name)
            ->values();

        return response()->json(['ok' => true, 'uploaded' => $uploaded]);
    }

    /** Gabungkan semua potongan jadi satu attachment. */
    public function completeChunks(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $data = $request->validate([
            'upload_id' => 'required|uuid',
            'total'     => 'required|integer|min:1|max:' . (int) ceil(self::MAX_FILE_SIZE / self::CHUNK_SIZE),
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
        ]);

        $disk = Storage::disk('local');
        $dir  = $this->chunkDir($data['upload_id']);

        for ($i = 0; $i < (int) $data['total']; $i++) {
            if (! $disk->exists("{$dir}/{$i}")) {
                return response()->json(['ok' => false, 'message' => 'Sebagian file belum terkirim, silakan upload ulang.'], 422);
            }
        }

        if ($quota = $this->quotaExceeded($request, (int) $data['file_size'])) {
            $disk->deleteDirectory($dir);
            return $quota;
        }

        $assembled = $disk->path("{$dir}/assembled");
        $out = fopen($assembled, 'wb');
        for ($i = 0; $i < (int) $data['total']; $i++) {
            $in = fopen($disk->path("{$dir}/{$i}"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        if (filesize($assembled) !== (int) $data['file_size']) {
            $disk->deleteDirectory($dir);
            return response()->json(['ok' => false, 'message' => 'File rusak saat dikirim, silakan upload ulang.'], 422);
        }

        $file = new File($assembled);
        $ext  = preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($data['file_name'], PATHINFO_EXTENSION) ?: (string) $file->guessExtension()));
        // Jangan sampai file yang bisa dieksekusi PHP tersaji dari folder publik.
        if ($ext === '' || preg_match('/^(php\d*|phtml|pht|phps|phar)$/', $ext)) {
            $ext = 'bin';
        }

        $path = Storage::disk('public')->putFileAs("task-attachments/{$task->id}", $file, Str::random(40) . '.' . $ext);
        $mime = $file->getMimeType();
        $disk->deleteDirectory($dir);

        $attachment = $task->attachments()->create([
            'type'       => 'file',
            'file_name'  => $data['file_name'],
            'file_path'  => $path,
            'mime_type'  => $mime,
            'file_size'  => (int) $data['file_size'],
            'created_by' => auth()->id(),
        ]);

        return $this->attachmentCreated($task, $attachment);
    }

    private function chunkDir(string $uploadId): string
    {
        // Per user, jadi upload_id milik orang lain tidak bisa ditimpa/digabung.
        return self::CHUNK_DIR . '/' . auth()->id() . '/' . strtolower($uploadId);
    }

    private function quotaExceeded(Request $request, int $bytes)
    {
        $limits = PlanLimits::forUser($request->user());
        if ($limits->canStore($bytes)) {
            return null;
        }

        $notice = $limits->limitNotice('storage');

        return response()->json([
            'ok'          => false,
            'message'     => $notice['message'],
            'error'       => $notice['message'],
            'upgrade_url' => $notice['url'],
            'plan_limit'  => $notice,
        ], 403);
    }

    private function attachmentCreated(Task $task, TaskAttachment $attachment)
    {
        if (function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties([
                    'file_name' => $attachment->file_name,
                    'type'      => $attachment->type,
                ])
                ->log('attachment_added');
        }

        return response()->json([
            'ok'         => true,
            'attachment' => [
                'id'        => $attachment->id,
                'type'      => $attachment->type,
                'file_name' => $attachment->file_name,
                'url'       => $attachment->publicUrl(),
                'is_image'  => $attachment->isImage(),
                'size'      => $attachment->humanSize(),
                'extension' => $attachment->extension(),
                'created_at'=> $attachment->created_at->diffForHumans(),
            ],
        ], 201);
    }

    public function destroy(Project $project, Task $task, TaskAttachment $attachment)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $attachment->task_id !== $task->id, 404);

        $fileName = $attachment->file_name;
        if ($attachment->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        if (function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties(['file_name' => $fileName])
                ->log('attachment_deleted');
        }

        return response()->json(['ok' => true]);
    }

    public function updateCover(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        if ($request->hasFile('cover')) {
            $request->validate(['cover' => 'required|image|max:5120']); // 5MB

            // Delete old cover
            if ($task->cover_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($task->cover_image_path);
            }

            $path = $request->file('cover')->store("task-covers/{$task->id}", 'public');
            $task->update(['cover_image_path' => $path]);

            if (function_exists('activity')) {
                activity('task')
                    ->performedOn($task)
                    ->causedBy(auth()->user())
                    ->withProperties(['cover_path' => $path])
                    ->log('cover_updated');
            }

            return response()->json([
                'ok'  => true,
                'url' => \Illuminate\Support\Facades\Storage::url($path),
            ]);
        }

        // Remove cover
        if ($task->cover_image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($task->cover_image_path);
        }
        $task->update(['cover_image_path' => null]);

        if (function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->log('cover_removed');
        }

        return response()->json(['ok' => true, 'url' => null]);
    }
}
