<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileWebController extends Controller
{
    public function index(Project $project)
    {
        $files = $project->files()->with('uploader')->orderBy('folder')->orderByDesc('created_at')->get();
        $folders = $files->pluck('folder')->unique()->sort()->values();
        return view('files.index', compact('project', 'files', 'folders'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200', // 50MB
            'folder' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $folder = trim($request->input('folder', 'General')) ?: 'General';
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
        $request->validate(['folder' => 'required|string|max:100']);
        $projectFile->update(['folder' => $request->folder]);
        return back()->with('success', 'File dipindahkan.');
    }
}
