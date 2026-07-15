@extends('layouts.app')
@section('title', 'Anggaran — ' . $project->name)
@section('page-title', 'Budget Tracking')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Anggaran</span>
    </nav>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Budget</p>
            <p class="text-xl font-bold text-gray-800">Rp {{ number_format($summary['budget'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Pengeluaran</p>
            <p class="text-xl font-bold text-red-600">Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Pemasukan</p>
            <p class="text-xl font-bold text-green-600">Rp {{ number_format($summary['income'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Sisa Anggaran</p>
            <p class="text-xl font-bold {{ $summary['balance'] < 0 ? 'text-red-600' : 'text-blue-600' }}">
                Rp {{ number_format($summary['balance'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Budget usage bar --}}
    @if($summary['budget'] > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <div class="flex justify-between text-sm mb-2">
            <span class="font-medium text-gray-700">Penggunaan Anggaran</span>
            <span class="{{ $summary['percent'] >= 90 ? 'text-red-600 font-bold' : 'text-gray-600' }}">{{ $summary['percent'] }}%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3">
            <div class="h-3 rounded-full transition-all {{ $summary['percent'] >= 90 ? 'bg-red-500' : ($summary['percent'] >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                 style="width: {{ min(100, $summary['percent']) }}%"></div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        {{-- By Category chart --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Pengeluaran per Kategori</h3>
            @if($byCategory->isEmpty())
            <p class="text-sm text-gray-400">Belum ada data.</p>
            @else
            <canvas id="categoryChart" height="220"></canvas>
            @endif
        </div>

        {{-- Add entry form --}}
        <div class="lg:col-span-2">
            @if(!auth()->user()->hasRole('customer'))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Tambah Entri</h3>
                <form method="POST" action="{{ route('budget.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipe *</label>
                        <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal *</label>
                        <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kategori *</label>
                        <input type="text" name="category" placeholder="e.g. Labor, Software..." required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah (Rp) *</label>
                        <input type="number" name="amount" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi *</label>
                        <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Referensi</label>
                        <input type="text" name="reference" placeholder="No. Invoice / PO" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- Entries Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Riwayat Transaksi</h3>
        </div>
        @if($entries->isEmpty())
        <div class="text-center py-10 text-gray-400">
            <p class="font-medium">Belum ada transaksi.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jumlah</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Oleh</th>
                    @if(!auth()->user()->hasRole('customer'))
                    <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($entries as $entry)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $entry->entry_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $entry->description }}</p>
                        @if($entry->reference)<p class="text-xs text-gray-400">{{ $entry->reference }}</p>@endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $entry->category }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $entry->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $entry->type === 'income' ? '+' : '-' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $entry->creator?->name }}</td>
                    @if(!auth()->user()->hasRole('customer'))
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('budget.destroy', [$project, $entry]) }}"
                              data-confirm-delete="{{ $entry->description }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@push('scripts')
<script>
@if(!$byCategory->isEmpty())
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: @json($byCategory->keys()),
        datasets: [{ data: @json($byCategory->values()), backgroundColor: ['#3B82F6','#F59E0B','#EF4444','#10B981','#8B5CF6','#F97316','#06B6D4','#EC4899'], borderWidth: 0 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});
@endif
</script>
@endpush
@endsection
