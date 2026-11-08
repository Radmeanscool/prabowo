<x-layouts.portal :title="$title">
    <x-page-header :title="$title" :subtitle="$description">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <label class="sr-only" for="q">Cari</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari…" class="form-input w-48 py-2">
            </form>
            @foreach ($extraActions as [$label, $url])
                <a href="{{ $url }}" class="btn btn-outline">{{ $label }}</a>
            @endforeach
            @if ($canCreate)
                <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah {{ \Illuminate\Support\Str::lower($singular) }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($items->isEmpty())
        <x-empty title="Belum ada data" :text="request('q') ? 'Tidak ada hasil untuk pencarian ini.' : null" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            @foreach ($columns as $column)
                                <td class="{{ $column['class'] ?? '' }}">{{ $column['value']($item) }}</td>
                            @endforeach
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route($routeBase.'.edit', $item) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-3.5" /> Ubah</a>
                                @if ($canDeleteRoute)
                                    <form method="POST" action="{{ route($routeBase.'.destroy', $item) }}" class="inline" data-confirm="Hapus data ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger-outline btn-sm" aria-label="Hapus"><x-icon name="trash" class="size-3.5" /></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @endif
</x-layouts.portal>
