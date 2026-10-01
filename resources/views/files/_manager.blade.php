{{-- File Manager project (folder + upload + daftar file). Dipakai di tab Files
     (/projects/{id}/files). Butuh: $project, $files, $folders, $folderTree. --}}
@php
    $canManageFiles = !auth()->user()->hasRole('client');
    $totalSize = $files->sum('size');
    $humanTotal = $totalSize >= 1048576 ? round($totalSize / 1048576, 1) . ' MB' : ($totalSize >= 1024 ? round($totalSize / 1024, 1) . ' KB' : $totalSize . ' B');

    // Label + warna ikon per tipe file.
    $typeMeta = function ($file) {
        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        [$color] = match (true) {
            $ext === 'pdf' => ['bg-red-50 text-red-600'],
            in_array($ext, ['doc', 'docx', 'txt', 'md', 'rtf']) => ['bg-blue-50 text-blue-600'],
            in_array($ext, ['xls', 'xlsx', 'csv']) => ['bg-green-50 text-green-600'],
            in_array($ext, ['ppt', 'pptx']) => ['bg-orange-50 text-orange-600'],
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => ['bg-purple-50 text-purple-600'],
            in_array($ext, ['zip', 'rar', '7z']) => ['bg-amber-50 text-amber-600'],
            in_array($ext, ['mp4', 'avi', 'mov', 'mp3', 'wav']) => ['bg-pink-50 text-pink-600'],
            default => ['bg-gray-100 text-gray-500'],
        };
        return ['ext' => $ext !== '' ? strtoupper(substr($ext, 0, 4)) : 'FILE', 'color' => $color];
    };
@endphp

<div class="space-y-4"
     x-data="{
        activeFolder: 'All',
        search: '',
        view: (() => { try { return localStorage.getItem('pf_view') || 'grid' } catch (e) { return 'grid' } })(),
        showUpload: false,
        showNewFolder: false,
        newFolderName: '',
        uploadFolder: '',
        moveFile: null,
        moveFolder: '',
        setView(v) { this.view = v; try { localStorage.setItem('pf_view', v) } catch (e) {} },
        inFolder(folder) { return this.activeFolder === 'All' || folder === this.activeFolder || folder.startsWith(this.activeFolder + '/') },
        matches(name) { return this.search === '' || name.toLowerCase().includes(this.search.toLowerCase()) },
        visible(folder, name) { return this.inFolder(folder) && this.matches(name) },
        get crumbs() {
            if (this.activeFolder === 'All') return [];
            const parts = this.activeFolder.split('/');
            return parts.map((p, i) => ({ name: p, path: parts.slice(0, i + 1).join('/') }));
        },
        openUpload() { this.showUpload = true; this.uploadFolder = this.activeFolder === 'All' ? '' : this.activeFolder },
     }">

    {{-- Toolbar --}}
    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 p-4 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </span>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Files</h2>
                <p class="text-xs text-gray-500">{{ $files->count() }} file · {{ $folders->count() }} folder · {{ $humanTotal }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                <input type="search" x-model="search" placeholder="Cari file..."
                       class="w-48 sm:w-64 pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="inline-flex rounded-lg border border-gray-200 p-0.5">
                <button type="button" @click="setView('grid')" title="Tampilan grid"
                        :class="view === 'grid' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600'" class="p-1.5 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </button>
                <button type="button" @click="setView('list')" title="Tampilan list"
                        :class="view === 'list' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600'" class="p-1.5 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            @if($canManageFiles)
            <button type="button" @click="showUpload ? showUpload = false : openUpload()"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-text="showUpload ? 'Tutup' : 'Upload File'"></span>
            </button>
            @endif
        </div>
    </div>

    {{-- Upload (drag & drop) --}}
    @if($canManageFiles)
    <div x-show="showUpload" x-cloak class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 p-5"
         x-data="{
            picked: [],
            dragging: false,
            sync(list) { this.picked = Array.from(list).map(f => ({ name: f.name, size: f.size })) },
            drop(e) {
                this.dragging = false;
                if (!e.dataTransfer.files.length) return;
                $refs.fileInput.files = e.dataTransfer.files;
                this.sync(e.dataTransfer.files);
            },
            fmt(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b >= 1024 ? (b / 1024).toFixed(1) + ' KB' : b + ' B') },
         }">
        <form method="POST" action="{{ route('project.files.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
                   :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400'"
                   class="flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-xl px-6 py-8 text-center cursor-pointer transition-colors">
                <span class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </span>
                <span class="text-sm font-medium text-gray-700">Tarik & lepas file ke sini, atau <span class="text-blue-600">pilih file</span></span>
                <span class="text-xs text-gray-400">Semua tipe file, maks 50 MB per file</span>
                <input type="file" name="files[]" multiple required x-ref="fileInput" @change="sync($event.target.files)" class="sr-only">
            </label>

            <template x-if="picked.length">
                <ul class="divide-y divide-gray-100 border border-gray-100 rounded-xl max-h-48 overflow-y-auto">
                    <template x-for="f in picked" :key="f.name">
                        <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <span class="truncate text-gray-700" x-text="f.name"></span>
                            <span class="text-xs text-gray-400 shrink-0" x-text="fmt(f.size)"></span>
                        </li>
                    </template>
                </ul>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Folder tujuan</label>
                    <input type="text" name="folder" x-model="uploadFolder" placeholder="General atau Docs/Kontrak" list="pf-folder-list"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-[11px] text-gray-400 mt-1">Pakai "/" untuk subfolder, mis. Docs/Kontrak/2024</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                    <input type="text" name="description" placeholder="Opsional"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" @click="showUpload = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                <button type="submit" :disabled="!picked.length"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Upload <span x-show="picked.length" x-text="'(' + picked.length + ')'"></span>
                </button>
            </div>
        </form>
    </div>
    <datalist id="pf-folder-list">
        @foreach($folders as $f)<option value="{{ $f }}">@endforeach
    </datalist>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-start">
        {{-- Folder --}}
        <div class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 p-3 lg:sticky lg:top-4">
            <div class="flex items-center justify-between px-1 mb-2">
                <p class="text-[10.5px] font-bold text-gray-400 uppercase tracking-wider">Folder</p>
                @if($canManageFiles)
                <button type="button" @click="showNewFolder = !showNewFolder" title="Buat folder baru"
                        class="p-1 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
                @endif
            </div>

            @if($canManageFiles)
            <div x-show="showNewFolder" x-cloak class="mb-2 px-1">
                <form method="POST" action="{{ route('project.files.folders.store', $project) }}" class="flex gap-1.5">
                    @csrf
                    <input type="hidden" name="parent" :value="activeFolder === 'All' ? '' : activeFolder">
                    <input type="text" name="name" x-model="newFolderName" required placeholder="Nama folder"
                           class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-2.5 rounded-lg transition-colors">Buat</button>
                </form>
                <p class="text-[11px] text-gray-400 mt-1" x-show="activeFolder !== 'All'">Di dalam: <span x-text="activeFolder"></span></p>
            </div>
            @endif

            <button type="button" @click="activeFolder = 'All'"
                    :class="activeFolder === 'All' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50'"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-sm flex items-center gap-2 transition-colors mb-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="flex-1">Semua file</span>
                <span class="text-xs text-gray-400">{{ $files->count() }}</span>
            </button>
            <div class="space-y-0.5">
                @forelse($folderTree as $name => $node)
                    <x-file-folder-node :name="$name" :node="$node" :files="$files" />
                @empty
                    <p class="px-2.5 py-2 text-xs text-gray-400">Belum ada folder.</p>
                @endforelse
            </div>
        </div>

        {{-- Daftar file --}}
        <div class="lg:col-span-3 space-y-3 min-w-0">
            {{-- Breadcrumb folder --}}
            <div class="flex items-center gap-1.5 text-sm flex-wrap">
                <button type="button" @click="activeFolder = 'All'"
                        :class="activeFolder === 'All' ? 'text-gray-900 font-semibold' : 'text-gray-500 hover:text-blue-600'">Semua file</button>
                <template x-for="c in crumbs" :key="c.path">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <button type="button" @click="activeFolder = c.path" x-text="c.name"
                                :class="c.path === activeFolder ? 'text-gray-900 font-semibold' : 'text-gray-500 hover:text-blue-600'"></button>
                    </span>
                </template>
            </div>

            @if($files->isEmpty())
            <div class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 py-16 px-6 text-center">
                <span class="w-14 h-14 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </span>
                <p class="mt-3 font-medium text-gray-700">Belum ada file di project ini</p>
                <p class="text-sm text-gray-400 mt-1">Simpan dokumen, desain, dan berkas project di satu tempat.</p>
                @if($canManageFiles)
                <button type="button" @click="openUpload()" class="mt-4 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                    Upload file pertama
                </button>
                @endif
            </div>
            @else
            {{-- Grid --}}
            <div x-show="view === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($files as $file)
                @php $meta = $typeMeta($file); @endphp
                <div x-show="visible(@js($file->folder), @js($file->original_name))"
                     class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 overflow-hidden flex flex-col hover:border-blue-300 transition-colors">
                    <a href="{{ $file->url() }}" target="_blank" class="h-28 flex items-center justify-center bg-gray-50 border-b border-gray-100">
                        @if($file->isImage())
                        <img src="{{ $file->url() }}" alt="" loading="lazy" class="w-full h-full object-cover">
                        @else
                        <span class="w-12 h-14 rounded-lg flex items-center justify-center text-xs font-bold {{ $meta['color'] }}">{{ $meta['ext'] }}</span>
                        @endif
                    </a>
                    <div class="p-3 flex-1 flex flex-col gap-1 min-w-0">
                        <a href="{{ $file->url() }}" target="_blank" class="text-sm font-medium text-gray-900 truncate hover:text-blue-600" title="{{ $file->original_name }}">{{ $file->original_name }}</a>
                        <p class="text-xs text-gray-400 truncate">{{ $file->humanSize() }} · {{ $file->created_at?->format('d M Y') }}</p>
                        <p class="text-xs text-gray-500 truncate flex items-center gap-1" title="{{ $file->folder }}">
                            <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            {{ $file->folder ?: 'General' }}
                        </p>
                    </div>
                    <div class="px-2 pb-2 flex items-center justify-end gap-0.5">
                        @include('files._file-actions', ['file' => $file])
                    </div>
                </div>
                @endforeach
            </div>

            {{-- List --}}
            <div x-show="view === 'list'" x-cloak class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Folder</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Ukuran</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($files as $file)
                        @php $meta = $typeMeta($file); @endphp
                        <tr x-show="visible(@js($file->folder), @js($file->original_name))" class="hover:bg-gray-50">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-[10px] font-bold {{ $meta['color'] }}">{{ $meta['ext'] }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ $file->url() }}" target="_blank" class="block font-medium text-gray-900 truncate hover:text-blue-600" title="{{ $file->original_name }}">{{ $file->original_name }}</a>
                                        <p class="text-xs text-gray-400 truncate">{{ $file->uploader?->name ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $file->folder ?: 'General' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500 whitespace-nowrap">{{ $file->humanSize() }}</td>
                            <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $file->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-0.5">
                                    @include('files._file-actions', ['file' => $file])
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tidak ada yang cocok dengan folder/pencarian --}}
            <div x-show="!@js($files->map(fn ($f) => ['folder' => $f->folder, 'name' => $f->original_name])->values()).some(f => visible(f.folder, f.name))" x-cloak
                 class="bg-white rounded-2xl shadow-2xs border border-gray-200/90 py-12 text-center">
                <p class="text-sm font-medium text-gray-600" x-text="search ? 'Tidak ada file yang cocok dengan pencarian.' : 'Folder ini masih kosong.'"></p>
                @if($canManageFiles)
                <button type="button" x-show="!search" @click="openUpload()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Upload ke folder ini</button>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Pindah folder --}}
    @if($canManageFiles)
    <div x-show="moveFile" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="moveFile = null">
        <div class="absolute inset-0 bg-black/40" @click="moveFile = null"></div>
        <form method="POST" :action="moveFile ? moveFile.action : ''" class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4">
            @csrf @method('PATCH')
            <div>
                <p class="text-base font-semibold text-gray-900">Pindahkan file</p>
                <p class="text-sm text-gray-500 truncate" x-text="moveFile ? moveFile.name : ''"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Folder tujuan</label>
                <input type="text" name="folder" x-model="moveFolder" required list="pf-folder-list" placeholder="General atau Docs/Kontrak"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="moveFile = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Pindahkan</button>
            </div>
        </form>
    </div>
    @endif
</div>
