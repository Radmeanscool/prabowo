<x-layouts.portal title="Pilih Proposal">
    <x-page-header title="Pilih proposal untuk dinilai" :subtitle="'Fitur ini diaktifkan panitia. Fase: '.($phase?->name ?? '—').'. Identitas tim tidak ditampilkan di daftar ini.'" />
    @if ($proposals->isEmpty())
        <x-empty icon="inbox" title="Tidak ada proposal tersedia" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Proposal</th><th>Tema</th><th>Tantangan</th><th class="sr-only">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($proposals as $proposal)
                        <tr>
                            <td class="font-semibold">{{ $proposal->product_name }}</td>
                            <td><x-theme-badge :theme="$proposal->theme" /></td>
                            <td>{{ $proposal->challenge?->title ?? '—' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('reviewer.self-select.store', $proposal) }}" data-confirm="Ambil proposal ini untuk dinilai? Pastikan Anda tidak memiliki konflik kepentingan.">
                                    @csrf
                                    <button class="btn btn-outline btn-sm">Ambil untuk dinilai</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $proposals->links() }}
    @endif
</x-layouts.portal>
