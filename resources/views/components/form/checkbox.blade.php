@props(['name', 'label', 'checked' => false, 'help' => null, 'value' => '1', 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f_'.str_replace('.', '_', $key);
    $isChecked = old('_token') !== null ? (bool) old($key) : (bool) $checked;
    $error = $errors->first($key);
@endphp
<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $id }}" class="flex items-start gap-3">
        <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" class="form-check mt-0.5" @checked($isChecked) {{ $attributes->except('class') }}>
        <span>
            <span class="text-sm font-semibold text-navy-900">{{ $label }}</span>
            @if ($help)
                <span class="mt-0.5 block text-xs leading-relaxed text-navy-500">{{ $help }}</span>
            @endif
        </span>
    </label>
    @if ($error)
        <p class="form-error">{{ $error }}</p>
    @endif
</div>
