<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GithubService
{
    private const BASE_URL = 'https://api.github.com';

    /**
     * @return array{ok: bool, message?: string, commits?: array, pulls?: array, repo?: array, branch?: string}
     */
    public function fetchSummary(Project $project, ?string $branch = null): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return ['ok' => false, 'message' => 'Repo belum dikonfigurasi.'];
        }

        $cacheKey = "github.summary.project.{$project->id}." . ($branch ?: 'default');

        return Cache::remember(
            $cacheKey,
            120,
            fn () => $this->fetchFromApi($ownerRepo, $project->github_token, $branch)
        );
    }

    /**
     * @return string[]
     */
    public function listBranches(Project $project): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return [];
        }

        return Cache::remember(
            "github.branches.project.{$project->id}",
            300,
            function () use ($project, $ownerRepo) {
                $resp = $this->client($project->github_token)
                    ->get(self::BASE_URL . "/repos/{$ownerRepo}/branches", ['per_page' => 100]);

                return $resp->successful()
                    ? collect($resp->json())->pluck('name')->all()
                    : [];
            }
        );
    }

    /**
     * List the contents of a directory in the repo at a given branch.
     *
     * @return array{ok: bool, message?: string, entries?: array}
     */
    public function browse(Project $project, ?string $branch, string $path): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return ['ok' => false, 'message' => 'Repo belum dikonfigurasi.'];
        }

        $resp = $this->client($project->github_token)->get(
            self::BASE_URL . "/repos/{$ownerRepo}/contents/" . ltrim($path, '/'),
            array_filter(['ref' => $branch])
        );

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'Gagal memuat isi folder (' . $resp->status() . ').'];
        }

        $body = $resp->json();

        if (!is_array($body) || isset($body['type'])) {
            return ['ok' => false, 'message' => 'Path tersebut adalah file, bukan folder.'];
        }

        $entries = collect($body)
            ->map(fn ($e) => [
                'name' => $e['name'],
                'path' => $e['path'],
                'type' => $e['type'],
            ])
            ->sort(fn ($a, $b) => $a['type'] === $b['type']
                ? strcasecmp($a['name'], $b['name'])
                : ($a['type'] === 'dir' ? -1 : 1))
            ->values()
            ->all();

        return ['ok' => true, 'entries' => $entries];
    }

    /**
     * Fetch a single file's content at a given branch.
     *
     * @return array{ok: bool, message?: string, name?: string, path?: string, sha?: string, content?: string, binary?: bool, html_url?: string}
     */
    public function getFile(Project $project, ?string $branch, string $path): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return ['ok' => false, 'message' => 'Repo belum dikonfigurasi.'];
        }

        $resp = $this->client($project->github_token)->get(
            self::BASE_URL . "/repos/{$ownerRepo}/contents/" . ltrim($path, '/'),
            array_filter(['ref' => $branch])
        );

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'Gagal memuat file (' . $resp->status() . ').'];
        }

        $body = $resp->json();

        if (!is_array($body) || ($body['type'] ?? null) !== 'file') {
            return ['ok' => false, 'message' => 'Path tersebut bukan file.'];
        }

        $content = base64_decode($body['content'] ?? '');

        return [
            'ok'       => true,
            'name'     => $body['name'],
            'path'     => $body['path'],
            'sha'      => $body['sha'],
            'content'  => $content,
            'binary'   => str_contains($content, "\0"),
            'html_url' => $body['html_url'] ?? null,
        ];
    }

    /**
     * Commit a new version of a file to a branch.
     *
     * @return array{ok: bool, message?: string}
     */
    public function updateFile(Project $project, string $branch, string $path, string $content, string $sha, string $message): array
    {
        $ownerRepo = $project->githubOwnerRepo();

        if (!$ownerRepo || !$project->github_token) {
            return ['ok' => false, 'message' => 'Repo belum dikonfigurasi.'];
        }

        $resp = $this->client($project->github_token)->put(
            self::BASE_URL . "/repos/{$ownerRepo}/contents/" . ltrim($path, '/'),
            [
                'message' => $message,
                'content' => base64_encode($content),
                'sha'     => $sha,
                'branch'  => $branch,
            ]
        );

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => $resp->json('message') ?? 'Gagal menyimpan perubahan ke GitHub.'];
        }

        return ['ok' => true];
    }

    public function forgetCache(Project $project, ?string $branch = null): void
    {
        Cache::forget("github.summary.project.{$project->id}." . ($branch ?: 'default'));
        Cache::forget("github.branches.project.{$project->id}");
    }

    private function client(string $token)
    {
        return Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(10);
    }

    private function fetchFromApi(string $ownerRepo, string $token, ?string $branch): array
    {
        $client = $this->client($token);

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

        $repo = $repoResp->json();
        $activeBranch = $branch ?: ($repo['default_branch'] ?? 'main');

        $commitsResp = $client->get(self::BASE_URL . "/repos/{$ownerRepo}/commits", ['per_page' => 10, 'sha' => $activeBranch]);
        $pullsResp   = $client->get(self::BASE_URL . "/repos/{$ownerRepo}/pulls", ['state' => 'open', 'per_page' => 10, 'base' => $activeBranch]);

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

        return [
            'ok'      => true,
            'branch'  => $activeBranch,
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