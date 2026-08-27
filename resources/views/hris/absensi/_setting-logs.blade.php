@php
    $fieldLabels = [
        'is_location_enabled'           => 'Validasi Lokasi (GPS)',
        'office_name'                   => 'Nama Kantor',
        'office_latitude'               => 'Latitude Kantor',
        'office_longitude'              => 'Longitude Kantor',
        'max_distance_meters'           => 'Radius Maksimum',
        'require_location_for_checkout' => 'Validasi Lokasi saat Check-Out',
        'is_face_recognition_enabled'   => 'Pengenalan Wajah',
        'face_recognition_threshold'    => 'Sensitivitas Pengenalan Wajah',
        'require_face_for_checkout'     => 'Verifikasi Wajah saat Check-Out',
    ];
    $fmtVal = function ($field, $val) {
        if ($val === null || $val === '') return '—';
        if (is_bool($val)) return $val ? 'Ya' : 'Tidak';
        if ($field === 'max_distance_meters') return $val . 'm';
        if (in_array($field, ['office_latitude', 'office_longitude'])) return rtrim(rtrim(number_format($val, 8, ',', ''), '0'), ',');
        if ($field === 'face_recognition_threshold') return number_format($val, 2, ',', '');
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
            @foreach($new as $field => $newVal)
                <div>
                    <span class="text-gray-500">{{ $fieldLabels[$field] ?? $field }}:</span>
                    <span class="text-red-600 line-through">{{ $fmtVal($field, $old[$field] ?? null) }}</span>
                    <span class="text-gray-300">→</span>
                    <span class="text-green-700 font-medium">{{ $fmtVal($field, $newVal) }}</span>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <p class="px-4 py-6 text-center text-xs text-gray-400">Belum ada perubahan pengaturan.</p>
@endforelse
