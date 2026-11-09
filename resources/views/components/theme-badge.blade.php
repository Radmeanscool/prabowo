@props(['theme' => null, 'icon' => false])
@if ($theme)
    <span {{ $attributes->merge(['class' => 'badge '.$theme->cssClass()]) }}>
        @if ($icon)<x-icon :name="$theme->icon()" class="size-3.5" stroke="2" />@endif
        {{ $theme->name }}
    </span>
@endif
