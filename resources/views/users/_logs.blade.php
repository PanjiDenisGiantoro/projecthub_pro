@php
    $fieldLabels = [
        'name'                      => 'Nama',
        'email'                     => 'Email',
        'is_active'                 => 'Status Aktif',
        'employment_type'           => 'Tipe Karyawan',
        'employment_type_other'     => 'Nama Tipe Karyawan',
        'outsourcing_company_name'  => 'Dari Perusahaan',
        'hire_date'                 => 'Tanggal Mulai Kerja',
        'contract_end_date'         => 'Tanggal Akhir Kontrak',
        'custom_fields'             => 'Field Tambahan',
    ];
    $eventLabels = [
        'created' => ['text' => 'Membuat user', 'color' => 'text-green-700'],
        'updated' => ['text' => 'Mengubah user', 'color' => 'text-blue-700'],
        'deleted' => ['text' => 'Menghapus user', 'color' => 'text-red-700'],
    ];
    $fmtVal = function ($field, $val) {
        if ($val === null || $val === '') return '—';
        if (is_bool($val)) return $val ? 'Ya' : 'Tidak';
        if ($field === 'custom_fields') return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string) $val;
        return (string) $val;
    };
@endphp
@forelse($logs as $log)
    @php
        $old   = $log->attribute_changes['old'] ?? [];
        $new   = $log->attribute_changes['attributes'] ?? [];
        $isDel = $log->event === 'deleted';
        $rows  = $isDel ? $old : $new;
        $targetName = optional($log->subject)->name
            ?? ($new['name'] ?? null)
            ?? ($old['name'] ?? null)
            ?? ('User #' . $log->subject_id);
        $ev = $eventLabels[$log->event] ?? ['text' => ucfirst($log->event ?? '-'), 'color' => 'text-gray-700'];
    @endphp
    <div class="px-4 py-3 text-xs">
        <div class="flex items-center justify-between gap-2">
            <span>
                <span class="font-medium text-gray-700">{{ $log->causer->name ?? 'System' }}</span>
                <span class="{{ $ev['color'] }} font-medium"> {{ strtolower($ev['text']) }} </span>
                <span class="font-medium text-gray-700">{{ $targetName }}</span>
            </span>
            <span class="text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</span>
        </div>

        @if($log->event === 'updated' && count($rows))
        <div class="mt-1.5 space-y-1">
            @foreach($rows as $field => $newVal)
                <div>
                    <span class="text-gray-500">{{ $fieldLabels[$field] ?? $field }}:</span>
                    <span class="text-red-600 line-through">{{ $fmtVal($field, $old[$field] ?? null) }}</span>
                    <span class="text-gray-300">→</span>
                    <span class="text-green-700 font-medium">{{ $fmtVal($field, $newVal) }}</span>
                </div>
            @endforeach
        </div>
        @endif
    </div>
@empty
    <p class="px-4 py-6 text-center text-xs text-gray-400">Belum ada aktivitas.</p>
@endforelse
