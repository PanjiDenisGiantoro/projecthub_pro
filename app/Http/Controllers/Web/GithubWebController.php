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

    public function index(Project $project)
    {
        $summary = $project->hasGithubIntegration()
            ? $this->github->fetchSummary($project)
            : null;

        return view('github.index', compact('project', 'summary'));
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

    public function refresh(Project $project)
    {
        $this->github->forgetCache($project);

        return redirect()->route('github.index', $project)
            ->with('success', 'Data GitHub diperbarui.');
    }

    public function destroy(Project $project)
    {
        $this->github->forgetCache($project);
        $project->update(['github_repo_url' => null, 'github_token' => null]);

        return redirect()->route('github.index', $project)
            ->with('success', 'Repo GitHub berhasil diputuskan.');
    }
}