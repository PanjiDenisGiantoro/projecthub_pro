<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileWebController extends Controller
{
    public function index(Project $project)
    {
        $files = $project->files()->with('uploader')->orderBy('folder')->orderByDesc('created_at')->get();

        // Gabungkan folder yang sudah berisi file dengan folder kosong yang
        // sengaja dibuat duluan (belum ada file di dalamnya).
        $folders = $files->pluck('folder')
            ->merge($project->folders()->pluck('path'))
            ->unique()
            ->sort()
            ->values();

        $folderTree = $this->buildFolderTree($folders);
        return view('files.index', compact('project', 'files', 'folders', 'folderTree'));
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

    public function destroy(Project $project, ProjectFile $projectFile)
    {
        Storage::disk('public')->delete($projectFile->stored_name);
        $projectFile->delete();
        return back()->with('success', 'File dihapus.');
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

    /**
     * Ubah daftar path folder flat ("Docs", "Docs/Kontrak") jadi tree bersarang
     * untuk ditampilkan sebagai folder-di-dalam-folder di sidebar.
     */
    private function buildFolderTree(\Illuminate\Support\Collection $paths): array
    {
        $tree = [];
        foreach ($paths as $path) {
            $node = &$tree;
            $currentPath = '';
            foreach (explode('/', $path) as $segment) {
                $currentPath = $currentPath === '' ? $segment : "{$currentPath}/{$segment}";
                $node[$segment] ??= ['path' => $currentPath, 'children' => []];
                $node = &$node[$segment]['children'];
            }
            unset($node);
        }
        return $tree;
    }
}
