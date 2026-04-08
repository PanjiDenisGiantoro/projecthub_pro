@extends('layouts.app')
@section('title', 'Customer Requests')
@section('page-title', 'Customer Requests')

@section('content')
<div class="py-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-wrap flex-1">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['submitted','under_review','approved','rejected','in_progress','done'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="project_id" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Proyek</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </form>
        @if(auth()->user()->hasRole('customer'))
        <a href="{{ route('requests.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Request
        </a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left">Tipe</th>
                    <th class="px-4 py-3 text-left">Prioritas</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                @php
                    $sc = ['submitted'=>'bg-blue-100 text-blue-700','under_review'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','in_progress'=>'bg-purple-100 text-purple-700','done'=>'bg-gray-100 text-gray-700'];
                    $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">{{ $req->title }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ ucfirst(str_replace('_',' ',$req->type)) }}</td>
                    <td class="px-4 py-3"><span class="badge {{ $pc[$req->priority] ?? '' }}">{{ ucfirst($req->priority) }}</span></td>
                    <td class="px-4 py-3"><span class="badge {{ $sc[$req->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$req->status)) }}</span></td>
                    <td class="px-4 py-3 text-gray-600">{{ $req->project->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $req->customer->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('requests.show', $req) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Belum ada request.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($requests->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
