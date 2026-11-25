{{--
    Aturan SLA Tiket per proyek (tab Tickets).
    Butuh: $project, $slaRules (SlaService::rulesForProject), $canManageSla.
    Target selesai tiket ("end date") = waktu dibuat + target selesai sesuai prioritas.
--}}
@php
    $slaRules     = $slaRules ?? app(\App\Services\SlaService::class)->rulesForProject($project->id);
    $canManageSla = $canManageSla ?? \App\Http\Controllers\Web\TicketSlaWebController::canManage($project);
    $isCustomSla  = collect($slaRules)->contains(fn ($r) => $r['source'] === 'project');
    $slaDots      = ['critical' => 'bg-red-500', 'high' => 'bg-orange-500', 'medium' => 'bg-blue-500', 'low' => 'bg-slate-400'];
    $toUnit = function (int $minutes) {
        if ($minutes % 1440 === 0) return ['value' => $minutes / 1440, 'unit' => 1440];
        if ($minutes % 60 === 0)   return ['value' => $minutes / 60, 'unit' => 60];
        return ['value' => $minutes, 'unit' => 1];
    };
    $slaFormInit = collect($slaRules)->map(fn ($r) => [
        'response'       => $toUnit($r['response']),
        'resolution'     => $toUnit($r['resolution']),
        'escalation'     => $r['escalation'],
        'business_hours' => $r['business_hours'],
    ])->all();
@endphp

<div x-data="{
        open: true,
        editing: false,
        reapply: true,
        form: @js($slaFormInit),
        minutes(v) { return Math.round((Number(v.value) || 0) * Number(v.unit)); },
     }"
     class="bg-white dark:bg-gray-850 rounded-2xl">

    {{-- Header --}}
    <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3" :class="open ? 'border-b border-gray-100 dark:border-gray-800' : ''">
        <button type="button" @click="open = !open" class="flex items-center gap-2.5 text-left cursor-pointer">
            <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </span>
            <span>
                <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    Aturan SLA Tiket
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $isCustomSla ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                        {{ $isCustomSla ? 'Khusus proyek ini' : 'Default' }}
                    </span>
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500 block mt-0.5">
                    Target selesai tiket dihitung otomatis dari prioritas, mulai saat tiket dibuat.
                </span>
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>

        @if($canManageSla)
            <button type="button" x-show="open && !editing" @click="editing = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                Ubah aturan
            </button>
        @endif
    </div>

    <div x-show="open">
        {{-- ── Mode lihat ─────────────────────────────────────────────── --}}
        <div x-show="!editing" class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        <th class="px-6 py-3">Prioritas</th>
                        <th class="px-4 py-3">Kapan dipakai</th>
                        <th class="px-4 py-3 whitespace-nowrap">Target respons</th>
                        <th class="px-4 py-3 whitespace-nowrap">Target selesai</th>
                        <th class="px-4 py-3 whitespace-nowrap">Hitungan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Peringatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach($slaRules as $key => $r)
                        @php $meta = \App\Models\SlaPolicy::PRIORITIES[$key]; @endphp
                        <tr class="align-top">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-2 font-semibold text-gray-800 dark:text-gray-100">
                                    <span class="w-2 h-2 rounded-full {{ $slaDots[$key] }}"></span>{{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 min-w-[240px]">
                                {{ $meta['desc'] }}
                                <span class="block text-[11px] text-gray-400 mt-0.5">Contoh: {{ $meta['example'] }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ \App\Models\SlaPolicy::humanMinutes($r['response']) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900 dark:text-white">{{ \App\Models\SlaPolicy::humanMinutes($r['resolution']) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ $r['business_hours'] ? 'Jam kerja' : '24 jam' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">di {{ $r['escalation'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="px-6 py-3 text-[11px] text-gray-400 dark:text-gray-500 border-t border-gray-50 dark:border-gray-800">
                <strong>Jam kerja</strong> = Senin–Jumat {{ sprintf('%02d:00', \App\Services\SlaService::WORK_START) }}–{{ sprintf('%02d:00', \App\Services\SlaService::WORK_END) }}, di luar hari libur perusahaan (target dalam hari = hari kerja).
                <strong>Peringatan</strong> dikirim ke tim proyek & PIC saat waktu SLA terpakai mencapai persentase tsb; lewat target → tiket ditandai SLA breach.
                @unless($canManageSla) Aturan hanya bisa diubah manager proyek / admin. @endunless
            </p>
        </div>

        {{-- ── Mode ubah ──────────────────────────────────────────────── --}}
        @if($canManageSla)
            <div x-show="editing" x-cloak class="p-4 sm:px-6">
                <form method="POST" action="{{ route('tickets.sla.update', $project) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3">
                        @foreach(\App\Models\SlaPolicy::PRIORITIES as $key => $meta)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-3.5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-2">
                                    <span class="inline-flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-gray-100">
                                        <span class="w-2 h-2 rounded-full {{ $slaDots[$key] }}"></span>{{ $meta['label'] }}
                                    </span>
                                </div>

                                @foreach(['response' => 'Target respons', 'resolution' => 'Target selesai'] as $field => $label)
                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $label }}</label>
                                        <div class="flex gap-1.5">
                                            <input type="number" min="1" step="1" required x-model.number="form.{{ $key }}.{{ $field }}.value"
                                                   class="w-full min-w-0 px-3 py-2 text-sm bg-gray-50/60 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <select x-model.number="form.{{ $key }}.{{ $field }}.unit"
                                                    class="px-2 py-2 text-sm bg-gray-50/60 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="1">menit</option>
                                                <option value="60">jam</option>
                                                <option value="1440">hari</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="rules[{{ $key }}][{{ $field }}_minutes]" :value="minutes(form.{{ $key }}.{{ $field }})">
                                    </div>
                                @endforeach

                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1">Peringatan di (%)</label>
                                    <input type="number" min="10" max="100" required name="rules[{{ $key }}][escalation_at_percent]" x-model.number="form.{{ $key }}.escalation"
                                           class="w-full px-3 py-2 text-sm bg-gray-50/60 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="sm:col-span-2">
                                    <input type="hidden" name="rules[{{ $key }}][business_hours_only]" :value="form.{{ $key }}.business_hours ? 1 : 0">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-200 cursor-pointer py-2">
                                        <input type="checkbox" x-model="form.{{ $key }}.business_hours" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        Hanya jam kerja
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <label class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="reapply" value="1" x-model="reapply" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Hitung ulang target tiket yang <strong>masih terbuka</strong> dengan aturan baru
                            <span class="block text-gray-400">Kalau tidak dicentang, aturan baru hanya berlaku untuk tiket baru (dan tiket yang prioritasnya diubah).</span></span>
                    </label>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-xl transition cursor-pointer">Simpan aturan</button>
                            <button type="button" @click="editing = false; form = @js($slaFormInit)" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition cursor-pointer">Batal</button>
                        </div>
                        @if($isCustomSla)
                            <button type="submit" form="sla-reset-form-{{ $project->id }}"
                                    onclick="return confirm('Kembalikan aturan SLA proyek ini ke default?')"
                                    class="px-3 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">Kembalikan ke default</button>
                        @endif
                    </div>
                </form>

                @if($isCustomSla)
                    <form id="sla-reset-form-{{ $project->id }}" method="POST" action="{{ route('tickets.sla.reset', $project) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="reapply" :value="reapply ? 1 : 0">
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
