<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · DEEPNEST</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#f6f2ea">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-[1fr_1.1fr]">
    <aside class="hidden flex-col justify-between bg-navy-950 p-12 text-paper lg:flex">
        <a href="{{ route('home') }}" class="self-start"><x-logo :light="true" /></a>
        <div class="max-w-lg">
            <p class="eyebrow text-navy-400">Hackathon Deeptech · {{ setting('organizer') }}</p>
            <p class="mt-6 font-serif text-[3.25rem] leading-[1.02] text-paper">{{ setting('tagline') }}</p>
            <p class="mt-6 text-sm leading-relaxed text-navy-400">{{ setting('site_full_name') }} · Tema Ketahanan Pangan dan Kesehatan.</p>
        </div>
        <p class="text-xs text-navy-500">{{ setting('organizer') }} · {{ setting('spirit') }}</p>
    </aside>
    <main class="flex items-center justify-center px-5 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-10 inline-block lg:hidden"><x-logo /></a>
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
