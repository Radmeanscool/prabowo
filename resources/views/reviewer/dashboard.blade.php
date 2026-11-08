<x-layouts.portal title="Dashboard">
    <x-page-header eyebrow="Dashboard Reviewer/Juri" :title="'Halo, '.$user->name" subtitle="Anda hanya melihat proposal yang ditugaskan panitia. Nyatakan konflik kepentingan sebelum menilai bila ada hubungan dengan tim pengusul." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Perlu dinilai" :value="$counts['open']" icon="clipboard" :href="route('reviewer.assignments.index')" />
        <x-stat label="Draf penilaian" :value="$counts['draft']" icon="pencil" :href="route('reviewer.reviews.index', ['status' => 'draft'])" />
        <x-stat label="Penilaian terkirim" :value="$counts['submitted']" icon="check-circle" :href="route('reviewer.reviews.index', ['status' => 'submitted'])" />
        <x-stat label="Konflik dinyatakan" :value="$counts['conflict']" icon="shield" :href="route('reviewer.conflicts.index')" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body flex items-center justify-between border-b border-navy-100">
                <h2 class="card-title">Penugasan terbuka</h2>
                <a href="{{ route('reviewer.assignments.index') }}" class="text-sm font-semibold text-forest-700">Semua penugasan</a>
            </div>
            <ul class="divide-y divide-navy-100">
                @forelse ($upcoming as $assignment)
                    <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <p class="font-semibold text-navy-950">{{ $assignment->proposal->product_name }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-2 text-xs">
                                <x-theme-badge :theme="$assignment->proposal->theme" />
                                <x-badge>{{ $assignment->phase->name }}</x-badge>
                                @if ($assignment->due_at)<x-badge tone="{{ $assignment->due_at->isPast() ? 'danger' : 'warning' }}">Batas {{ fmt_date($assignment->due_at) }}</x-badge>@endif
                            </div>
                        </div>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="btn btn-dark btn-sm shrink-0">Nilai Proposal</a>
                    </li>
                @empty
                    <li class="p-6"><x-empty icon="check-circle" title="Tidak ada penugasan terbuka" text="Penugasan baru dari admin akan muncul di sini." /></li>
                @endforelse
            </ul>
        </div>
        <div class="card card-body self-start">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Pengumuman</h2>
                <a href="{{ route('reviewer.announcements') }}" class="text-sm font-semibold text-forest-700">Semua</a>
            </div>
            @forelse ($announcements as $announcement)
                <div class="mt-4 border-t border-navy-100 pt-4 first-of-type:border-0 first-of-type:pt-0">
                    <p class="text-xs text-navy-400">{{ fmt_date($announcement->published_at ?? $announcement->created_at) }}</p>
                    <p class="mt-0.5 font-semibold text-navy-900">{{ $announcement->title }}</p>
                </div>
            @empty
                <p class="mt-3 text-sm text-navy-500">Belum ada pengumuman.</p>
            @endforelse
        </div>
    </div>
</x-layouts.portal>
