{{--
    Indikator kekuatan password. Taruh tepat setelah input password, di dalam <form> yang sama.
    Aturan harus sama dengan Password::defaults() di AppServiceProvider.
    Pakai: <x-password-strength name="password" />
--}}
@props(['name' => 'password'])

<div x-data="{
        pw: '',
        get rules() {
            return [
                { label: 'Minimal 8 karakter',     ok: this.pw.length >= 8 },
                { label: 'Huruf besar & kecil',    ok: /[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw) },
                { label: 'Angka',                  ok: /\d/.test(this.pw) },
                { label: 'Simbol (! @ # $ dll.)',  ok: /[^A-Za-z0-9]/.test(this.pw) },
            ];
        },
        get score() { return this.rules.filter(r => r.ok).length; },
        get level() { return ['', 'Lemah', 'Sedang', 'Cukup', 'Kuat'][this.score]; },
        get color() { return ['', 'bg-rose-500', 'bg-amber-500', 'bg-yellow-400', 'bg-emerald-500'][this.score]; },
     }"
     x-init="
        const input = $el.closest('form')?.querySelector('[name={{ $name }}]');
        if (input) { pw = input.value; input.addEventListener('input', () => pw = input.value); }
     "
     {{ $attributes->merge(['class' => 'mt-2']) }}>
    <div class="flex items-center gap-2">
        <div class="flex flex-1 gap-1">
            <template x-for="i in 4" :key="i">
                <div class="h-1.5 flex-1 rounded-full transition-colors" :class="i <= score ? color : 'bg-gray-400/50'"></div>
            </template>
        </div>
        <span class="text-[11px] font-semibold w-16 text-right"
              :class="score === 4 ? 'text-emerald-500' : (score >= 2 ? 'text-amber-500' : 'text-rose-500')"
              x-show="pw.length" x-text="level"></span>
    </div>
    <ul class="mt-1.5 grid grid-cols-2 gap-x-3 gap-y-0.5">
        <template x-for="r in rules" :key="r.label">
            <li class="flex items-center gap-1 text-[11px]" :class="r.ok ? 'text-emerald-500' : 'text-slate-400'">
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path x-show="r.ok" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    <circle x-show="!r.ok" cx="12" cy="12" r="4"/>
                </svg>
                <span x-text="r.label"></span>
            </li>
        </template>
    </ul>
</div>
