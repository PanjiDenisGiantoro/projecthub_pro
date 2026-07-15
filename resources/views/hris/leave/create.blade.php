@extends('layouts.app')
@section('title', 'Ajukan Cuti')
@section('page-title', 'Ajukan Cuti')

@section('content')
<div class="max-w-2xl mx-auto pt-5 space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('hris.leave.index') }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Ajukan Cuti / Izin</h1>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('hris.leave.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
              x-data="{ submitting: false }"
              @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Cuti *</label>
                <select name="leave_type_id" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                    <option value="">Pilih jenis cuti...</option>
                    @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                        {{ $type->name }}
                        @if($type->has_balance && $type->default_quota > 0) (saldo) @endif
                    </option>
                    @endforeach
                </select>
                @error('leave_type_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai *</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir *</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" required
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alasan *</label>
                <textarea name="reason" rows="3" required placeholder="Masukkan alasan cuti..."
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none resize-none">{{ old('reason') }}</textarea>
                @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran <span class="text-gray-400">(opsional)</span></label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-violet-50 file:text-violet-700 file:font-medium">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="submitting"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    <span x-show="!submitting">Kirim Pengajuan</span>
                    <span x-show="submitting" x-cloak>Mengirim...</span>
                </button>
                <a href="{{ route('hris.leave.index') }}"
                   class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
