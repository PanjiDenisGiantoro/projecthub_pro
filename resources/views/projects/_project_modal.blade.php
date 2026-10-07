{{-- Project Create & Edit Modal Component --}}
<div x-data="projectModalComponent()" @open-project-modal.window="handleOpenEvent($event)"
    @keydown.escape.window="if (isOpen) closeModal()" class="relative">

    <template x-teleport="body">
        <div x-show="isOpen" x-cloak class="relative z-[100]">
            {{-- Backdrop --}}
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] w-screen h-screen bg-slate-900/60 backdrop-blur-xs"></div>

            {{-- Modal Wrapper --}}
            <div x-show="isOpen" @click.self="closeModal()"
                class="fixed inset-0 z-[101] w-screen h-screen overflow-y-auto p-3 sm:p-6 flex items-center justify-center">

                {{-- Dialog Card --}}
                <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-2xl max-h-[92vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/90 dark:border-slate-800 overflow-hidden text-slate-800 dark:text-slate-100"
                    style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">

                    {{-- Header --}}
                    <div
                        class="px-6 py-4.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                :class="mode === 'create' ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400'">
                                <template x-if="mode === 'create'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </template>
                                <template x-if="mode === 'edit'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                </template>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight"
                                    x-text="mode === 'create' ? 'Create New Project' : 'Edit Project'"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                    x-text="mode === 'create' ? 'Tambahkan proyek baru dan tentukan tim pelaksananya' : 'Perbarui data dan preferensi proyek'">
                                </p>
                            </div>
                        </div>

                        <button type="button" @click="closeModal()"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Form Body --}}
                    <form :action="actionUrl" method="POST" enctype="multipart/form-data" @submit="handleSubmit($event)"
                        class="flex-1 flex flex-col min-h-0">
                        @csrf
                        <template x-if="mode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="redirect_to" :value="redirectTo">

                        {{-- Hidden inputs for images marked as removed --}}
                        <template x-for="img in existingImages.filter(i => i.removed)" :key="img.path">
                            <input type="hidden" name="remove_images[]" :value="img.path">
                        </template>

                        <div class="flex-1 overflow-y-auto p-6 space-y-5">

                            @if(isset($errors) && $errors->any())
                                <div
                                    class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 rounded-xl text-xs text-red-600 dark:text-red-400">
                                    <p class="font-semibold mb-1">Terdapat kesalahan pengisian:</p>
                                    <ul class="list-disc list-inside space-y-0.5">
                                        @foreach($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Project Name --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Project Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" x-model="formData.name" required maxlength="255"
                                    placeholder="Misal: Redesign Website & Mobile App"
                                    class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Deskripsi Proyek
                                </label>
                                <textarea name="description" x-model="formData.description" rows="3"
                                    placeholder="Tuliskan gambaran ringkas tentang tujuan dan ruang lingkup proyek..."
                                    class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none transition"></textarea>
                            </div>

                            {{-- Client & Lead PIC --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Custom Client Select --}}
                                <div x-data="{ open: false, search: '' }">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Client</label>
                                    <input type="hidden" name="client_id" :value="formData.client_id">

                                    <div class="relative">
                                        <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-medium hover:border-gray-400 dark:hover:border-slate-600 transition cursor-pointer">
                                            <span class="flex items-center gap-2 truncate">
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span :class="formData.client_id ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400'"
                                                      x-text="getClientName()"></span>
                                            </span>
                                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-150 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.outside="open = false; search = ''" x-cloak
                                             class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 py-1.5 z-50 max-h-56 overflow-y-auto space-y-0.5">
                                            <div class="p-1.5 border-b border-gray-100 dark:border-slate-700 sticky top-0 bg-white dark:bg-slate-800">
                                                <input type="text" x-model="search" placeholder="Cari client..."
                                                       class="w-full px-2.5 py-1 text-xs border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <button type="button" @click="formData.client_id = ''; open = false"
                                                    class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                                    :class="!formData.client_id ? 'bg-blue-50/70 text-blue-600 font-bold' : 'text-slate-600 dark:text-slate-300'">
                                                <span>-- Tanpa Klien (Internal) --</span>
                                                <svg x-show="!formData.client_id" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <template x-for="c in clientsList.filter(item => !search || item.name.toLowerCase().includes(search.toLowerCase()))" :key="c.id">
                                                <button type="button" @click="formData.client_id = c.id; open = false"
                                                        class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                                        :class="String(formData.client_id) === String(c.id) ? 'bg-blue-50/70 text-blue-600 font-bold' : 'text-slate-700 dark:text-slate-300'">
                                                    <span class="truncate" x-text="c.name"></span>
                                                    <svg x-show="String(formData.client_id) === String(c.id)" class="w-3.5 h-3.5 text-blue-600 shrink-0 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Custom Manager Select --}}
                                <div x-data="{ open: false, search: '' }">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Project Lead (PIC)</label>
                                    <input type="hidden" name="manager_id" :value="formData.manager_id">

                                    <div class="relative">
                                        <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-medium hover:border-gray-400 dark:hover:border-slate-600 transition cursor-pointer">
                                            <span class="flex items-center gap-2 truncate">
                                                <template x-if="formData.manager_id">
                                                    <span class="w-5 h-5 rounded-full text-white text-[10px] flex items-center justify-center font-bold shrink-0"
                                                          :style="'background-color:' + getAvatarBg(getManagerName())"
                                                          x-text="getInitials(getManagerName())"></span>
                                                </template>
                                                <template x-if="!formData.manager_id">
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </template>
                                                <span :class="formData.manager_id ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400'"
                                                      x-text="getManagerName()"></span>
                                            </span>
                                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-150 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.outside="open = false; search = ''" x-cloak
                                             class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 py-1.5 z-50 max-h-56 overflow-y-auto space-y-0.5">
                                            <div class="p-1.5 border-b border-gray-100 dark:border-slate-700 sticky top-0 bg-white dark:bg-slate-800">
                                                <input type="text" x-model="search" placeholder="Cari project lead..."
                                                       class="w-full px-2.5 py-1 text-xs border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <button type="button" @click="formData.manager_id = ''; open = false"
                                                    class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                                    :class="!formData.manager_id ? 'bg-blue-50/70 text-blue-600 font-bold' : 'text-slate-600 dark:text-slate-300'">
                                                <span>-- Pilih Project Lead --</span>
                                                <svg x-show="!formData.manager_id" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <template x-for="m in managersList.filter(item => !search || item.name.toLowerCase().includes(search.toLowerCase()))" :key="m.id">
                                                <button type="button" @click="formData.manager_id = m.id; open = false"
                                                        class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                                        :class="String(formData.manager_id) === String(m.id) ? 'bg-blue-50/70 text-blue-600 font-bold' : 'text-slate-700 dark:text-slate-300'">
                                                    <div class="flex items-center gap-2 truncate">
                                                        <span class="w-5 h-5 rounded-full text-white text-[10px] flex items-center justify-center font-bold shrink-0"
                                                              :style="'background-color:' + getAvatarBg(m.name)"
                                                              x-text="getInitials(m.name)"></span>
                                                        <span class="truncate" x-text="m.name"></span>
                                                    </div>
                                                    <svg x-show="String(formData.manager_id) === String(m.id)" class="w-3.5 h-3.5 text-blue-600 shrink-0 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Dates --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tanggal
                                        Mulai</label>
                                    <input type="date" name="start_date" x-model="formData.start_date"
                                        class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tanggal
                                        Selesai (Deadline)</label>
                                    <input type="date" name="end_date" x-model="formData.end_date"
                                        class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            {{-- Budget & Status --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Budget
                                        (IDR)</label>
                                    <input type="number" name="budget" x-model="formData.budget" min="0" step="1000"
                                        placeholder="0"
                                        class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                                    <input type="hidden" name="status" :value="formData.status">

                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-medium hover:border-gray-400 dark:hover:border-slate-600 transition cursor-pointer">
                                            <span class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="getStatusObj().dot"></span>
                                                <span class="text-slate-800 dark:text-slate-200 font-semibold" x-text="getStatusObj().label"></span>
                                            </span>
                                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-150 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.outside="open = false" x-cloak
                                             class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 py-1.5 z-50 space-y-0.5">
                                            <template x-for="s in statusOptions" :key="s.value">
                                                <button type="button" @click="formData.status = s.value; open = false"
                                                        class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                                        :class="formData.status === s.value ? 'bg-blue-50/70 text-blue-600 font-bold' : 'text-slate-700 dark:text-slate-300'">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="s.dot"></span>
                                                        <div>
                                                            <span x-text="s.label"></span>
                                                            <span class="text-[11px] text-slate-400 ml-1.5" x-text="'(' + s.desc + ')'"></span>
                                                        </div>
                                                    </div>
                                                    <svg x-show="formData.status === s.value" class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Foto / Logo Proyek --}}
                            <div class="pt-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Foto / Logo Proyek
                                    </label>
                                    <span class="text-[11px] text-gray-400">Maks. 4 file &middot; JPG, PNG, WEBP</span>
                                </div>

                                {{-- Image preview container --}}
                                <div class="flex flex-wrap gap-3 items-center">
                                    {{-- Existing Images (in edit mode) --}}
                                    <template x-for="(img, idx) in existingImages" :key="img.path">
                                        <div class="relative w-20 h-20 rounded-xl overflow-hidden border group transition"
                                            :class="img.removed ? 'border-red-400 opacity-40 grayscale' : 'border-gray-200 dark:border-slate-700'">
                                            <img :src="img.url" class="w-full h-full object-cover">
                                            <button type="button" @click="img.removed = !img.removed"
                                                :title="img.removed ? 'Batal Hapus' : 'Hapus Foto'"
                                                class="absolute top-1 right-1 w-6 h-6 rounded-full text-white text-xs flex items-center justify-center transition shadow-sm"
                                                :class="img.removed ? 'bg-blue-600' : 'bg-red-600/90 hover:bg-red-600'">
                                                <span x-show="!img.removed">&times;</span>
                                                <span x-show="img.removed" x-cloak>&#8635;</span>
                                            </button>
                                            <span x-show="img.removed" x-cloak
                                                class="absolute bottom-1 left-1 right-1 text-[9px] bg-red-600 text-white text-center font-bold rounded py-0.5">Dihapus</span>
                                        </div>
                                    </template>

                                    {{-- Newly Selected Files --}}
                                    <template x-for="(preview, idx) in newPreviews" :key="idx">
                                        <div
                                            class="relative w-20 h-20 rounded-xl overflow-hidden border border-blue-300 ring-2 ring-blue-500/20 group">
                                            <img :src="preview" class="w-full h-full object-cover">
                                            <button type="button" @click="removeNewFile(idx)" title="Hapus"
                                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 hover:bg-black text-white text-xs flex items-center justify-center transition">
                                                &times;
                                            </button>
                                            <span
                                                class="absolute bottom-1 left-1 right-1 text-[9px] bg-blue-600 text-white text-center font-bold rounded py-0.5">Baru</span>
                                        </div>
                                    </template>

                                    {{-- Add More Button --}}
                                    <button type="button" x-show="totalImagesCount() < 4"
                                        @click="$refs.imagesInput.click()"
                                        class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-700 hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 text-gray-400 hover:text-blue-600 flex flex-col items-center justify-center text-xs gap-1 transition-all cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span class="text-[11px] font-medium">Unggah</span>
                                    </button>
                                </div>

                                <input type="file" name="images[]" x-ref="imagesInput"
                                    @change="handleFileSelect($event)" multiple accept="image/*" class="hidden">

                                <p x-show="imageError" x-cloak x-text="imageError" class="mt-2 text-xs text-red-500">
                                </p>
                            </div>

                        </div>

                        {{-- Footer Actions --}}
                        <div
                            class="px-6 py-4 bg-gray-50/80 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-slate-700 rounded-xl transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="submitting"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-sm hover:shadow disabled:opacity-50 cursor-pointer">
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin text-white" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                <span
                                    x-text="mode === 'create' ? (submitting ? 'Menyimpan...' : 'Simpan Proyek') : (submitting ? 'Memperbarui...' : 'Simpan Perubahan')"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function projectModalComponent() {
        return {
            isOpen: false,
            mode: 'create',
            actionUrl: '{{ route('projects.store') }}',
            redirectTo: 'index',
            submitting: false,
            formData: {
                id: null,
                name: '',
                description: '',
                client_id: '',
                manager_id: '',
                start_date: '',
                end_date: '',
                budget: '',
                status: 'active',
            },
            clientsList: @json($clients ?? []),
            managersList: @json($managers ?? []),
            statusOptions: [
                { value: 'draft', label: 'Draft', dot: 'bg-slate-400', desc: 'Perencanaan awal' },
                { value: 'active', label: 'Active', dot: 'bg-emerald-500', desc: 'Sedang berjalan' },
                { value: 'on_hold', label: 'On Hold', dot: 'bg-purple-500', desc: 'Tertunda sementara' },
                { value: 'completed', label: 'Completed', dot: 'bg-blue-600', desc: 'Telah selesai' },
                { value: 'cancelled', label: 'Cancelled', dot: 'bg-rose-500', desc: 'Dibatalkan' },
            ],

            getClientName() {
                if (!this.formData.client_id) return 'Pilih Klien';
                const found = this.clientsList.find(c => String(c.id) === String(this.formData.client_id));
                return found ? found.name : 'Pilih Klien';
            },

            getManagerName() {
                if (!this.formData.manager_id) return 'Pilih Project Lead';
                const found = this.managersList.find(m => String(m.id) === String(this.formData.manager_id));
                return found ? found.name : 'Pilih Project Lead';
            },

            getStatusObj() {
                return this.statusOptions.find(s => s.value === this.formData.status) || this.statusOptions[1];
            },

            getInitials(name) {
                if (!name) return '??';
                const parts = name.trim().split(' ');
                if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
                return name.substring(0, 2).toUpperCase();
            },

            getAvatarBg(name) {
                const colors = ['#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#06b6d4', '#10b981', '#f59e0b'];
                if (!name) return colors[0];
                let hash = 0;
                for (let i = 0; i < name.length; i++) {
                    hash = name.charCodeAt(i) + ((hash << 5) - hash);
                }
                return colors[Math.abs(hash) % colors.length];
            },

            existingImages: [],
            newFiles: [],
            newPreviews: [],
            imageError: '',

            handleOpenEvent(e) {
                const detail = e.detail || {};
                if (detail.mode === 'edit' && detail.project) {
                    this.openEdit(detail.project, detail.redirect || 'index');
                } else {
                    this.openCreate(detail.redirect || 'index');
                }
            },

            openCreate(redirect = 'index') {
                this.mode = 'create';
                this.actionUrl = '{{ route('projects.store') }}';
                this.redirectTo = redirect;
                this.formData = {
                    id: null,
                    name: '',
                    description: '',
                    client_id: '',
                    manager_id: '',
                    start_date: '{{ now()->format('Y-m-d') }}',
                    end_date: '{{ now()->addMonth()->format('Y-m-d') }}',
                    budget: '',
                    status: 'active',
                };
                this.existingImages = [];
                this.newFiles = [];
                this.newPreviews = [];
                this.imageError = '';
                this.submitting = false;
                this.isOpen = true;
            },

            openEdit(p, redirect = 'index') {
                this.mode = 'edit';
                this.actionUrl = '/projects/' + (p.slug || p.id);
                this.redirectTo = redirect;
                this.formData = {
                    id: p.id,
                    name: p.name || '',
                    description: p.description || '',
                    client_id: p.client_id || '',
                    manager_id: p.manager_id || '',
                    start_date: p.start_date ? p.start_date.substring(0, 10) : '',
                    end_date: p.end_date ? p.end_date.substring(0, 10) : '',
                    budget: p.budget ? Math.round(Number(p.budget)) : '',
                    status: p.status || 'active',
                };

                // Existing images
                const imgs = p.images || [];
                const urls = p.image_urls || [];
                this.existingImages = imgs.map((path, i) => ({
                    path: path,
                    url: urls[i] || ('/storage/' + path),
                    removed: false
                }));

                this.newFiles = [];
                this.newPreviews = [];
                this.imageError = '';
                this.submitting = false;
                this.isOpen = true;
            },

            closeModal() {
                this.isOpen = false;
                this.newPreviews.forEach(u => URL.revokeObjectURL(u));
                this.newFiles = [];
                this.newPreviews = [];
            },

            totalImagesCount() {
                const activeExisting = this.existingImages.filter(i => !i.removed).length;
                return activeExisting + this.newFiles.length;
            },

            handleFileSelect(e) {
                const selected = Array.from(e.target.files);
                const activeExisting = this.existingImages.filter(i => !i.removed).length;
                const remainingAllowed = Math.max(0, 4 - activeExisting - this.newFiles.length);

                if (selected.length > remainingAllowed) {
                    this.imageError = 'Maksimal total 4 foto atau logo untuk setiap proyek.';
                } else {
                    this.imageError = '';
                }

                const toAdd = selected.slice(0, remainingAllowed);
                this.newFiles = [...this.newFiles, ...toAdd];
                this.syncFileInput();
                this.newPreviews = this.newFiles.map(f => URL.createObjectURL(f));
            },

            removeNewFile(idx) {
                this.newFiles.splice(idx, 1);
                this.imageError = '';
                this.syncFileInput();
                this.newPreviews = this.newFiles.map(f => URL.createObjectURL(f));
            },

            syncFileInput() {
                const dt = new DataTransfer();
                this.newFiles.forEach(f => dt.items.add(f));
                if (this.$refs.imagesInput) {
                    this.$refs.imagesInput.files = dt.files;
                }
            },

            handleSubmit(e) {
                if (this.submitting) {
                    e.preventDefault();
                    return;
                }
                this.submitting = true;
            }
        };
    }

    // Global helper triggers
    window.openCreateProjectModal = function (redirect = 'index') {
        window.dispatchEvent(new CustomEvent('open-project-modal', { detail: { mode: 'create', redirect: redirect } }));
    };

    window.openEditProjectModal = function (project, redirect = 'index') {
        window.dispatchEvent(new CustomEvent('open-project-modal', { detail: { mode: 'edit', project: project, redirect: redirect } }));
    };

    // Auto open if query parameter ?create=1 or ?edit=1 is present
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('create') === '1') {
            window.openCreateProjectModal(params.get('redirect') || 'index');
        }
        if (params.get('edit') === '1') {
            const editBtn = document.querySelector('button[\\@click*="openEditProjectModal"]');
            if (editBtn) {
                editBtn.click();
            }
        }
    });
</script>