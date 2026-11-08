@props(['name', 'label' => null, 'value' => null, 'help' => null, 'required' => false, 'rows' => 4, 'words' => null, 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f_'.str_replace('.', '_', $key);
    $error = $errors->first($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    @endif
    @if ($help)
        <p id="{{ $id }}_help" class="-mt-0.5 mb-2 text-xs leading-relaxed text-navy-500">{{ $help }}</p>
    @endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($words) data-word-limit="{{ $words }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @elseif ($help) aria-describedby="{{ $id }}_help" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-textarea']) }}>{{ old($key, $value) }}</textarea>
    @if ($error)
        <p id="{{ $id }}_error" class="form-error">{{ $error }}</p>
    @endif
</div>
