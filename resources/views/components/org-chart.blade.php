@props(['units', 'company'])

@php
    $ids = $units->pluck('id');
    $byParent = $units->groupBy(fn($u) => $u->parent_id);
    // Unit tanpa parent, atau parent-nya tidak ikut ter-load (mis. karena filter
    // pencarian sedang aktif) dianggap root supaya tidak ada unit yang hilang dari bagan.
    $roots = $units->filter(fn($u) => is_null($u->parent_id) || ! $ids->contains($u->parent_id));
@endphp

<div class="org-chart-wrapper">
    <ul class="org-chart">
        <li>
            <div class="org-chart-box">
                <div class="org-chart-box__header" style="background:#4c1d95">{{ $company?->name ?? 'Perusahaan' }}</div>
                <div class="org-chart-box__body">
                    <div class="org-chart-box__meta">{{ $roots->count() }} unit level 1</div>
                </div>
            </div>
            @if($roots->isNotEmpty())
                <ul>
                    @foreach($roots as $root)
                        <x-org-chart-node :unit="$root" :by-parent="$byParent" />
                    @endforeach
                </ul>
            @endif
        </li>
    </ul>
</div>
