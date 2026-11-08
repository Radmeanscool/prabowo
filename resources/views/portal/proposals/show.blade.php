<x-layouts.portal title="Lihat Proposal">
    <x-page-header eyebrow="Proposal tim" :title="$proposal->product_name">
        <x-slot:actions>
            <x-status-badge :status="$proposal->status" />
            @can('update', $proposal)
                <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-4" /> Ubah</a>
            @endcan
            <a href="{{ route('portal.proposals.status', $proposal) }}" class="btn btn-outline btn-sm">Status</a>
        </x-slot:actions>
    </x-page-header>

    @unless ($proposal->isEditableByParticipant())
        <p class="mb-6 flex items-center gap-2 rounded-xl border border-navy-100 bg-white px-4 py-3 text-sm text-navy-600"><x-icon name="lock" class="size-4" /> Proposal terkunci sejak dikirim pada {{ fmt_date($proposal->submitted_at, true) }} (versi {{ $proposal->version }}).</p>
    @endunless

    @include('proposals.content', ['proposal' => $proposal, 'viewer' => 'participant'])
</x-layouts.portal>
