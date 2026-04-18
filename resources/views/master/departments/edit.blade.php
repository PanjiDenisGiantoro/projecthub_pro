@extends('layouts.app')
@section('title', 'Edit Departemen')
@section('page-title', 'Edit Departemen')

@section('content')
<div class="py-4 max-w-xl"
     x-data="{
         selectedCompany: '{{ old('company_filter', $department->division->branch->company_id) }}',
         selectedBranch: '{{ old('branch_filter', $department->division->branch_id) }}'
     }">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('departments.index') }}" class="hover:text-blue-600 transition-colors">Departemen</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">{{ $department->name }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('departments.update', $department) }}" class="space-y-5"
              data-confirm-submit="Perbarui data departemen?" data-confirm-btn="Ya, Perbarui">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                <p class="col-span-2 text-xs font-medium text-gray-500 -mb-1">Filter Lokasi (opsional)</p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Perusahaan</label>
                    <select x-model="selectedCompany" @change="selectedBranch = ''"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">— Semua —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $department->division->branch->company_id == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Branch</label>
                    <select x-model="selectedBranch"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">— Semua —</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                    x-show="!selectedCompany || selectedCompany == '{{ $branch->company_id }}'"
                                    {{ $department->division->branch_id == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divisi <span class="text-red-500">*</span></label>
                <select name="division_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('division_id') border-red-400 @enderror">
                    <option value="">— Pilih Divisi —</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}"
                                x-show="(!selectedBranch || selectedBranch == '{{ $div->branch_id }}') && (!selectedCompany || selectedCompany == '{{ $div->branch->company_id }}')"
                                {{ old('division_id', $department->division_id) == $div->id ? 'selected' : '' }}>
                            {{ $div->branch->company->name }} / {{ $div->branch->name }} / {{ $div->name }}
                        </option>
                    @endforeach
                </select>
                @error('division_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Departemen <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                    <input type="text" name="code" value="{{ old('code', $department->code) }}" maxlength="50"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono uppercase @error('code') border-red-400 @enderror">
                    @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Departemen</label>
                    <select name="head_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak ada —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('head_id', $department->head_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('description', $department->description) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       {{ old('is_active', $department->is_active) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Departemen Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Perbarui</button>
                <a href="{{ route('departments.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
