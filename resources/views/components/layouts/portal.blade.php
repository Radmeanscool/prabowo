@props(['title' => null])
@php
    $user = auth()->user();
    $role = $user->role->value;
    $p = $portalProposal ?? null;

    $sections = match ($role) {
        'participant' => [
            null => [
                ['Dashboard', 'portal.dashboard', 'grid', 'portal.dashboard'],
                ['Profil Saya', 'portal.profile', 'user', 'portal.profile'],
                ['Tim Saya', 'portal.team', 'users', 'portal.team*'],
            ],
            'Proposal' => [
                ['Pilih Tantangan', 'portal.shortcut.challenge', 'target', 'portal.proposals.challenge*|portal.proposals.create|portal.shortcut.challenge'],
                ['Proposal Saya', 'portal.proposals.index', 'file', 'portal.proposals.index|portal.proposals.show|portal.proposals.edit|portal.proposals.confirm'],
                ['Berkas Proposal', 'portal.shortcut.files', 'upload', 'portal.proposals.files*|portal.shortcut.files'],
                ['Status Pengajuan', 'portal.proposal.status', 'history', 'portal.proposals.status|portal.proposal.status'],
            ],
            'Informasi' => [
                ['Pengumuman', 'portal.announcements', 'megaphone', 'portal.announcements'],
                ['Bantuan & FAQ', 'portal.help', 'help', 'portal.help'],
            ],
        ],
        'reviewer' => array_filter([
            null => [
                ['Dashboard', 'reviewer.dashboard', 'grid', 'reviewer.dashboard'],
                ['Proposal Ditugaskan', 'reviewer.assignments.index', 'clipboard', 'reviewer.assignments.*'],
                ['Rubrik Penilaian', 'reviewer.rubric', 'list-check', 'reviewer.rubric'],
            ],
            'Penilaian' => [
                ['Draf Penilaian', ['reviewer.reviews.index', ['status' => 'draft']], 'pencil', 'reviewer.reviews.index:draft'],
                ['Penilaian Terkirim', ['reviewer.reviews.index', ['status' => 'submitted']], 'check-circle', 'reviewer.reviews.index:submitted'],
                setting('reviewer_self_selection_enabled') ? ['Pilih Proposal', 'reviewer.self-select.index', 'plus', 'reviewer.self-select.*'] : null,
            ],
            'Informasi' => [
                ['Pengumuman', 'reviewer.announcements', 'megaphone', 'reviewer.announcements'],
                ['Konflik Kepentingan', 'reviewer.conflicts.index', 'shield', 'reviewer.conflicts.*'],
            ],
        ]),
        default => [
            null => [
                ['Dashboard', 'admin.dashboard', 'grid', 'admin.dashboard'],
            ],
            'Seleksi' => [
                ['Proposal', 'admin.proposals.index', 'file', 'admin.proposals.*'],
                ['Penugasan Reviewer', 'admin.assignments.index', 'clipboard', 'admin.assignments.*'],
                ['Penilaian', 'admin.reviews.index', 'list-check', 'admin.reviews.*'],
                ['Produk Disetujui', 'admin.products.index', 'star', 'admin.products.*'],
            ],
            'Peserta' => [
                ['Pengguna', 'admin.users.index', 'user', 'admin.users.*'],
                ['Tim', 'admin.teams.index', 'users', 'admin.teams.*'],
            ],
            'Program' => [
                ['Tema', 'admin.themes.index', 'layers', 'admin.themes.*|admin.theme-questions.*'],
                ['Tantangan', 'admin.challenges.index', 'target', 'admin.challenges.*'],
                ['Mitra Challenge Owner', 'admin.challenge-owners.index', 'building', 'admin.challenge-owners.*'],
                ['Tahapan Program', 'admin.stages.index', 'flag', 'admin.stages.*'],
                ['Agenda', 'admin.agenda.index', 'calendar', 'admin.agenda.*'],
                ['Mentor & Juri', 'admin.people.index', 'network', 'admin.people.*'],
                ['Rubrik & Kriteria', 'admin.review-phases.index', 'scale', 'admin.review-phases.*|admin.criteria.*'],
            ],
            'Konten' => [
                ['Pengumuman', 'admin.announcements.index', 'megaphone', 'admin.announcements.*'],
                ['Dokumen', 'admin.documents.index', 'book', 'admin.documents.*'],
                ['Halaman & FAQ', 'admin.pages.index', 'file', 'admin.pages.*|admin.faqs.*'],
            ],
            'Sistem' => [
                ['Laporan & Ekspor', 'admin.exports.index', 'download', 'admin.exports.*'],
                ['Log Audit', 'admin.audit.index', 'history', 'admin.audit.*'],
                ['Pengaturan', 'admin.settings.edit', 'settings', 'admin.settings.*'],
            ],
        ],
    };

    $portalLabel = match ($role) {
        'participant' => 'Dashboard Peserta',
        'reviewer' => 'Dashboard Reviewer',
        default => 'Dashboard Admin',
    };

    $isActive = function (string $pattern): bool {
        foreach (explode('|', $pattern) as $part) {
            [$name, $status] = array_pad(explode(':', $part, 2), 2, null);
            if (request()->routeIs($name) && ($status === null || request('status') === $status)) {
                return true;
            }
        }
        return false;
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ? $title.' · ' : '' }}{{ $portalLabel }} · DEEPNEST</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<a href="#konten" class="skip-link">Lewati ke konten</a>

<aside id="portal-sidebar" class="portal-sidebar" aria-label="Navigasi {{ $portalLabel }}">
    <div class="flex h-16 items-center justify-between px-5">
        <a href="{{ route('dashboard') }}"><x-logo :light="true" /></a>
        <button type="button" class="rounded-lg p-1.5 text-navy-300 hover:bg-white/10 lg:hidden" data-toggle="#portal-sidebar" data-toggle-class="is-open" aria-label="Tutup menu">
            <x-icon name="x" />
        </button>
    </div>
    <div class="mx-4 mb-2 rounded-xl bg-white/5 px-3 py-2.5">
        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-signal-400">{{ $portalLabel }}</p>
        <p class="mt-0.5 truncate text-sm font-semibold text-white">{{ $user->name }}</p>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-6">
        @foreach ($sections as $heading => $items)
            @if ($heading)
                <p class="side-heading">{{ $heading }}</p>
            @endif
            @foreach (array_filter($items) as [$label, $route, $icon, $pattern])
                @php $href = is_array($route) ? route($route[0], $route[1]) : route($route); @endphp
                <a href="{{ $href }}" class="side-link {{ $isActive($pattern) ? 'is-active' : '' }}" @if ($isActive($pattern)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="size-[1.15rem] text-navy-400" /> {{ $label }}
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="border-t border-white/10 p-3">
        <a href="{{ route('home') }}" class="side-link"><x-icon name="globe" class="size-[1.15rem] text-navy-400" /> Situs Publik</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-link w-full"><x-icon name="logout" class="size-[1.15rem] text-navy-400" /> Keluar</button>
        </form>
    </div>
</aside>
<div class="portal-backdrop" data-toggle="#portal-sidebar" data-toggle-class="is-open" aria-hidden="true"></div>

<div class="lg:pl-72">
    <header class="sticky top-0 z-30 border-b border-navy-100 bg-white/90 backdrop-blur">
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-ghost -ml-2 lg:hidden" data-toggle="#portal-sidebar" data-toggle-class="is-open" aria-controls="portal-sidebar" aria-expanded="false" aria-label="Buka menu">
                    <x-icon name="menu" class="size-6" />
                </button>
                <span class="hidden text-sm font-semibold text-navy-500 sm:inline">{{ $portalLabel }}</span>
            </div>
            <details class="relative" data-dropdown>
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-navy-50 [&::-webkit-details-marker]:hidden">
                    <span class="flex size-8 items-center justify-center rounded-full bg-forest-600 text-xs font-bold text-white">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="hidden max-w-48 truncate text-sm font-semibold text-navy-800 sm:inline">{{ $user->name }}</span>
                    <x-icon name="chevron-down" class="size-4 text-navy-400" />
                </summary>
                <div class="absolute right-0 mt-2 w-60 rounded-2xl border border-navy-100 bg-white p-2 shadow-xl">
                    <div class="px-3 py-2">
                        <p class="truncate text-sm font-semibold text-navy-900">{{ $user->email }}</p>
                        <p class="text-xs text-navy-500">{{ $user->role->label() }}</p>
                    </div>
                    @if ($user->isParticipant())
                        <a href="{{ route('portal.profile') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><x-icon name="user" class="size-4" /> Profil</a>
                    @endif
                    <a href="{{ route('account.password') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><x-icon name="lock" class="size-4" /> Ubah kata sandi</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-red-700 hover:bg-red-50"><x-icon name="logout" class="size-4" /> Keluar</button>
                    </form>
                </div>
            </details>
        </div>
    </header>

    <main id="konten" class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <x-flash />
        {{ $slot }}
    </main>
</div>
</body>
</html>
