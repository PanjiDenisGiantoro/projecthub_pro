@php
    $fieldLabels = [
        'title'             => 'Judul',
        'description'       => 'Deskripsi',
        'completion_notes'  => 'Deskripsi Penyelesaian',
        'status'            => 'Status',
        'priority'          => 'Prioritas',
        'assigned_to'       => 'Assignee',
        'milestone_id'      => 'Milestone',
        'board_column_id'   => 'Kolom Board',
        'sprint_id'         => 'Sprint',
        'start_date'        => 'Tanggal Mulai',
        'due_date'          => 'Tenggat',
        'estimated_hours'   => 'Estimasi Jam',
        'story_points'      => 'Story Points',
    ];
    $statusLabels   = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
    $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

    // Kumpulkan semua ID foreign key dari seluruh log dulu, baru resolve nama-nya sekali
    // query per jenis relasi (bukan query per baris log — hindari N+1 di panel ini).
    $userIds = collect();
    $milestoneIds = collect();
    $columnIds = collect();
    foreach ($logs as $log) {
        foreach ([$log->attribute_changes['old'] ?? [], $log->attribute_changes['attributes'] ?? []] as $set) {
            if (array_key_exists('assigned_to', $set)) $userIds->push($set['assigned_to']);
            if (array_key_exists('milestone_id', $set)) $milestoneIds->push($set['milestone_id']);
            if (array_key_exists('board_column_id', $set)) $columnIds->push($set['board_column_id']);
        }
    }
    $userNames      = \App\Models\User::whereIn('id', $userIds->filter()->unique())->pluck('name', 'id');
    $milestoneNames = \App\Models\Milestone::whereIn('id', $milestoneIds->filter()->unique())->pluck('title', 'id');
    $columnNames    = \App\Models\BoardColumn::whereIn('id', $columnIds->filter()->unique())->pluck('name', 'id');

    $fmtVal = function ($field, $val) use ($statusLabels, $priorityLabels, $userNames, $milestoneNames, $columnNames) {
        if ($val === null || $val === '') return '—';
        if ($field === 'status') return $statusLabels[$val] ?? $val;
        if ($field === 'priority') return $priorityLabels[$val] ?? ucfirst($val);
        if ($field === 'assigned_to') return $userNames[$val] ?? "User #{$val}";
        if ($field === 'milestone_id') return $milestoneNames[$val] ?? "Milestone #{$val}";
        if ($field === 'board_column_id') return $columnNames[$val] ?? "Kolom #{$val}";
        if (in_array($field, ['start_date', 'due_date'], true)) {
            try { return \Carbon\Carbon::parse($val)->format('d M Y'); } catch (\Throwable) { return (string) $val; }
        }
        if ($field === 'estimated_hours') return $val . ' jam';
        if (is_bool($val)) return $val ? 'Ya' : 'Tidak';
        if (is_string($val) && mb_strlen($val) > 80) return mb_substr($val, 0, 80) . '…';
        return (string) $val;
    };
@endphp
@forelse($logs as $log)
    @php
        $old = $log->attribute_changes['old'] ?? [];
        $new = $log->attribute_changes['attributes'] ?? [];
    @endphp
    <div class="px-4 py-3 text-xs">
        <div class="flex items-center justify-between gap-2">
            <span class="font-medium text-gray-700">{{ $log->causer->name ?? 'System' }}</span>
            <span class="text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</span>
        </div>
        <div class="mt-1.5 space-y-1">
            @forelse($new as $field => $newVal)
                <div>
                    <span class="text-gray-500">{{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}:</span>
                    <span class="text-red-600 line-through">{{ $fmtVal($field, $old[$field] ?? null) }}</span>
                    <span class="text-gray-300">→</span>
                    <span class="text-green-700 font-medium">{{ $fmtVal($field, $newVal) }}</span>
                </div>
            @empty
                <p class="text-gray-400 italic">{{ ucfirst($log->description) }}</p>
            @endforelse
        </div>
    </div>
@empty
    <p class="px-4 py-6 text-center text-xs text-gray-400">Belum ada perubahan pada task ini.</p>
@endforelse
