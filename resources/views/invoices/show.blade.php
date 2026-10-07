@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Detail Invoice')

@push('head')
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .print-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
@endpush

@section('content')
@php
    $statusMap = [
        'draft'     => ['bg' => 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300 border-slate-200', 'dot' => 'bg-slate-400',   'label' => 'Draft'],
        'sent'      => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300',     'dot' => 'bg-blue-500',    'label' => 'Terkirim'],
        'paid'      => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300', 'dot' => 'bg-emerald-500', 'label' => 'Lunas'],
        'overdue'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300',     'dot' => 'bg-rose-500',    'label' => 'Jatuh Tempo'],
        'cancelled' => ['bg' => 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400',       'dot' => 'bg-gray-400',    'label' => 'Dibatalkan'],
    ];
    $st = $statusMap[$invoice->status] ?? $statusMap['draft'];
    $user = auth()->user();
    $isOverdue = $invoice->status === 'overdue' || ($invoice->status !== 'paid' && $invoice->due_date && $invoice->due_date->isPast());
@endphp

<div class="space-y-6 pt-5 pb-8 max-w-4xl mx-auto">

    {{-- ── Top Navigation & Actions Bar (No Print) ────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
        <nav class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <a href="{{ route('invoices.index') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                <span>&larr; Invoice & Penagihan</span>
            </a>
            <span>/</span>
            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $invoice->invoice_number }}</span>
        </nav>

        <div class="flex items-center gap-2.5 flex-wrap">
            {{-- Print --}}
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>

            {{-- PDF --}}
            <a href="{{ route('invoices.pdf', $invoice) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 transition shadow-2xs">
                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh PDF
            </a>

            {{-- Send to Client (if draft) --}}
            @if($user->hasRole(['admin','member']) && $invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}"
                  data-confirm-submit="Kirim invoice {{ $invoice->invoice_number }} ke client?"
                  data-confirm-text="Client akan menerima notifikasi dan invoice diterbitkan resmi."
                  data-confirm-btn="Ya, Kirim Sekarang">
                @csrf @method('PUT')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Kirim ke Client
                </button>
            </form>
            @endif

            {{-- Mark Paid (if sent/overdue) --}}
            @if($user->hasRole(['admin','member']) && in_array($invoice->status, ['sent','overdue']))
            <form method="POST" action="{{ route('invoices.markPaid', $invoice) }}"
                  data-confirm-submit="Tandai invoice {{ $invoice->invoice_number }} Lunas?"
                  data-confirm-text="Status pembayaran akan diperbarui menjadi Lunas."
                  data-confirm-btn="Ya, Tandai Lunas">
                @csrf @method('PUT')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tandai Lunas
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- ── Invoice Document Card (Clean Corporate Paper Layout) ───────────── --}}
    <div class="print-card bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-md p-6 sm:p-10 space-y-8">

        {{-- Top Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-slate-100 dark:border-gray-700">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('flovig_icon.png') }}" alt="Flovig" class="w-9 h-9 object-contain">
                    <div>
                        <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">FLOVIG WORKSPACE</h2>
                        <p class="text-[11px] text-slate-400">Enterprise Project Management & Invoicing</p>
                    </div>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 space-y-0.5 mt-3">
                    <p class="font-medium text-slate-700 dark:text-slate-300">PT Flovig Solusi Digital</p>
                    <p>billing@flovig.com</p>
                </div>
            </div>

            <div class="sm:text-right space-y-2">
                <div class="flex items-center sm:justify-end gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $st['bg'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $st['dot'] }}"></span>
                        {{ $st['label'] }}
                    </span>
                </div>
                <h1 class="text-2xl font-mono font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $invoice->invoice_number }}
                </h1>
                @if($invoice->project)
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Proyek: <a href="{{ route('projects.show', $invoice->project) }}" class="font-bold text-blue-600 hover:underline">{{ $invoice->project->name }}</a>
                </p>
                @else
                <p class="text-xs text-slate-400">Tagihan Internal (Non-Proyek)</p>
                @endif
            </div>
        </div>

        {{-- Billed To & Dates Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/70 dark:bg-gray-800/50 p-5 rounded-xl border border-slate-100 dark:border-gray-700/60">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Tagihan Ditujukan Kepada</p>
                <div class="flex items-center gap-3">
                    @if($invoice->client && $invoice->client->avatar)
                        <img src="{{ Storage::url($invoice->client->avatar) }}" alt="{{ $invoice->client->name }}"
                             class="w-10 h-10 rounded-xl object-cover ring-2 ring-white dark:ring-gray-700">
                    @else
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                            {{ strtoupper(substr($invoice->client->name ?? 'C', 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $invoice->client->name ?? '—' }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $invoice->client->email ?? '' }}</p>
                    </div>
                </div>
            </div>

            <div class="sm:text-right space-y-1.5 text-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Jadwal & Jatuh Tempo</p>
                <p class="text-slate-600 dark:text-slate-300">
                    Tanggal Terbit: <strong class="text-slate-900 dark:text-white">{{ $invoice->issue_date->format('d M Y') }}</strong>
                </p>
                <p class="{{ $isOverdue ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-600 dark:text-slate-300' }}">
                    Jatuh Tempo: <strong class="{{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-900 dark:text-white' }}">{{ $invoice->due_date->format('d M Y') }}</strong>
                </p>
                @if($invoice->paid_at)
                <p class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center sm:justify-end gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Lunas: {{ $invoice->paid_at->format('d M Y') }}
                </p>
                @endif
            </div>
        </div>

        {{-- Line Items Table --}}
        <div>
            <div class="border border-slate-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-gray-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-gray-700">
                            <th class="px-5 py-3">Deskripsi Item & Jasa</th>
                            <th class="px-4 py-3 text-center w-24">Kuantitas</th>
                            <th class="px-5 py-3 text-right w-44">Harga Satuan</th>
                            <th class="px-5 py-3 text-right w-44">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-slate-700 dark:text-slate-200">
                        @foreach($invoice->items as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 font-medium leading-relaxed">{{ $item->description }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">{{ $item->quantity }}</td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-slate-600 dark:text-slate-400">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900 dark:text-white tabular-nums">
                                Rp {{ number_format($item->total, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals Summary Section --}}
            <div class="mt-5 flex justify-end">
                <div class="w-full sm:w-80 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500 dark:text-slate-400 px-2">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400 px-2">
                        <span>Pajak ({{ $invoice->tax }}%)</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">Rp {{ number_format($invoice->total - $invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 flex items-baseline justify-between text-blue-900 dark:text-blue-100">
                        <span class="font-extrabold uppercase tracking-wider text-xs">TOTAL TAGIHAN</span>
                        <span class="font-black text-lg sm:text-xl text-blue-700 dark:text-blue-400 tabular-nums">
                            Rp {{ number_format($invoice->total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes & Attachments --}}
        @if($invoice->notes || $invoice->attachment)
        <div class="pt-4 border-t border-slate-100 dark:border-gray-700 space-y-3">
            @if($invoice->notes)
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-800 text-xs text-slate-600 dark:text-slate-300">
                <p class="font-bold text-slate-800 dark:text-white mb-1">Catatan Pembayaran & Rekening:</p>
                <p class="whitespace-pre-line leading-relaxed">{{ $invoice->notes }}</p>
            </div>
            @endif

            @if($invoice->attachment)
            <div class="flex items-center gap-2 text-xs">
                <span class="text-slate-400">Dokumen Lampiran:</span>
                <a href="{{ $invoice->attachmentUrl() }}" target="_blank"
                   class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-800 hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    {{ basename($invoice->attachment) }}
                </a>
            </div>
            @endif
        </div>
        @endif

        {{-- Footer Watermark / Audit --}}
        <div class="pt-6 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-[10px] text-slate-400">
            <span>Diterbitkan melalui Flovig Enterprise Platform</span>
            <span>ID Dokumen: #{{ $invoice->id }} &bull; {{ $invoice->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

</div>
@endsection
