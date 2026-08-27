@if($customFields->isNotEmpty())
<div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Field Tambahan</p>
        @if(auth()->user()->hasRole('admin') || auth()->user()->is_super_admin)
        <a href="{{ route('custom-fields.index') }}" class="text-xs text-blue-600 hover:underline">Kelola Field</a>
        @endif
    </div>
    <div class="space-y-4">
        @foreach($customFields as $field)
        @php $val = old('custom_fields.' . $field->key, ($values[$field->key] ?? null)); @endphp
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
            </label>

            @if($field->type === 'select')
                <select name="custom_fields[{{ $field->key }}]" {{ $field->is_required ? 'required' : '' }}
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">— Pilih —</option>
                    @foreach($field->options ?? [] as $opt)
                        <option value="{{ $opt }}" {{ (string) $val === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            @elseif($field->type === 'checkbox')
                <label class="flex items-center gap-2">
                    <input type="hidden" name="custom_fields[{{ $field->key }}]" value="0">
                    <input type="checkbox" name="custom_fields[{{ $field->key }}]" value="1"
                           {{ $val ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-sm text-gray-600">Ya</span>
                </label>
            @elseif($field->type === 'textarea')
                <textarea name="custom_fields[{{ $field->key }}]" rows="3" {{ $field->is_required ? 'required' : '' }}
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $val }}</textarea>
            @else
                <input type="{{ $field->type === 'number' ? 'number' : ($field->type === 'date' ? 'date' : 'text') }}"
                       name="custom_fields[{{ $field->key }}]" value="{{ $val }}"
                       {{ $field->is_required ? 'required' : '' }}
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @endif

            @error('custom_fields.' . $field->key) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        @endforeach
    </div>
</div>
@endif
