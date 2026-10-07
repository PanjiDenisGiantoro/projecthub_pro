<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFolder;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Support\FolderTreeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileWebController extends Controller
{
    /** URL lama File Manager penuh — sekarang tampil langsung di tab Files project. */
    public function index(Project $project)
    {
        return redirect()->route('projects.tab', [$project, 'files']);
    }

    /** Data File Manager (dipakai tab Files di ProjectWebController::show). */
    public static function managerData(Project $project): array
    {
        $standaloneFiles = $project->files()->with('uploader')->orderBy('folder')->orderByDesc('created_at')->get();

        $taskAttachments = TaskAttachment::whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->with(['task.boardColumn', 'creator'])
            ->orderByDesc('created_at')
            ->get();

        $folders = $standaloneFiles->pluck('folder')
            ->merge($project->folders()->pluck('path'))
            ->unique()
            ->sort()
            ->values();

        $folderTree = FolderTreeBuilder::build($folders);

        // Map unified resources list
        $unifiedItems = collect();

        // 1. Standalone Files & Links
        foreach ($standaloneFiles as $file) {
            $isLink = $file->isLink();
            $ext = $isLink ? 'link' : strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
            $unifiedItems->push([
                'id'            => 'pf-' . $file->id,
                'raw_id'        => $file->id,
                'source'        => 'standalone', // 'standalone' | 'task'
                'type'          => $isLink ? 'link' : 'file',
                'name'          => $file->original_name,
                'url'           => $file->url(),
                'size'          => $isLink ? 'Link' : $file->humanSize(),
                'raw_size'      => (int) $file->size,
                'mime_type'     => $file->mime_type,
                'extension'     => $ext,
                'is_image'      => $file->isImage(),
                'folder'        => $file->folder ?: 'General',
                'description'   => $file->description,
                'created_at'    => $file->created_at ? $file->created_at->format('d M Y H:i') : '—',
                'created_diff'  => $file->created_at ? $file->created_at->diffForHumans() : '',
                'created_timestamp' => $file->created_at?->timestamp ?? 0,
                'uploader_name' => $file->uploader?->name ?? 'User',
                'uploader_avatar'=> $file->uploader?->avatar ? Storage::url($file->uploader->avatar) : null,
                'uploader_initials' => $file->uploader?->initials() ?? 'U',
                'uploader_color'=> $file->uploader?->avatarColor() ?? '#6b7280',
                'task'          => null,
                'can_delete'    => !auth()->user()->hasRole('client'),
                'delete_url'    => route('project.files.destroy', [$project, $file]),
                'move_url'      => route('project.files.move', [$project, $file]),
            ]);
        }

        // 2. Task Attachments & Links
        foreach ($taskAttachments as $att) {
            $isLink = $att->isLink();
            $ext = $isLink ? 'link' : $att->extension();
            $unifiedItems->push([
                'id'            => 'ta-' . $att->id,
                'raw_id'        => $att->id,
                'source'        => 'task',
                'type'          => $isLink ? 'link' : 'file',
                'name'          => $att->file_name ?: ($isLink ? $att->url : 'Attachment #' . $att->id),
                'url'           => $att->publicUrl(),
                'size'          => $isLink ? 'Link' : $att->humanSize(),
                'raw_size'      => (int) ($att->file_size ?? 0),
                'mime_type'     => $att->mime_type,
                'extension'     => $ext,
                'is_image'      => $att->isImage(),
                'folder'        => 'Task: ' . ($att->task?->boardColumn?->name ?? 'General'),
                'description'   => null,
                'created_at'    => $att->created_at ? $att->created_at->format('d M Y H:i') : '—',
                'created_diff'  => $att->created_at ? $att->created_at->diffForHumans() : '',
                'created_timestamp' => $att->created_at?->timestamp ?? 0,
                'uploader_name' => $att->creator?->name ?? 'User',
                'uploader_avatar'=> $att->creator?->avatar ? Storage::url($att->creator->avatar) : null,
                'uploader_initials' => $att->creator?->initials() ?? 'U',
                'uploader_color'=> $att->creator?->avatarColor() ?? '#6b7280',
                'task'          => $att->task ? [
                    'id'          => $att->task->id,
                    'title'       => $att->task->title,
                    'status'      => $att->task->status,
                    'priority'    => $att->task->priority,
                    'column_name' => $att->task->boardColumn?->name ?? 'Task',
                ] : null,
                'can_delete'    => !auth()->user()->hasRole('client') && ($att->created_by === auth()->id() || auth()->user()->hasRole(['admin', 'project_manager'])),
                'delete_url'    => $att->task ? route('tasks.attachments.destroy', [$project, $att->task, $att]) : null,
                'move_url'      => null,
            ]);
        }

        // 3. Task Cover Images (if any)
        $taskCovers = $project->tasks()->whereNotNull('cover_image_path')->with(['boardColumn', 'assignee'])->get();
        foreach ($taskCovers as $tc) {
            $unifiedItems->push([
                'id'            => 'tc-' . $tc->id,
                'raw_id'        => $tc->id,
                'source'        => 'task',
                'type'          => 'file',
                'name'          => 'Cover — ' . $tc->title,
                'url'           => Storage::url($tc->cover_image_path),
                'size'          => 'Cover Image',
                'raw_size'      => 0,
                'mime_type'     => 'image/jpeg',
                'extension'     => 'image',
                'is_image'      => true,
                'folder'        => 'Task Covers',
                'description'   => 'Gambar sampul task ' . $tc->title,
                'created_at'    => $tc->updated_at ? $tc->updated_at->format('d M Y H:i') : '—',
                'created_diff'  => $tc->updated_at ? $tc->updated_at->diffForHumans() : '',
                'created_timestamp' => $tc->updated_at?->timestamp ?? 0,
                'uploader_name' => $tc->assignee?->name ?? 'Lead / Assignee',
                'uploader_avatar'=> $tc->assignee?->avatar ? Storage::url($tc->assignee->avatar) : null,
                'uploader_initials' => $tc->assignee?->initials() ?? 'U',
                'uploader_color'=> $tc->assignee?->avatarColor() ?? '#6b7280',
                'task'          => [
                    'id'          => $tc->id,
                    'title'       => $tc->title,
                    'status'      => $tc->status,
                    'priority'    => $tc->priority,
                    'column_name' => $tc->boardColumn?->name ?? 'Task',
                ],
                'can_delete'    => false,
                'delete_url'    => null,
                'move_url'      => null,
            ]);
        }

        // Sort latest first
        $unifiedItems = $unifiedItems->sortByDesc('created_timestamp')->values();

        // Summary Statistics
        $totalItemsCount = $unifiedItems->count();
        $standaloneCount = $unifiedItems->where('source', 'standalone')->count();
        $taskItemsCount  = $unifiedItems->where('source', 'task')->count();
        $linksCount      = $unifiedItems->where('type', 'link')->count();
        $filesCount      = $unifiedItems->where('type', 'file')->count();
        $totalStorageBytes = $unifiedItems->sum('raw_size');
        $humanTotalStorage = $totalStorageBytes >= 1048576 
            ? round($totalStorageBytes / 1048576, 1) . ' MB' 
            : ($totalStorageBytes >= 1024 ? round($totalStorageBytes / 1024, 1) . ' KB' : $totalStorageBytes . ' B');

        // Project tasks list for filtering by task in files view
        $projectTasks = $project->tasks()->select('id', 'title', 'status')->orderBy('title')->get();

        // Alias for backward compatibility
        $files = $standaloneFiles;

        return compact(
            'files',
            'standaloneFiles',
            'taskAttachments',
            'unifiedItems',
            'folders',
            'folderTree',
            'totalItemsCount',
            'standaloneCount',
            'taskItemsCount',
            'linksCount',
            'filesCount',
            'humanTotalStorage',
            'projectTasks'
        );
    }

    /** Buat folder kosong (bisa bersarang lebih dari 1 level lewat "parent"). */
    public function storeFolder(Request $request, Project $project)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'parent' => 'nullable|string|max:255',
        ]);

        $parent = $this->normalizeFolderPath($request->input('parent'));
        $name   = $this->normalizeFolderPath($request->input('name'));
        $path   = $parent !== '' ? "{$parent}/{$name}" : $name;

        if ($path === '') {
            return back()->withErrors(['name' => 'Nama folder tidak boleh kosong.']);
        }

        ProjectFolder::firstOrCreate(
            ['project_id' => $project->id, 'path' => $path],
            ['created_by' => auth()->id()]
        );

        return back()->with('success', "Folder \"{$path}\" berhasil dibuat.");
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200', // 50MB
            'folder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $folder = $this->normalizeFolderPath($request->input('folder', 'General')) ?: 'General';
        $description = $request->input('description');

        foreach ($request->file('files', []) as $file) {
            if (!$file->isValid()) continue;
            $stored = $file->store("project-files/{$project->id}", 'public');
            ProjectFile::create([
                'project_id'    => $project->id,
                'folder'        => $folder,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $stored,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'description'   => $description,
                'uploaded_by'   => auth()->id(),
            ]);
        }

        return back()->with('success', 'File berhasil diunggah.');
    }

    /** Simpan link web / URL eksternal (standalone project link). */
    public function storeLink(Request $request, Project $project)
    {
        $request->validate([
            'url' => 'required|url|max:2000',
            'title' => 'required|string|max:255',
            'folder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $folder = $this->normalizeFolderPath($request->input('folder', 'General')) ?: 'General';

        ProjectFile::create([
            'project_id'    => $project->id,
            'folder'        => $folder,
            'original_name' => $request->input('title'),
            'stored_name'   => $request->input('url'),
            'mime_type'     => 'link',
            'size'          => 0,
            'description'   => $request->input('description'),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Link berhasil ditambahkan ke file proyek.');
    }

    public function destroy(Project $project, ProjectFile $projectFile)
    {
        if (!$projectFile->isLink() && $projectFile->stored_name) {
            Storage::disk('public')->delete($projectFile->stored_name);
        }
        $projectFile->delete();
        return back()->with('success', 'File / Link berhasil dihapus.');
    }

    public function moveFolder(Request $request, Project $project, ProjectFile $projectFile)
    {
        $request->validate(['folder' => 'required|string|max:255']);
        $projectFile->update(['folder' => $this->normalizeFolderPath($request->folder)]);
        return back()->with('success', 'File dipindahkan.');
    }

    /**
     * "Docs / Kontrak / / 2024/" -> "Docs/Kontrak/2024" — supaya folder bisa
     * dinamai lewat "/" (folder di dalam folder) tanpa slash ganda/nyasar di ujung.
     */
    private function normalizeFolderPath(?string $path): string
    {
        $segments = array_filter(array_map('trim', explode('/', $path ?? '')), fn($s) => $s !== '');
        return implode('/', $segments);
    }
}
