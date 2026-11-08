<x-layouts.portal title="Proposal Saya">
    <x-page-header eyebrow="Tim {{ $team->name }}" title="Proposal Saya" subtitle="Proposal disusun bersama oleh tim. Ketua tim yang mengirim proposal.">
        <x-slot:actions>
            @if ($canCreate)
                <a href="{{ route('portal.proposals.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Proposal Baru</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @forelse ($proposals as $proposal)
        <div class="card card-body mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p class="text-lg font-bold text-navy-950">{{ $proposal->product_name }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-status-badge :status="$proposal->status" />
                    <x-theme-badge :theme="$proposal->theme" />
                    @if ($proposal->challenge)<x-badge>{{ $proposal->challenge->title }}</x-badge>@endif
                </div>
                <p class="mt-2 text-xs text-navy-500">
                    Dibuat {{ fmt_date($proposal->created_at) }}
                    @if ($proposal->submitted_at) · Dikirim {{ fmt_date($proposal->submitted_at, true) }} (versi {{ $proposal->version }}) @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $proposal)
                    <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-4" /> Ubah</a>
                @endcan
                <a href="{{ route('portal.proposals.show', $proposal) }}" class="btn btn-outline btn-sm">Lihat Proposal</a>
                <a href="{{ route('portal.proposals.files', $proposal) }}" class="btn btn-outline btn-sm">Berkas</a>
                <a href="{{ route('portal.proposals.status', $proposal) }}" class="btn btn-outline btn-sm">Status</a>
            </div>
        </div>
    @empty
        <x-empty icon="file" title="Belum ada proposal" text="Mulai dengan memilih tema, lalu pilih tantangan mitra yang ingin dijawab tim Anda.">
            <a href="{{ route('portal.proposals.create') }}" class="btn btn-primary">Pilih Tema & Tantangan</a>
        </x-empty>
    @endforelse
</x-layouts.portal>
