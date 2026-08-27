<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;

class TeamNotificationWebController extends Controller
{
    public function __construct(private TeamNotifier $notifier)
    {
    }

    public function index(Project $project)
    {
        return view('notifications.index', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'provider'    => 'required|in:slack,discord',
            'webhook_url' => 'required|url|max:500',
        ]);

        $project->update([
            $data['provider'] . '_webhook_url' => $data['webhook_url'],
        ]);

        return back()->with('success', ucfirst($data['provider']) . ' berhasil dihubungkan.');
    }

    public function destroy(Request $request, Project $project)
    {
        $data = $request->validate(['provider' => 'required|in:slack,discord']);

        $project->update([
            $data['provider'] . '_webhook_url' => null,
        ]);

        return back()->with('success', ucfirst($data['provider']) . ' berhasil diputuskan.');
    }

    public function test(Request $request, Project $project)
    {
        $data = $request->validate(['provider' => 'required|in:slack,discord']);

        $webhookUrl = $project->{$data['provider'] . '_webhook_url'};
        if (!$webhookUrl) {
            return back()->with('error', 'Webhook belum dikonfigurasi.');
        }

        $method = $data['provider'] === 'slack' ? 'sendSlack' : 'sendDiscord';
        $ok = $this->notifier->{$method}(
            $webhookUrl,
            'Test Notifikasi',
            'Ini pesan uji coba dari proyek "' . $project->name . '" di ProjectHub Pro. Kalau ini muncul, integrasi berhasil! 🎉'
        );

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Test notifikasi berhasil dikirim, cek channel-nya.'
            : 'Gagal mengirim test notifikasi, cek kembali URL webhook-nya.');
    }
}