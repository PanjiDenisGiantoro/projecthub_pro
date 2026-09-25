@extends('layouts.app')
@section('title', 'Edit Unit Organisasi')
@section('page-title', 'Edit Unit Organisasi')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@section('content')
<div class="py-4 w-full">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('organization-units.index', ['company_id' => $organizationUnit->company_id]) }}" class="hover:text-blue-600 transition-colors">Organisasi</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Edit</span>
    </div>

    <form method="POST" action="{{ route('organization-units.update', $organizationUnit) }}" class="fl-form"
          data-confirm-submit="Simpan perubahan unit organisasi?" data-confirm-btn="Ya, Simpan">
        @csrf @method('PUT')
        @include('master.organization-units._form', ['organizationUnit' => $organizationUnit])
        <div class="fl-actions">
            <a href="{{ route('organization-units.index', ['company_id' => $organizationUnit->company_id]) }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#sel-parent').select2({ placeholder: '— Tanpa Parent (Level 1) —', allowClear: true, width: '100%' });
    $('#sel-head').select2({ placeholder: '— Tidak ada —', allowClear: true, width: '100%' });
    $('#sel-parent').on('change', updateColorPreview);
    updateColorPreview();
});

const ORG_COLOR_PALETTE = ['#1d4ed8', '#7c3aed', '#db2777', '#d97706', '#0891b2', '#16a34a'];

function toggleColorAuto(checkbox) {
    document.getElementById('color-input').disabled = checkbox.checked;
    updateColorPreview();
}

function currentOrgLevel() {
    const sel = document.getElementById('sel-parent');
    const opt = sel.options[sel.selectedIndex];
    const parentLevel = opt && opt.value ? parseInt(opt.dataset.level || '1', 10) : 0;
    return parentLevel + 1;
}

function readableTextColor(hex) {
    const c = hex.replace('#', '');
    if (c.length !== 6) return '#ffffff';
    const r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.6 ? '#111827' : '#ffffff';
}

function updateColorPreview() {
    const bar   = document.getElementById('color-preview-bar');
    const label = document.getElementById('color-preview-label');
    if (!bar || !label) return;

    const auto  = document.getElementById('color-auto').checked;
    const level = currentOrgLevel();
    const color = auto ? ORG_COLOR_PALETTE[(level - 1) % ORG_COLOR_PALETTE.length] : document.getElementById('color-input').value;

    bar.style.backgroundColor = color;
    label.style.color = readableTextColor(color);
    label.textContent = auto
        ? `Otomatis — Level ${level} (${color})`
        : `Warna custom (${color})`;
}

document.getElementById('color-input').addEventListener('input', updateColorPreview);
</script>
@endpush
