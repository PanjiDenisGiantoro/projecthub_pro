@php
    $eventColor = [
        'created' => 'text-green-700',
        'updated' => 'text-blue-700',
        'deleted' => 'text-red-700',
    ];
@endphp
@forelse($logs as $log)
    <div class="px-4 py-3 text-xs flex items-center justify-between gap-2">
        <span>
            <span class="font-medium text-gray-700">{{ $log->causer->name ?? 'System' }}</span>
            <span class="{{ $eventColor[$log->event] ?? 'text-gray-700' }}"> {{ $log->description }}</span>
        </span>
        <span class="text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</span>
    </div>
@empty
    <p class="px-4 py-6 text-center text-xs text-gray-400">Belum ada aktivitas pendaftaran wajah.</p>
@endforelse
