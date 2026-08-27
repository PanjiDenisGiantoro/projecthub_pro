@props(['size' => 48, 'pupilSize' => 16, 'maxDist' => 10, 'character' => 'purple', 'solid' => false])

@php
    $blinkKey = $character === 'purple' ? 'purpleBlink' : ($character === 'black' ? 'blackBlink' : null);
@endphp

@if($solid)
    {{-- Colored pupil only (no white eye), used for the orange/yellow characters. --}}
    <div x-data="eyeTrack('{{ $character }}', {{ $maxDist }})" x-effect="track()"
         class="rounded-full"
         :style="`width:{{ $size }}px; height:{{ $size }}px; background-color:#2D2D2D; transform: translate(${tx}px, ${ty}px); transition: transform 0.1s ease-out;`">
    </div>
@else
    <div x-data="eyeTrack('{{ $character }}', {{ $maxDist }})" x-effect="track()"
         class="rounded-full flex items-center justify-center transition-all duration-150"
         :style="`width:{{ $size }}px; height:${ $store.login.{{ $blinkKey }} ? 2 : {{ $size }} }px; background-color:white; overflow:hidden;`">
        <div x-show="!$store.login.{{ $blinkKey }}" class="rounded-full"
             :style="`width:{{ $pupilSize }}px; height:{{ $pupilSize }}px; background-color:#2D2D2D; transform: translate(${tx}px, ${ty}px); transition: transform 0.1s ease-out;`">
        </div>
    </div>
@endif
