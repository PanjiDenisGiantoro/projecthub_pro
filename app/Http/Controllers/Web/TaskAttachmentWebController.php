<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;

class TaskAttachmentWebController extends Controller
{
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
