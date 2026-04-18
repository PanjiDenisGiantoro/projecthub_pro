@extends('layouts.app')
@section('title', 'Risk Register — ' . $project->name)
@section('page-title', 'Risk Register')

@section('content')
<div class="py-4" x-data="{showForm:false, editRisk:null}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Risk Register</span>
    </nav>

    <div class="flex justify-between items-center mb-4">
        <div class="flex gap-3">
            <span class="text-sm text-gray-500">{{ $risks->count() }} risiko terdaftar</span>
        </div>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Tambah Risiko'"></span>
        </button>
        @endif
    </div>

    {{-- Risk Matrix --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Risk Matrix</h3>
            <canvas id="riskMatrix" height="280"></canvas>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan</h3>
            @php
                $byLevel = $risks->groupBy(fn($r) => $r->level());
                $levels = ['critical'=>'Kritis','high'=>'Tinggi','medium'=>'Sedang','low'=>'Rendah'];
                $levelColors = ['critical'=>'text-red-600','high'=>'text-orange-600','medium'=>'text-yellow-600','low'=>'text-green-600'];
            @endphp
            @foreach($levels as $lvl => $label)
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm {{ $levelColors[$lvl] }} font-medium">{{ $label }}</span>
                <span class="text-sm font-bold text-gray-700">{{ $byLevel->get($lvl, collect())->count() }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Add Form --}}
    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Tambah Risiko Baru</h4>
        <form method="POST" action="{{ route('risks.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori *</label>
                <select name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['technical','schedule','resource','budget','external','other'] as $c)
                    <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status *</label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['open','mitigated','accepted','closed'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Probabilitas (1-5) *</label>
                <input type="number" name="probability" min="1" max="5" value="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dampak (1-5) *</label>
                <input type="number" name="impact" min="1" max="5" value="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Owner</label>
                <input type="text" name="owner" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Rencana Mitigasi</label>
                <textarea name="mitigation_plan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Risk Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($risks->isEmpty())
        <div class="text-center py-10 text-gray-400">
            <p class="text-3xl mb-2">⚠️</p>
            <p class="font-medium text-gray-500">Belum ada risiko terdaftar.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Judul</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">P×I</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Level</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Owner</th>
                    @if(!auth()->user()->hasRole('customer'))
                    <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($risks as $risk)
                @php
                    $levelBadge = match($risk->level()) {
                        'critical' => 'bg-red-100 text-red-700',
                        'high'     => 'bg-orange-100 text-orange-700',
                        'medium'   => 'bg-yellow-100 text-yellow-700',
                        default    => 'bg-green-100 text-green-700',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $risk->title }}</p>
                        @if($risk->description)
                        <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($risk->description, 60) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 capitalize">{{ $risk->category }}</td>
                    <td class="px-4 py-3 text-center font-bold text-gray-700">{{ $risk->score() }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $levelBadge }}">{{ ucfirst($risk->level()) }}</span>
                    </td>
                    <td class="px-4 py-3 capitalize text-gray-600">{{ $risk->status }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $risk->owner ?? '—' }}</td>
                    @if(!auth()->user()->hasRole('customer'))
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('risks.destroy', [$project, $risk]) }}"
                              data-confirm-delete="{{ $risk->title }}">
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
const matrixData = @json($risks->map(fn($r) => ['x'=>$r->probability,'y'=>$r->impact,'label'=>$r->title,'score'=>$r->score()]));

new Chart(document.getElementById('riskMatrix'), {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Risiko',
            data: matrixData.map(r => ({ x: r.x, y: r.y })),
            backgroundColor: matrixData.map(r => r.score >= 15 ? '#EF4444' : r.score >= 8 ? '#F97316' : r.score >= 4 ? '#EAB308' : '#22C55E'),
            pointRadius: 10,
            pointHoverRadius: 12,
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { min: 0.5, max: 5.5, title: { display: true, text: 'Probabilitas' }, ticks: { stepSize: 1 } },
            y: { min: 0.5, max: 5.5, title: { display: true, text: 'Dampak' }, ticks: { stepSize: 1 } }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => {
                        const r = matrixData[ctx.dataIndex];
                        return `${r.label} (P:${r.x} × I:${r.y} = ${r.score})`;
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection
