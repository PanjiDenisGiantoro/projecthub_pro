@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoice & Billing')

@section('content')
<div class="py-4">
    <div class="flex justify-between items-center mb-4">
        <form method="GET" class="flex gap-2">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                <option value="">Semua Status</option>
                @foreach(['draft','sent','paid','overdue','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
        @if(auth()->user()->hasRole(['admin','manager']))
        <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Invoice
        </a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">No. Invoice</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Total</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Jatuh Tempo</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invoices as $inv)
                @php $sc = ['draft'=>'bg-gray-100 text-gray-700','sent'=>'bg-blue-100 text-blue-700','paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','cancelled'=>'bg-gray-100 text-gray-500']; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-medium text-gray-800">{{ $inv->invoice_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $inv->project->name ?? 'Internal' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $inv->client->name }}</td>
                    <td class="px-4 py-3 font-medium">Rp {{ number_format($inv->total,0,',','.') }}</td>
                    <td class="px-4 py-3"><span class="badge {{ $sc[$inv->status] ?? '' }}">{{ ucfirst($inv->status) }}</span></td>
                    <td class="px-4 py-3 {{ $inv->status === 'overdue' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        {{ $inv->due_date->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('invoices.show', $inv) }}" class="text-violet-600 hover:text-violet-800 text-sm font-medium">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection
