@props(['name', 'label' => null, 'help' => null, 'required' => false, 'accept' => null, 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f_'.str_replace('.', '_', $key);
    $error = $errors->first($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    @endif
    <input type="file" name="{{ $name }}" id="{{ $id }}" @if ($accept) accept="{{ $accept }}" @endif @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => 'block w-full rounded-xl border border-dashed border-navy-300 bg-navy-50/50 p-3 text-sm text-navy-700 file:mr-4 file:rounded-lg file:border-0 file:bg-navy-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700']) }}>
    @if ($error)
        <p class="form-error">{{ $error }}</p>
    @elseif ($help)
        <p class="form-help">{{ $help }}</p>
    @endif
</div>
