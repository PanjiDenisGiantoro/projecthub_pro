@props(['unit', 'byParent'])

@php $children = $byParent->get($unit->id, collect()); @endphp

<li>
    <x-org-chart-box :unit="$unit" :children-count="$children->count()" />

    @if($children->isNotEmpty())
        <ul>
            @foreach($children as $child)
                <x-org-chart-node :unit="$child" :by-parent="$byParent" />
            @endforeach
        </ul>
    @endif
</li>
