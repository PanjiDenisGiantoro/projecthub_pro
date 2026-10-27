{{-- Rincian harga paket + PPN (+ promo & biaya layanan opsional). Butuh: $subtotal (setelah promo), $rate, $ppn, $total; opsional: $gross, $discount, $promoLabel, $priceLabel, $fee, $feeLabel --}}
<div class="text-sm space-y-1">
    <div class="flex justify-between gap-3 text-gray-500">
        <span>{{ $priceLabel ?? 'Harga paket' }}</span>
        <span class="text-gray-800 whitespace-nowrap">Rp {{ number_format(($discount ?? 0) > 0 ? $gross : $subtotal, 0, ',', '.') }}</span>
    </div>
    @if(($discount ?? 0) > 0)
        <div class="flex justify-between gap-3 text-emerald-600">
            <span>Promo{{ ! empty($promoLabel) ? ' ' . $promoLabel : '' }}</span>
            <span class="whitespace-nowrap">−Rp {{ number_format($discount, 0, ',', '.') }}</span>
        </div>
    @endif
    <div class="flex justify-between text-gray-500">
        <span>PPN {{ \App\Models\PpnRate::formatRate($rate) }}</span>
        <span class="text-gray-800">Rp {{ number_format($ppn, 0, ',', '.') }}</span>
    </div>
    @if(($fee ?? 0) > 0)
        <div class="flex justify-between text-gray-500">
            <span>Biaya layanan{{ ! empty($feeLabel) ? ' (' . $feeLabel . ')' : '' }}</span>
            <span class="text-gray-800">Rp {{ number_format($fee, 0, ',', '.') }}</span>
        </div>
    @endif
    <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-1">
        <span>Total</span>
        <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>
</div>
