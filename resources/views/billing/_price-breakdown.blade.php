{{-- Rincian harga paket + PPN (+ biaya layanan opsional). Butuh: $subtotal, $rate, $ppn, $total; opsional: $fee, $feeLabel --}}
<div class="text-sm space-y-1">
    <div class="flex justify-between text-gray-500">
        <span>Harga paket</span>
        <span class="text-gray-800">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
    </div>
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
