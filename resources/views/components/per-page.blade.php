@props(['options' => [10, 25, 50, 100], 'paramName' => 'per_page'])
<form method="GET" class="inline-flex items-center gap-2 text-sm text-gray-500">
    @foreach(request()->except($paramName) as $key => $value)
        @if(is_array($value))
            @foreach($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="{{ $paramName }}-select" class="whitespace-nowrap">Tampilkan</label>
    <select id="{{ $paramName }}-select" name="{{ $paramName }}" onchange="this.form.submit()"
            class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        @foreach($options as $n)
            <option value="{{ $n }}" {{ (int) request($paramName, 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
        @endforeach
    </select>
</form>
