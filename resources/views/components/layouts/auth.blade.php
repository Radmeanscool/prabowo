<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · DEEPNEST</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-2">
    <aside class="hero-surface relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="grid-lines absolute inset-0" aria-hidden="true"></div>
        <a href="{{ route('home') }}" class="relative"><x-logo :light="true" /></a>
        <div class="relative max-w-md">
            <p class="eyebrow text-signal-400">Hackathon Deeptech</p>
            <p class="mt-4 text-3xl font-extrabold leading-tight text-white">{{ setting('tagline') }}</p>
            <p class="mt-4 text-sm leading-relaxed text-navy-200">{{ setting('site_full_name') }} · Tema Ketahanan Pangan dan Kesehatan.</p>
        </div>
        <p class="relative text-xs text-navy-400">{{ setting('organizer') }} · {{ setting('spirit') }}</p>
    </aside>
    <main class="flex items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-8 inline-block lg:hidden"><x-logo /></a>
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
