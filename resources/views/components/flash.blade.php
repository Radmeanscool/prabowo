@php
    $messages = [
        'success' => ['tone' => 'border-forest-200 bg-forest-50 text-forest-800', 'icon' => 'check-circle'],
        'status' => ['tone' => 'border-forest-200 bg-forest-50 text-forest-800', 'icon' => 'check-circle'],
        'warning' => ['tone' => 'border-signal-200 bg-signal-50 text-signal-700', 'icon' => 'alert'],
        'error' => ['tone' => 'border-red-200 bg-red-50 text-red-800', 'icon' => 'x-circle'],
    ];
@endphp
@foreach ($messages as $key => $style)
    @if (session($key))
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $style['tone'] }}">
            <x-icon :name="$style['icon']" class="mt-0.5 size-5" />
            <div class="font-medium">{{ session($key) }}</div>
        </div>
    @endif
@endforeach
@if ($errors->any() && ! ($hideErrors ?? false))
    <div role="alert" class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <x-icon name="alert" class="mt-0.5 size-5" />
        <div>
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
