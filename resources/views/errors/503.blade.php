<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sedang pemeliharaan · DEEPNEST</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center px-5 sm:px-10">
    <main class="mx-auto w-full max-w-3xl">
        <p class="border-b border-navy-950 pb-3 text-[0.82rem] font-medium text-navy-700">DEEPNEST</p>
        <p class="mt-10 font-serif text-[clamp(6rem,20vw,11rem)] leading-[0.85] text-navy-950">503</p>
        <h1 class="mt-6 font-serif text-4xl font-normal text-navy-950">Sedang pemeliharaan</h1>
        <p class="mt-3 max-w-xl leading-relaxed text-navy-600">Portal DEEPNEST sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary">Ke Beranda</a>
            <a href="{{ url('/masuk') }}" class="btn btn-outline">Masuk</a>
        </div>
    </main>
</body>
</html>
