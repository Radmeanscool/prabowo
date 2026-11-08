<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}DEEPNEST · Hackathon Deeptech Universitas Hasanuddin</title>
    <meta name="description" content="{{ $description ?? setting('site_full_name').' — '.setting('tagline') }}">
    <meta name="theme-color" content="#0c1c34">
    <meta property="og:title" content="{{ $title ?? 'DEEPNEST' }}">
    <meta property="og:description" content="{{ $description ?? setting('tagline') }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
<a href="#konten" class="skip-link">Lewati ke konten</a>

@php
    $navMain = [
        ['label' => 'Tentang', 'route' => 'about', 'match' => 'about'],
        ['label' => 'Tantangan', 'route' => 'challenges.index', 'match' => 'challenges.*'],
        ['label' => 'Mitra', 'route' => 'owners.index', 'match' => 'owners.*'],
        ['label' => 'Produk', 'route' => 'products.index', 'match' => 'products.*'],
    ];
    $navProgram = [
        ['label' => 'Tahapan Program', 'route' => 'stages', 'icon' => 'layers'],
        ['label' => 'Linimasa', 'route' => 'timeline', 'icon' => 'calendar'],
        ['label' => 'Agenda', 'route' => 'agenda', 'icon' => 'clock'],
        ['label' => 'Mentor & Juri', 'route' => 'people.index', 'icon' => 'users'],
    ];
    $navInfo = [
        ['label' => 'Pengumuman', 'route' => 'announcements.index', 'icon' => 'megaphone'],
        ['label' => 'Sumber Daya', 'route' => 'resources.index', 'icon' => 'book'],
        ['label' => 'Ketentuan & Kelayakan', 'route' => 'rules', 'icon' => 'shield'],
        ['label' => 'FAQ', 'route' => 'faq', 'icon' => 'help'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-navy-100/80 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75">
    <div class="container-page flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="Beranda DEEPNEST"><x-logo /></a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
            @foreach ($navMain as $item)
                <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
            @foreach (['Program' => $navProgram, 'Informasi' => $navInfo] as $groupLabel => $group)
                <details class="relative" data-dropdown>
                    <summary class="nav-link flex cursor-pointer list-none items-center gap-1 [&::-webkit-details-marker]:hidden">
                        {{ $groupLabel }} <x-icon name="chevron-down" class="size-4" />
                    </summary>
                    <div class="absolute left-0 top-full mt-2 w-60 rounded-2xl border border-navy-100 bg-white p-2 shadow-xl">
                        @foreach ($group as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-navy-700 hover:bg-navy-50 hover:text-navy-950">
                                <x-icon :name="$item['icon']" class="size-4 text-forest-600" /> {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-dark"><x-icon name="grid" class="size-4" /> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang</a>
            @endauth
        </div>

        <button type="button" class="btn btn-ghost -mr-2 lg:hidden" data-toggle="#mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Buka menu">
            <x-icon name="menu" class="size-6" />
        </button>
    </div>

    <div id="mobile-nav" class="is-hidden border-t border-navy-100 bg-white lg:hidden">
        <nav class="container-page grid gap-1 py-4" aria-label="Navigasi seluler">
            <a href="{{ route('home') }}" class="nav-link">Beranda</a>
            @foreach ($navMain as $item)
                <a href="{{ route($item['route']) }}" class="nav-link">{{ $item['label'] }}</a>
            @endforeach
            <p class="mt-3 px-3 eyebrow text-navy-400">Program</p>
            @foreach ($navProgram as $item)
                <a href="{{ route($item['route']) }}" class="nav-link">{{ $item['label'] }}</a>
            @endforeach
            <p class="mt-3 px-3 eyebrow text-navy-400">Informasi</p>
            @foreach ($navInfo as $item)
                <a href="{{ route($item['route']) }}" class="nav-link">{{ $item['label'] }}</a>
            @endforeach
            <div class="mt-4 grid grid-cols-2 gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-dark col-span-2">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang</a>
                @endauth
            </div>
        </nav>
    </div>
</header>

<main id="konten" class="flex-1">
    {{ $slot }}
</main>

<footer class="mt-auto bg-navy-950 text-navy-200">
    <div class="container-page grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <x-logo :light="true" />
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-navy-300">
                {{ setting('site_full_name') }}. {{ setting('tagline') }}. {{ setting('spirit') }}.
            </p>
            <p class="mt-4 text-xs leading-relaxed text-navy-400">
                Diselenggarakan oleh {{ setting('organizer') }} melalui Program Hilirisasi Riset — Bantuan Pendanaan Penyelenggaraan Hackathon Deeptech, Kementerian Pendidikan Tinggi, Sains, dan Teknologi.
            </p>
        </div>
        <div class="lg:col-span-2">
            <p class="eyebrow text-signal-400">Program</p>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a class="hover:text-white" href="{{ route('about') }}">Tentang DEEPNEST</a></li>
                <li><a class="hover:text-white" href="{{ route('challenges.index') }}">Tantangan</a></li>
                <li><a class="hover:text-white" href="{{ route('stages') }}">Tahapan Program</a></li>
                <li><a class="hover:text-white" href="{{ route('timeline') }}">Linimasa</a></li>
                <li><a class="hover:text-white" href="{{ route('products.index') }}">Produk Disetujui</a></li>
            </ul>
        </div>
        <div class="lg:col-span-2">
            <p class="eyebrow text-signal-400">Informasi</p>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a class="hover:text-white" href="{{ route('announcements.index') }}">Pengumuman</a></li>
                <li><a class="hover:text-white" href="{{ route('resources.index') }}">Sumber Daya</a></li>
                <li><a class="hover:text-white" href="{{ route('rules') }}">Ketentuan</a></li>
                <li><a class="hover:text-white" href="{{ route('faq') }}">FAQ</a></li>
            </ul>
        </div>
        <div class="lg:col-span-4">
            <p class="eyebrow text-signal-400">Kontak</p>
            <ul class="mt-4 space-y-3 text-sm">
                @if (setting('contact_address'))
                    <li class="flex gap-3"><x-icon name="map-pin" class="mt-0.5 size-4 text-navy-400" /><span>{{ setting('contact_address') }}</span></li>
                @endif
                @if (setting('contact_email'))
                    <li class="flex gap-3"><x-icon name="mail" class="mt-0.5 size-4 text-navy-400" /><a class="hover:text-white" href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></li>
                @endif
                @if (setting('contact_phone'))
                    <li class="flex gap-3"><x-icon name="phone" class="mt-0.5 size-4 text-navy-400" /><span>{{ setting('contact_phone') }}</span></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col gap-3 py-6 text-xs text-navy-400 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ setting('organizer') }} · DEEPNEST</p>
            <p class="flex gap-4">
                <a class="hover:text-white" href="{{ route('privacy') }}">Kebijakan Privasi</a>
                <a class="hover:text-white" href="{{ route('terms') }}">Syarat Penggunaan</a>
            </p>
        </div>
    </div>
</footer>
</body>
</html>
