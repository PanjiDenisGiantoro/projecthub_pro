<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GithubService
{
    private const BASE_URL = 'https://api.github.com';

    /**
     * @return array{ok: bool, message?: string, commits?: array, pulls?: array, repo?: array}
     */
    public function fetchSummary(Project $project): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return ['ok' => false, 'message' => 'Repo belum dikonfigurasi.'];
        }

        return Cache::remember(
            "github.summary.project.{$project->id}",
            120,
            fn () => $this->fetchFromApi($ownerRepo, $project->github_token)
        );
    }

    public function forgetCache(Project $project): void
    {
        Cache::forget("github.summary.project.{$project->id}");
    }

    private function fetchFromApi(string $ownerRepo, string $token): array
    {
        $client = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(10);

        $repoResp = $client->get(self::BASE_URL . "/repos/{$ownerRepo}");

        if ($repoResp->status() === 401) {
            return ['ok' => false, 'message' => 'Token GitHub tidak valid atau sudah kedaluwarsa.'];
        }
        if ($repoResp->status() === 404) {
            return ['ok' => false, 'message' => 'Repo tidak ditemukan (cek URL atau akses token).'];
        }
        if (!$repoResp->successful()) {
            return ['ok' => false, 'message' => 'Gagal menghubungi GitHub API (' . $repoResp->status() . ').'];
        }

        $commitsResp = $client->get(self::BASE_URL . "/repos/{$ownerRepo}/commits", ['per_page' => 10]);
        $pullsResp   = $client->get(self::BASE_URL . "/repos/{$ownerRepo}/pulls", ['state' => 'open', 'per_page' => 10]);

        $commits = collect($commitsResp->successful() ? $commitsResp->json() : [])->map(fn ($c) => [
            'sha'     => substr($c['sha'], 0, 7),
            'message' => strtok($c['commit']['message'], "\n"),
            'author'  => $c['commit']['author']['name'] ?? 'unknown',
            'avatar'  => $c['author']['avatar_url'] ?? null,
            'url'     => $c['html_url'],
            'date'    => $c['commit']['author']['date'] ?? null,
        ])->all();

        $pulls = collect($pullsResp->successful() ? $pullsResp->json() : [])->map(fn ($p) => [
            'number' => $p['number'],
            'title'  => $p['title'],
            'author' => $p['user']['login'] ?? 'unknown',
            'avatar' => $p['user']['avatar_url'] ?? null,
            'url'    => $p['html_url'],
            'date'   => $p['created_at'],
        ])->all();

        $repo = $repoResp->json();

        return [
            'ok'      => true,
            'repo'    => [
                'full_name'    => $repo['full_name'] ?? $ownerRepo,
                'description'  => $repo['description'] ?? null,
                'default_branch' => $repo['default_branch'] ?? 'main',
                'stars'        => $repo['stargazers_count'] ?? 0,
                'open_issues'  => $repo['open_issues_count'] ?? 0,
                'url'          => $repo['html_url'] ?? "https://github.com/{$ownerRepo}",
            ],
            'commits' => $commits,
            'pulls'   => $pulls,
        ];
    }
}