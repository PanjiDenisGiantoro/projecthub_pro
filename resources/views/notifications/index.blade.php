@extends('layouts.app')
@section('title', 'Notifikasi Tim — ' . $project->name)
@section('page-title', 'Notifikasi Tim')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Notifikasi Tim</span>
    </nav>

    <p class="text-sm text-gray-500 mb-5 max-w-2xl">
        Hubungkan channel Slack/Discord untuk dapat notifikasi otomatis saat ada task baru, task selesai, atau tiket baru di proyek ini.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- ── Slack ─────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:#4A154B">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M9 15.5a2.5 2.5 0 01-2.5 2.5A2.5 2.5 0 014 15.5 2.5 2.5 0 016.5 13H9v2.5zM10.25 15.5a2.5 2.5 0 012.5-2.5 2.5 2.5 0 012.5 2.5V22a2.5 2.5 0 01-2.5 2.5 2.5 2.5 0 01-2.5-2.5v-6.5zM12.75 9A2.5 2.5 0 0110.25 6.5 2.5 2.5 0 0112.75 4a2.5 2.5 0 012.5 2.5V9h-2.5zM12.75 10.25a2.5 2.5 0 012.5 2.5 2.5 2.5 0 01-2.5 2.5H6.25a2.5 2.5 0 01-2.5-2.5 2.5 2.5 0 012.5-2.5h6.5zM15.5 12.75a2.5 2.5 0 012.5-2.5 2.5 2.5 0 012.5 2.5A2.5 2.5 0 0118 15.25h-2.5v-2.5zM19 9A2.5 2.5 0 0121.5 6.5 2.5 2.5 0 0119 4a2.5 2.5 0 01-2.5 2.5V9H19z" fill="#fff"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Slack</h2>
                    <p class="text-xs text-gray-500">{{ $project->hasSlackIntegration() ? 'Terhubung' : 'Belum terhubung' }}</p>
                </div>
            </div>

            @if(!$project->hasSlackIntegration())
                <form method="POST" action="{{ route('team-notifications.store', $project) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="provider" value="slack">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Incoming Webhook URL</label>
                        <input type="url" name="webhook_url" required placeholder="https://hooks.slack.com/services/..."
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">
                            Buat di Slack → App Directory → cari "Incoming Webhooks" → Add to Slack → pilih channel → copy Webhook URL.
                        </p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors" style="background:#4A154B">
                        Hubungkan Slack
                    </button>
                </form>
            @else
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('team-notifications.test', $project) }}">
                        @csrf
                        <input type="hidden" name="provider" value="slack">
                        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                            Kirim Test
                        </button>
                    </form>
                    <form method="POST" action="{{ route('team-notifications.destroy', $project) }}" onsubmit="return confirm('Putuskan integrasi Slack?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="provider" value="slack">
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 px-3 py-2 rounded-lg border border-red-200 hover:bg-red-50 transition">
                            Putuskan
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- ── Discord ───────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:#5865F2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#fff"><path d="M20.3 5.4A18 18 0 0015.6 4l-.3.5a12.6 12.6 0 016.6 3.4 15.4 15.4 0 00-13.7-1.3A15.5 15.5 0 006.8 4l-.3.5a12.6 12.6 0 016.6-3.4l-.3-.5a18 18 0 00-4.7 1.4C4.7 8 3.7 12.4 4 16.8a18 18 0 005.4 2.7l.7-1.2a11.7 11.7 0 01-1.9-.9l.5-.4c3.5 1.6 7.4 1.6 10.9 0l.5.4a11.7 11.7 0 01-1.9.9l.7 1.2a18 18 0 005.4-2.7c.4-5.1-.9-9.5-3.9-11.4zM9.7 14.3c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.9 2.1-1.9 2.1zm6.6 0c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.8 2.1-1.9 2.1z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Discord</h2>
                    <p class="text-xs text-gray-500">{{ $project->hasDiscordIntegration() ? 'Terhubung' : 'Belum terhubung' }}</p>
                </div>
            </div>

            @if(!$project->hasDiscordIntegration())
                <form method="POST" action="{{ route('team-notifications.store', $project) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="provider" value="discord">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
                        <input type="url" name="webhook_url" required placeholder="https://discord.com/api/webhooks/..."
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">
                            Buat di Discord → Server Settings → Integrations → Webhooks → New Webhook → pilih channel → Copy Webhook URL.
                        </p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors" style="background:#5865F2">
                        Hubungkan Discord
                    </button>
                </form>
            @else
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('team-notifications.test', $project) }}">
                        @csrf
                        <input type="hidden" name="provider" value="discord">
                        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                            Kirim Test
                        </button>
                    </form>
                    <form method="POST" action="{{ route('team-notifications.destroy', $project) }}" onsubmit="return confirm('Putuskan integrasi Discord?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="provider" value="discord">
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 px-3 py-2 rounded-lg border border-red-200 hover:bg-red-50 transition">
                            Putuskan
                        </button>
                    </form>
                </div>
            @endif
        </div>

    </div>

    @if($project->hasSlackIntegration() || $project->hasDiscordIntegration())
    <div class="mt-5 bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-500">
        Notifikasi otomatis terkirim saat: task baru dibuat, task ditandai selesai, dan tiket baru dibuka.
    </div>
    @endif
</div>
@endsection