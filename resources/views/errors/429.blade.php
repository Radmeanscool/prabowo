<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Terlalu banyak permintaan · DEEPNEST</title>
    @vite(['resources/css/app.css'])
</head>
<body class="hero-surface flex min-h-screen items-center justify-center px-4 text-white">
    <main class="max-w-lg text-center">
        <p class="text-7xl font-extrabold text-signal-400">429</p>
        <h1 class="mt-4 text-3xl font-extrabold text-white">Terlalu banyak permintaan</h1>
        <p class="mt-3 text-navy-200">Tunggu sebentar sebelum mencoba lagi.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary">Ke Beranda</a>
            <a href="{{ url('/masuk') }}" class="btn btn-outline-light">Masuk</a>
        </div>
    </main>
</body>
</html>
