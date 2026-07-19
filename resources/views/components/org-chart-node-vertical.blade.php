@props(['unit', 'byParent'])

@php $children = $byParent->get($unit->id, collect()); @endphp

<li>
    <x-org-chart-box :unit="$unit" :children-count="$children->count()" />

    @if($children->isNotEmpty())
        <ul class="org-tree">
            @foreach($children as $child)
                <x-org-chart-node-vertical :unit="$child" :by-parent="$byParent" />
            @endforeach
        </ul>
    @endif
</li>
