@php
    $type = $field['type'] ?? 'text';
    $name = $field['name'] ?? null;
    $span = ($field['span'] ?? null) === 'half' ? '' : 'sm:col-span-2';
    if (in_array($type, ['text', 'email', 'url', 'number', 'color', 'select', 'date', 'datetime', 'time'], true) && ! isset($field['span'])) {
        $span = '';
    }
    $raw = array_key_exists('value', $field) ? (is_callable($field['value']) ? $field['value']($model) : $field['value']) : ($name ? $model->{$name} : null);
    if ($raw instanceof \BackedEnum) { $raw = $raw->value; }
    if ($raw instanceof \DateTimeInterface) {
        $raw = match ($type) { 'date' => $raw->format('Y-m-d'), 'time' => $raw->format('H:i'), default => $raw->format('Y-m-d\TH:i') };
    } elseif ($type === 'time' && is_string($raw)) {
        $raw = substr($raw, 0, 5);
    }
@endphp

@switch($type)
    @case('section')
        <div class="sm:col-span-2 border-b border-navy-100 pb-2 pt-3 first:pt-0">
            <p class="text-sm font-extrabold text-navy-950">{{ $field['label'] }}</p>
            @if (! empty($field['help']))<p class="text-xs text-navy-500">{{ $field['help'] }}</p>@endif
        </div>
        @break

    @case('textarea')
    @case('markdown')
        <x-form.textarea :name="$name" :label="$field['label']" :value="$raw" :rows="$field['rows'] ?? ($type === 'markdown' ? 10 : 4)" :required="$field['required'] ?? false"
            :help="($field['help'] ?? null).($type === 'markdown' ? ' Mendukung Markdown: ## Judul, **tebal**, - daftar, [tautan](https://…).' : '')" class="{{ $span }}" />
        @break

    @case('select')
        <x-form.select :name="$name" :label="$field['label']" :options="$field['options']" :value="$raw" :required="$field['required'] ?? false"
            :placeholder="$field['placeholder'] ?? 'Pilih…'" :help="$field['help'] ?? null" class="{{ $span }}" />
        @break

    @case('checkbox')
        <x-form.checkbox :name="$name" :label="$field['label']" :checked="(bool) $raw" :help="$field['help'] ?? null" class="sm:col-span-2" />
        @break

    @case('multicheck')
        @php $checked = collect(old($name, is_callable($field['checked'] ?? null) ? $field['checked']($model) : []))->map(fn ($v) => (string) $v)->all(); @endphp
        <fieldset class="sm:col-span-2">
            <legend class="form-label">{{ $field['label'] }}</legend>
            @if (! empty($field['help']))<p class="-mt-1 mb-2 text-xs text-navy-500">{{ $field['help'] }}</p>@endif
            <div class="flex flex-wrap gap-4">
                @foreach ($field['options'] as $value => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" class="form-check" @checked(in_array((string) $value, $checked, true))> {{ $label }}
                    </label>
                @endforeach
            </div>
            @error($name)<p class="form-error">{{ $message }}</p>@enderror
        </fieldset>
        @break

    @case('image')
    @case('file')
        <div class="sm:col-span-2">
            <x-form.file :name="$name" :label="$field['label']" :accept="$field['accept'] ?? null" :help="$field['help'] ?? null" />
            @if (! empty($model->{$field['column']}))
                <div class="mt-3 flex items-center gap-4 rounded-xl border border-navy-100 p-3">
                    @if ($type === 'image' && ! empty($field['preview']))
                        <img src="{{ $field['preview']($model) }}" alt="Pratinjau" class="size-16 rounded-lg object-cover">
                    @else
                        <x-icon name="file" class="size-6 text-navy-400" />
                    @endif
                    <span class="text-sm text-navy-600">Berkas tersimpan{{ ! empty($field['current']) ? ': '.$field['current']($model) : '' }}</span>
                    <label class="ml-auto flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="{{ $name }}_remove" value="1" class="form-check"> Hapus</label>
                </div>
            @endif
        </div>
        @break

    @default
        <x-form.input :name="$name" :type="$type === 'datetime' ? 'datetime-local' : $type" :label="$field['label']" :value="$raw" :required="$field['required'] ?? false"
            :help="$field['help'] ?? null" :placeholder="$field['placeholder'] ?? null" class="{{ $span }}"
            :min="$field['min'] ?? null" :max="$field['max'] ?? null" :step="$field['step'] ?? null" />
@endswitch
