@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f_'.str_replace('.', '_', $key);
    $error = $errors->first($key);
    $current = $type === 'password' ? null : old($key, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $current }}"
        @if ($required) required @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @elseif ($help) aria-describedby="{{ $id }}_help" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
    @if ($error)
        <p id="{{ $id }}_error" class="form-error">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}_help" class="form-help">{{ $help }}</p>
    @endif
</div>
