<div class="bg-white rounded-xl border border-gray-200">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-900">Jenis Cuti</h3>
            <p class="text-xs text-gray-500 mt-0.5">Konfigurasi jenis cuti sesuai kebijakan perusahaan</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('hris.master.leave-types.reset') }}" method="POST" onsubmit="return confirm('Reset ke 9 jenis cuti default UU Ketenagakerjaan?')">
                @csrf
                <button class="text-xs text-amber-600 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50">Reset Default</button>
            </form>
            <button onclick="document.getElementById('modal-add-leave').showModal()"
                    class="text-xs bg-violet-600 text-white px-3 py-1.5 rounded-lg hover:bg-violet-700">+ Tambah</button>
        </div>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Kuota</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Berbayar</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Saldo</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($leaveTypes as $lt)
            <tr>
                <td class="px-4 py-3 font-mono font-semibold text-violet-700">{{ $lt->code }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $lt->name }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $lt->default_quota ?: '∞' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="{{ $lt->is_paid ? 'text-green-600' : 'text-gray-400' }}">{{ $lt->is_paid ? '✓' : '—' }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="{{ $lt->has_balance ? 'text-green-600' : 'text-gray-400' }}">{{ $lt->has_balance ? '✓' : '—' }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <form action="{{ route('hris.master.leave-types.toggle', $lt) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button class="text-xs {{ $lt->is_active ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $lt->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3 text-center">
                    <form action="{{ route('hris.master.leave-types.destroy', $lt) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jenis cuti ini?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada jenis cuti.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah --}}
<dialog id="modal-add-leave" class="rounded-2xl p-6 shadow-xl w-full max-w-lg">
    <form action="{{ route('hris.master.leave-types.store') }}" method="POST" class="space-y-4">
        @csrf
        <h3 class="font-bold text-gray-900 text-lg">Tambah Jenis Cuti</h3>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nama *</label>
                <input type="text" name="name" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Kode *</label>
                <input type="text" name="code" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Kuota (0 = tak terbatas)</label>
                <input type="number" name="default_quota" value="0" min="0" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender_restriction" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                    <option value="all">Semua</option>
                    <option value="male">Laki-laki</option>
                    <option value="female">Perempuan</option>
                </select>
            </div>
        </div>
        <div class="flex gap-4 flex-wrap text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_paid" value="1" checked> Berbayar</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="needs_approval" value="1" checked> Butuh Approval</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="has_balance" value="1" checked> Pakai Saldo</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="needs_attachment" value="1"> Wajib Lampiran</label>
        </div>
        <div class="flex gap-2 justify-end pt-2">
            <button type="button" onclick="document.getElementById('modal-add-leave').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
            <button class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-xl hover:bg-violet-700">Simpan</button>
        </div>
    </form>
</dialog>
