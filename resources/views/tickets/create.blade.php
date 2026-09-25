@extends('layouts.app')
@section('title', 'Buat Tiket — ' . $project->name)
@section('page-title', 'Buat Tiket Baru')

@section('content')
<div class="py-4 w-full">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('tickets.index', $project) }}" class="hover:text-blue-600">Tickets</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Buat Baru</span>
    </nav>

    <form method="POST" action="{{ route('tickets.store', $project) }}" class="fl-form" enctype="multipart/form-data"
          x-data="{ submitting: false }"
          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Detail Tiket</h3>
                <p class="fl-section-desc">Jelaskan masalahnya selengkap mungkin: langkah reproduksi, hasil yang diharapkan, dan yang terjadi.</p>
            </div>
            <div class="fl-fields">
                <div class="fl-span-2">
                    <label class="fl-label" for="title">Judul <span class="fl-req">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="Ringkasan singkat masalah"
                           class="fl-input @error('title') is-invalid @enderror">
                    @error('title') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
                <div class="fl-span-2">
                    <label class="fl-label" for="description">Deskripsi <span class="fl-req">*</span></label>
                    <textarea id="description" name="description" rows="5" required
                              class="fl-input @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Klasifikasi</h3>
                <p class="fl-section-desc">Membantu tim menentukan penanganan dan urutan pengerjaan.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="type">Tipe</label>
                    <select id="type" name="type" class="fl-input">
                        @foreach(['bug','issue','enhancement','security','performance'] as $t)
                            <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fl-label" for="priority">Prioritas</label>
                    <select id="priority" name="priority" class="fl-input">
                        @foreach(['critical','high','medium','low'] as $p)
                            <option value="{{ $p }}" {{ old('priority','medium') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fl-label" for="error_category">Kategori Error</label>
                    <select id="error_category" name="error_category" class="fl-input">
                        <option value="">— Pilih Kategori —</option>
                        @foreach(['frontend'=>'Frontend','backend'=>'Backend','database'=>'Database','api'=>'API','infrastructure'=>'Infrastructure','integration'=>'Integrasi Pihak Ketiga','configuration'=>'Konfigurasi','other'=>'Lainnya'] as $val => $label)
                            <option value="{{ $val }}" {{ old('error_category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fl-label" for="milestone_id">Milestone</label>
                    <select id="milestone_id" name="milestone_id" class="fl-input">
                        <option value="">— Tanpa milestone —</option>
                        @foreach($milestones as $m)
                            <option value="{{ $m->id }}" {{ old('milestone_id') == $m->id ? 'selected' : '' }}>{{ $m->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Lampiran</h3>
                <p class="fl-section-desc">Screenshot, log, atau file pendukung lain.</p>
            </div>
            <div class="fl-fields">
                <div class="fl-span-2">
                    <input type="file" name="attachments[]" multiple class="fl-file">
                    <p class="fl-help">Maks. 5 file, masing-masing maks. 10MB.</p>
                    @error('attachments') <p class="fl-error">{{ $message }}</p> @enderror
                    @error('attachments.*') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="fl-actions">
            <a href="{{ route('tickets.index', $project) }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" :disabled="submitting" class="fl-btn fl-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Buat Tiket</span>
                <span x-show="submitting" x-cloak>Membuat...</span>
            </button>
        </div>
    </form>
</div>
@endsection
