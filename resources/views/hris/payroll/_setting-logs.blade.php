@php
    $fieldLabels = [
        'method'                  => 'Metode PPh 21',
        'payment_scheme'          => 'Skema Pembayaran',
        'jkk_rate'                => 'Tarif JKK',
        'potong_alpha'            => 'Potong Gaji Alpha',
        'potongan_alpha_metode'   => 'Metode Potongan Alpha',
        'potongan_alpha_nominal'  => 'Nominal Potongan Alpha',
        'tax_tunjangan_jabatan'   => 'Pajak Tunjangan Jabatan',
        'tax_tunjangan_transport' => 'Pajak Tunjangan Transport',
        'tax_tunjangan_makan'     => 'Pajak Tunjangan Makan',
    ];
    $fmtVal = function ($field, $val) {
        if ($val === null) return '—';
        if (is_bool($val)) return $val ? 'Ya' : 'Tidak';
        if ($field === 'jkk_rate') return rtrim(rtrim(number_format($val * 100, 2, ',', '.'), '0'), ',') . '%';
        if ($field === 'potongan_alpha_nominal') return 'Rp' . number_format($val, 0, ',', '.');
        if ($field === 'method') return $val === 'ter' ? 'TER' : 'Progresif';
        if ($field === 'payment_scheme') return ['gross' => 'Gross', 'gross_up' => 'Gross-Up', 'net' => 'Net'][$val] ?? $val;
        if ($field === 'potongan_alpha_metode') return $val === 'nominal' ? 'Nominal Tetap' : 'Proporsional';
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
