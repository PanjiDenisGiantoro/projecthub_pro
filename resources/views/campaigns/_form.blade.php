{{-- Dipakai create & edit. $campaign = null saat buat baru. --}}
@php $campaign = $campaign ?? null; @endphp

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Informasi Campaign</h3>
        <p class="fl-section-desc">Nama, channel, dan status campaign.</p>
    </div>
    <div class="fl-fields">
        <div class="fl-span-2">
            <label class="fl-label" for="name">Nama Campaign <span class="fl-req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $campaign?->name) }}" required placeholder="cth. Kampanye Peluncuran Q4"
                   class="fl-input @error('name') is-invalid @enderror">
            @error('name') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div class="fl-span-2">
            <label class="fl-label" for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="2" class="fl-input resize-none">{{ old('description', $campaign?->description) }}</textarea>
        </div>

        <div>
            <label class="fl-label" for="channel">Channel <span class="fl-req">*</span></label>
            <select id="channel" name="channel" class="fl-input">
                @foreach(['social_media'=>'📱 Social Media','email'=>'📧 Email','event'=>'🎪 Event','ads'=>'📢 Ads','seo'=>'🔍 SEO','other'=>'🔗 Lainnya'] as $v=>$l)
                <option value="{{ $v }}" {{ old('channel', $campaign?->channel) === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="fl-label" for="status">Status</label>
            <select id="status" name="status" class="fl-input">
                @foreach(['draft','active','paused','completed','cancelled'] as $st)
                <option value="{{ $st }}" {{ old('status', $campaign?->status ?? 'draft') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Target &amp; Anggaran</h3>
        <p class="fl-section-desc">Budget, target leads, periode, dan audiens yang dituju.</p>
    </div>
    <div class="fl-fields">
        <div>
            <label class="fl-label" for="budget">Budget</label>
            <div class="fl-input-icon">
                <span class="fl-prefix">Rp</span>
                <input type="number" id="budget" name="budget" value="{{ old('budget', $campaign?->budget) }}" min="0" placeholder="0" class="fl-input">
            </div>
        </div>

        <div>
            <label class="fl-label" for="goal_leads">Target Leads</label>
            <input type="number" id="goal_leads" name="goal_leads" value="{{ old('goal_leads', $campaign?->goal_leads) }}" min="0" placeholder="0" class="fl-input">
        </div>

        <div>
            <label class="fl-label" for="start_date">Tanggal Mulai</label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $campaign?->start_date?->format('Y-m-d')) }}" class="fl-input">
        </div>

        <div>
            <label class="fl-label" for="end_date">Tanggal Selesai</label>
            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $campaign?->end_date?->format('Y-m-d')) }}" class="fl-input">
        </div>

        <div class="fl-span-2">
            <label class="fl-label" for="target">Target Audiens</label>
            <textarea id="target" name="target" rows="2" placeholder="cth. UMKM di Jabodetabek, usia 25–40 tahun" class="fl-input resize-none">{{ old('target', $campaign?->target) }}</textarea>
        </div>
    </div>
</section>

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Penanggung Jawab</h3>
        <p class="fl-section-desc">PIC campaign dan proyek terkait (opsional).</p>
    </div>
    <div class="fl-fields">
        <div>
            <label class="fl-label" for="owner_id">PIC / Owner</label>
            <select id="owner_id" name="owner_id" class="fl-input">
                <option value="">— Pilih PIC —</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ old('owner_id', $campaign?->owner_id) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="fl-label" for="project_id">Link ke Proyek</label>
            <select id="project_id" name="project_id" class="fl-input">
                <option value="">— Tidak ada —</option>
                @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ old('project_id', $campaign?->project_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>
