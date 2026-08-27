<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamNotifier
{
    /**
     * Kirim pesan ke Slack & Discord webhook milik project (kalau dikonfigurasi).
     * Dibuat best-effort: kegagalan kirim tidak boleh menggagalkan aksi utama (mis. simpan task),
     * jadi exception ditelan & dicatat ke log saja.
     */
    public function notify(Project $project, string $title, string $message, ?string $url = null): void
    {
        if ($project->hasSlackIntegration()) {
            $this->sendSlack($project->slack_webhook_url, $title, $message, $url);
        }

        if ($project->hasDiscordIntegration()) {
            $this->sendDiscord($project->discord_webhook_url, $title, $message, $url);
        }
    }

    public function sendSlack(string $webhookUrl, string $title, string $message, ?string $url = null): bool
    {
        $text = "*{$title}*\n{$message}" . ($url ? "\n<{$url}>" : '');

        try {
            $resp = Http::timeout(5)->post($webhookUrl, ['text' => $text]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::warning('Slack webhook gagal terkirim: ' . $e->getMessage());
            return false;
        }
    }

    public function sendDiscord(string $webhookUrl, string $title, string $message, ?string $url = null): bool
    {
        $content = "**{$title}**\n{$message}" . ($url ? "\n{$url}" : '');

        try {
            $resp = Http::timeout(5)->post($webhookUrl, ['content' => $content]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::warning('Discord webhook gagal terkirim: ' . $e->getMessage());
            return false;
        }
    }
}