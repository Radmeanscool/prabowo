@props(['name', 'label' => null, 'options' => [], 'value' => null, 'help' => null, 'required' => false, 'placeholder' => 'Pilih…', 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f_'.str_replace('.', '_', $key);
    $error = $errors->first($key);
    $current = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" @if ($required) required @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @elseif ($help) aria-describedby="{{ $id }}_help" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-select']) }}>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current !== '' && $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $id }}_error" class="form-error">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}_help" class="form-help">{{ $help }}</p>
    @endif
</div>
