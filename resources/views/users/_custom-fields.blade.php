@if($customFields->isNotEmpty())
<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Additional Information</h3>
        <p class="fl-section-desc">
            Custom fields defined for your company.
            @if(auth()->user()->hasRole('admin') || auth()->user()->is_super_admin)
                <a href="{{ route('custom-fields.index') }}" class="text-blue-600 hover:underline">Manage fields</a>
            @endif
        </p>
    </div>
    <div class="fl-fields">
        @foreach($customFields as $field)
        @php $val = old('custom_fields.' . $field->key, ($values[$field->key] ?? null)); @endphp
        <div class="{{ $field->type === 'textarea' ? 'fl-span-2' : '' }}">
            @if($field->type === 'checkbox')
                <input type="hidden" name="custom_fields[{{ $field->key }}]" value="0">
                <label class="fl-switch mt-1">
                    <input type="checkbox" name="custom_fields[{{ $field->key }}]" value="1" {{ $val ? 'checked' : '' }}>
                    <span class="fl-switch-track"></span>
                    <span>{{ $field->label }} @if($field->is_required)<span class="fl-req text-red-500">*</span>@endif</span>
                </label>
            @else
                <label class="fl-label">
                    {{ $field->label }} @if($field->is_required)<span class="fl-req">*</span>@endif
                </label>

                @if($field->type === 'select')
                    <select name="custom_fields[{{ $field->key }}]" {{ $field->is_required ? 'required' : '' }} class="fl-input">
                        <option value="">— Select —</option>
                        @foreach($field->options ?? [] as $opt)
                            <option value="{{ $opt }}" {{ (string) $val === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($field->type === 'textarea')
                    <textarea name="custom_fields[{{ $field->key }}]" rows="3" {{ $field->is_required ? 'required' : '' }}
                              class="fl-input">{{ $val }}</textarea>
                @else
                    <input type="{{ $field->type === 'number' ? 'number' : ($field->type === 'date' ? 'date' : 'text') }}"
                           name="custom_fields[{{ $field->key }}]" value="{{ $val }}"
                           {{ $field->is_required ? 'required' : '' }}
                           class="fl-input">
                @endif
            @endif

            @error('custom_fields.' . $field->key) <p class="fl-error">{{ $message }}</p> @enderror
        </div>
        @endforeach
    </div>
</section>
@endif
