@extends('superadmin.layout')
@section('title', 'Semua User')
@section('page-title', 'Semua User')

@section('content')

<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <p class="text-sm text-slate-400">Total: <span class="text-white font-semibold">{{ $users->total() }}</span> user</p>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">User</th>
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Role</th>
                <th class="text-left px-6 py-3 font-medium">Terdaftar</th>
                <th class="text-center px-6 py-3 font-medium">Status</th>
                <th class="text-right px-6 py-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($users as $user)
            @php $company = $user->company ?? $user->organizationUnit?->company; @endphp
            <tr class="hover:bg-white/2 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-white">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-300 text-xs">
                    <div class="flex flex-wrap gap-1">
                        <span class="bg-white/10 text-white text-xs px-2 py-0.5 rounded-full">{{ $company?->name ?? '—' }}</span>
                        @foreach($user->additionalCompanies as $extra)
                        <span class="bg-indigo-500/15 text-indigo-400 text-xs px-2 py-0.5 rounded-full">+ {{ $extra->name }}</span>
                        @endforeach
                    </div>
                </td>
                <td class="px-6 py-4">
                    @foreach($user->getRoleNames() as $role)
                    <span class="bg-indigo-500/15 text-indigo-400 text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ $role }}</span>
                    @endforeach
                </td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                <td class="px-6 py-4 text-center">
                    @if($user->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2 py-0.5 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2 py-0.5 rounded-full">Nonaktif</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <button type="button"
                            onclick='openCompanyModal(@json($user->id), @json($user->name), @json($user->company_id), @json($user->additionalCompanies->pluck("id")))'
                            class="text-xs font-medium text-indigo-400 hover:text-indigo-300 border border-indigo-500/30 hover:border-indigo-500/60 rounded-lg px-3 py-1.5 transition-all">
                        Kelola Company
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada user.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-white/5 text-slate-400">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- Modal Kelola Akses Company --}}
<div id="modal-companies" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeCompanyModal()"></div>
    <div class="relative bg-slate-900 border border-white/10 rounded-2xl w-full max-w-md shadow-2xl max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 sticky top-0 bg-slate-900 z-10">
            <div>
                <h3 class="font-semibold text-white text-sm">Kelola Akses Company</h3>
                <p id="cm-username" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <button onclick="closeCompanyModal()" class="text-slate-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="cm-form" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')

            <p class="text-xs text-slate-500 leading-relaxed">
                Company utama tidak bisa dilepas dari sini. Centang company lain yang boleh diakses user ini
                (mis. untuk mengelola Organization Units di company tersebut).
            </p>

            <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
                @foreach($companies as $c)
                <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border border-white/10 cursor-pointer hover:border-indigo-500/40 transition-all has-[:checked]:border-indigo-500/60 has-[:checked]:bg-indigo-500/10 has-[:disabled]:opacity-60 has-[:disabled]:cursor-not-allowed">
                    <input type="checkbox" name="companies[]" value="{{ $c->id }}" class="cm-company-checkbox accent-indigo-500" data-company-id="{{ $c->id }}">
                    <span class="text-sm text-white">{{ $c->name }}</span>
                    <span class="cm-primary-badge hidden ml-auto text-[10px] font-semibold text-amber-400 uppercase tracking-wide">Utama</span>
                </label>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 pt-1">
                <button type="button" onclick="closeCompanyModal()"
                        class="px-4 py-2 text-sm text-slate-400 hover:text-white border border-white/10 hover:border-white/25 rounded-xl transition-all">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition-all">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompanyModal(userId, userName, primaryCompanyId, additionalIds) {
    document.getElementById('cm-username').textContent = userName;
    document.getElementById('cm-form').action = `/superadmin/users/${userId}/companies`;

    document.querySelectorAll('.cm-company-checkbox').forEach(cb => {
        const id = Number(cb.dataset.companyId);
        const badge = cb.closest('label').querySelector('.cm-primary-badge');
        const isPrimary = id === primaryCompanyId;

        cb.checked = isPrimary || additionalIds.includes(id);
        cb.disabled = isPrimary;
        badge.classList.toggle('hidden', !isPrimary);
    });

    document.getElementById('modal-companies').classList.remove('hidden');
}

function closeCompanyModal() {
    document.getElementById('modal-companies').classList.add('hidden');
}
</script>

@endsection
