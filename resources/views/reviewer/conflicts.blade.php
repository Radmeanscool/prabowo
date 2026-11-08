<x-layouts.portal title="Konflik Kepentingan">
    <x-page-header title="Konflik kepentingan" subtitle="Nyatakan hubungan kerja, kekeluargaan, bimbingan, atau kepentingan lain dengan tim pengusul. Juri tidak menilai tim yang dibimbingnya." />
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="card-title">Pernyataan Anda</h2>
                <ul class="mt-3 divide-y divide-navy-100">
                    @forelse ($declarations as $declaration)
                        <li class="py-3 text-sm">
                            <p class="font-semibold">{{ $declaration->proposal?->product_name ?? ($declaration->institution ? 'Institusi: '.$declaration->institution : 'Tim') }}</p>
                            <p class="mt-0.5 text-navy-600">{{ $declaration->reason }}</p>
                            <p class="mt-0.5 text-xs text-navy-400">{{ fmt_date($declaration->created_at, true) }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-navy-500">Belum ada pernyataan konflik kepentingan.</li>
                    @endforelse
                </ul>
            </div>
            @if ($assignments->isNotEmpty())
                <div class="card card-body">
                    <h2 class="card-title">Konflik pada proposal yang ditugaskan</h2>
                    <p class="mt-1 text-sm text-navy-500">Buka proposal terkait lalu gunakan tombol "Nyatakan Konflik Kepentingan".</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($assignments as $assignment)
                            <li><a href="{{ route('reviewer.assignments.show', $assignment) }}#konflik" class="font-semibold text-forest-700">{{ $assignment->proposal->product_name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <form method="POST" action="{{ route('reviewer.conflicts.store') }}" class="card card-body space-y-4 self-start">
            @csrf
            <h2 class="card-title">Nyatakan konflik dengan institusi</h2>
            <p class="text-sm text-navy-500">Admin tidak akan dapat menugaskan proposal dari institusi ini kepada Anda.</p>
            <x-form.input name="institution" label="Nama institusi" required />
            <x-form.textarea name="reason" label="Alasan" rows="3" required />
            <button class="btn btn-dark">Simpan Pernyataan</button>
        </form>
    </div>
</x-layouts.portal>
