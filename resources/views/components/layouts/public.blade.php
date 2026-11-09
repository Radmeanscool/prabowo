<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}DEEPNEST · Hackathon Deeptech Universitas Hasanuddin</title>
    <meta name="description" content="{{ $description ?? setting('site_full_name').' — '.setting('tagline') }}">
    <meta name="theme-color" content="#f6f2ea">
    <meta property="og:title" content="{{ $title ?? 'DEEPNEST' }}">
    <meta property="og:description" content="{{ $description ?? setting('tagline') }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
<a href="#konten" class="skip-link">Lewati ke konten</a>

@php
    // Bagian yang belum berisi data terbit tidak ditautkan dari navigasi (lihat AppServiceProvider).
    $show = ($publicNav ?? []) + ['products' => true, 'owners' => true, 'documents' => true];
    $registrationOpen = (bool) setting('registration_enabled', true);

    $navMain = array_values(array_filter([
        ['label' => 'Tentang', 'route' => 'about', 'match' => 'about'],
        ['label' => 'Tantangan', 'route' => 'challenges.index', 'match' => 'challenges.*'],
        $show['owners'] ? ['label' => 'Mitra', 'route' => 'owners.index', 'match' => 'owners.*'] : null,
        $show['products'] ? ['label' => 'Produk', 'route' => 'products.index', 'match' => 'products.*'] : null,
    ]));
    $navProgram = [
        ['label' => 'Tahapan Program', 'route' => 'stages'],
        ['label' => 'Linimasa', 'route' => 'timeline'],
        ['label' => 'Agenda', 'route' => 'agenda'],
        ['label' => 'Mentor & Juri', 'route' => 'people.index'],
    ];
    $navInfo = array_values(array_filter([
        ['label' => 'Pengumuman', 'route' => 'announcements.index'],
        $show['documents'] ? ['label' => 'Sumber Daya', 'route' => 'resources.index'] : null,
        ['label' => 'Ketentuan & Kelayakan', 'route' => 'rules'],
        ['label' => 'FAQ', 'route' => 'faq'],
    ]));
    $groupActive = fn (array $group) => collect($group)->contains(fn ($item) => request()->routeIs($item['route']));
@endphp

<header class="sticky top-0 z-40 border-b border-navy-950/10 bg-paper/95 backdrop-blur supports-[backdrop-filter]:bg-paper/85">
    <div class="container-page flex h-16 items-center justify-between gap-6">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="Beranda DEEPNEST"><x-logo /></a>

        <nav class="hidden items-center lg:flex" aria-label="Navigasi utama">
            @foreach ($navMain as $item)
                <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}" @if (request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            @foreach (['Program' => $navProgram, 'Informasi' => $navInfo] as $groupLabel => $group)
                <details class="relative" data-dropdown>
                    <summary class="nav-link flex cursor-pointer list-none items-center gap-1 [&::-webkit-details-marker]:hidden {{ $groupActive($group) ? 'is-active' : '' }}">
                        {{ $groupLabel }} <x-icon name="chevron-down" class="size-3.5 opacity-60" />
                    </summary>
                    <div class="absolute left-0 top-full mt-3 w-56 border border-navy-950/15 bg-sheet py-2 shadow-[0_18px_40px_-20px_rgba(21,20,15,0.35)]">
                        @foreach ($group as $item)
                            <a href="{{ route($item['route']) }}" class="block px-4 py-2 text-[0.92rem] text-navy-700 hover:bg-navy-50 hover:text-navy-950 {{ request()->routeIs($item['route']) ? 'font-semibold text-navy-950' : '' }}">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="nav-link">Masuk</a>
                @if ($registrationOpen)
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
                @endif
            @endauth
        </div>

        <button type="button" class="btn btn-ghost -mr-2 lg:hidden" data-toggle="#mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Buka menu">
            <x-icon name="menu" class="size-6" />
        </button>
    </div>

    <div id="mobile-nav" class="is-hidden max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-navy-950/10 bg-paper lg:hidden">
        <nav class="container-page grid gap-8 py-6 sm:grid-cols-3" aria-label="Navigasi seluler">
            <div>
                <p class="eyebrow text-navy-500">Utama</p>
                <ul class="mt-3 space-y-1">
                    <li><a href="{{ route('home') }}" class="block py-1.5 font-serif text-2xl text-navy-950">Beranda</a></li>
                    @foreach ($navMain as $item)
                        <li><a href="{{ route($item['route']) }}" class="block py-1.5 font-serif text-2xl text-navy-950">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            @foreach (['Program' => $navProgram, 'Informasi' => $navInfo] as $groupLabel => $group)
                <div>
                    <p class="eyebrow text-navy-500">{{ $groupLabel }}</p>
                    <ul class="mt-3 space-y-1">
                        @foreach ($group as $item)
                            <li><a href="{{ route($item['route']) }}" class="block py-1.5 text-base text-navy-800">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
            <div class="flex flex-wrap gap-2 border-t border-navy-950/10 pt-6 sm:col-span-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                    @if ($registrationOpen)
                        <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
                    @endif
                @endauth
            </div>
        </nav>
    </div>
</header>

<main id="konten" class="flex-1">
    {{ $slot }}
</main>

<footer class="mt-auto overflow-hidden bg-navy-950 text-navy-300">
    <div class="container-page">
        <div class="grid gap-12 border-b border-white/10 pb-14 pt-16 md:grid-cols-12">
            <div class="md:col-span-12 lg:col-span-5">
                <p class="font-serif text-[2rem] leading-[1.1] text-paper">{{ setting('tagline') }}.</p>
                <p class="mt-5 max-w-md text-sm leading-relaxed text-navy-400">
                    Diselenggarakan oleh {{ setting('organizer') }} melalui Program Hilirisasi Riset — Bantuan Pendanaan Penyelenggaraan Hackathon Deeptech, Direktorat Hilirisasi dan Kemitraan, Direktorat Jenderal Riset dan Pengembangan, Kementerian Pendidikan Tinggi, Sains, dan Teknologi.
                </p>
            </div>
            <div class="md:col-span-4 lg:col-span-2">
                <p class="eyebrow text-navy-500">Program</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a class="hover:text-paper" href="{{ route('about') }}">Tentang DEEPNEST</a></li>
                    <li><a class="hover:text-paper" href="{{ route('challenges.index') }}">Tantangan</a></li>
                    <li><a class="hover:text-paper" href="{{ route('stages') }}">Tahapan Program</a></li>
                    <li><a class="hover:text-paper" href="{{ route('timeline') }}">Linimasa</a></li>
                    @if ($show['products'])
                        <li><a class="hover:text-paper" href="{{ route('products.index') }}">Produk</a></li>
                    @endif
                </ul>
            </div>
            <div class="md:col-span-4 lg:col-span-2">
                <p class="eyebrow text-navy-500">Informasi</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a class="hover:text-paper" href="{{ route('announcements.index') }}">Pengumuman</a></li>
                    @if ($show['documents'])
                        <li><a class="hover:text-paper" href="{{ route('resources.index') }}">Sumber Daya</a></li>
                    @endif
                    <li><a class="hover:text-paper" href="{{ route('rules') }}">Ketentuan</a></li>
                    <li><a class="hover:text-paper" href="{{ route('faq') }}">FAQ</a></li>
                </ul>
            </div>
            <div class="md:col-span-4 lg:col-span-3">
                <p class="eyebrow text-navy-500">Kontak</p>
                <ul class="mt-4 space-y-3 text-sm leading-relaxed">
                    @if (setting('contact_address'))
                        <li>{{ setting('contact_address') }}</li>
                    @endif
                    @if (setting('contact_email'))
                        <li><a class="underline decoration-white/25 underline-offset-4 hover:text-paper hover:decoration-paper" href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></li>
                    @endif
                    @if (setting('contact_phone'))
                        <li>{{ setting('contact_phone') }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <p class="select-none whitespace-nowrap pb-6 pt-10 font-serif leading-[0.8] tracking-[-0.02em] text-paper text-[min(calc(27.5vw-20px),20.1rem)]" aria-hidden="true">DEEPNEST</p>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col gap-3 py-5 text-xs text-navy-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ setting('organizer') }} · {{ setting('spirit') }}</p>
            <p class="flex gap-5">
                <a class="hover:text-paper" href="{{ route('privacy') }}">Kebijakan Privasi</a>
                <a class="hover:text-paper" href="{{ route('terms') }}">Syarat Penggunaan</a>
            </p>
        </div>
    </div>
</footer>
</body>
</html>
