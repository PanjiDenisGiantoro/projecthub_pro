<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\GithubService;
use Illuminate\Http\Request;

class GithubWebController extends Controller
{
    public function __construct(private GithubService $github)
    {
    }

    public function index(Request $request, Project $project)
    {
        $branch = $request->query('branch') ?: null;

        $summary = $project->hasGithubIntegration()
            ? $this->github->fetchSummary($project, $branch)
            : null;

        $branches = $project->hasGithubIntegration()
            ? $this->github->listBranches($project)
            : [];

        return view('github.index', compact('project', 'summary', 'branches'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'github_repo_url' => 'required|url|max:255',
            'github_token'    => 'required|string|max:255',
        ]);

        $project->update($data);
        $this->github->forgetCache($project);

        return redirect()->route('github.index', $project)
            ->with('success', 'Repo GitHub berhasil dihubungkan.');
    }

    public function refresh(Request $request, Project $project)
    {
        $branch = $request->query('branch') ?: null;
        $this->github->forgetCache($project, $branch);

        return redirect()->route('github.index', array_filter(['project' => $project->id, 'branch' => $branch]))
            ->with('success', 'Data GitHub diperbarui.');
    }

    public function destroy(Project $project)
    {
        $this->github->forgetCache($project);
        $project->update(['github_repo_url' => null, 'github_token' => null]);

        return redirect()->route('github.index', $project)
            ->with('success', 'Repo GitHub berhasil diputuskan.');
    }

    public function files(Request $request, Project $project)
    {
        abort_unless($project->hasGithubIntegration(), 404);

        $branch = $request->query('branch') ?: null;
        $path = trim((string) $request->query('path', ''), '/');

        $result = $this->github->browse($project, $branch, $path);
        $branches = $this->github->listBranches($project);
        $activeBranch = $branch ?: ($this->github->fetchSummary($project)['repo']['default_branch'] ?? null);

        return view('github.files', compact('project', 'result', 'branches', 'path', 'activeBranch'));
    }

    public function editFile(Request $request, Project $project)
    {
        abort_unless($project->hasGithubIntegration(), 404);

        $branch = $request->query('branch') ?: null;
        $path = trim((string) $request->query('path', ''), '/');
        abort_if($path === '', 404);

        $file = $this->github->getFile($project, $branch, $path);
        $activeBranch = $branch ?: ($this->github->fetchSummary($project)['repo']['default_branch'] ?? null);

        return view('github.file-edit', compact('project', 'file', 'path', 'activeBranch'));
    }

    public function updateFile(Request $request, Project $project)
    {
        abort_unless($project->hasGithubIntegration(), 404);

        $data = $request->validate([
            'path'    => 'required|string',
            'branch'  => 'required|string',
            'sha'     => 'required|string',
            'content' => 'required|string',
            'message' => 'nullable|string|max:255',
        ]);

        $result = $this->github->updateFile(
            $project,
            $data['branch'],
            $data['path'],
            $data['content'],
            $data['sha'],
            $data['message'] ?: "Update {$data['path']} via ProjectHub"
        );

        if (!($result['ok'] ?? false)) {
            return back()->withInput()->with('error', $result['message'] ?? 'Gagal menyimpan perubahan ke GitHub.');
        }

        $this->github->forgetCache($project, $data['branch']);

        return redirect()->route('github.files.edit', [
            'project' => $project,
            'path'    => $data['path'],
            'branch'  => $data['branch'],
        ])->with('success', 'File berhasil di-commit ke GitHub.');
    }
}