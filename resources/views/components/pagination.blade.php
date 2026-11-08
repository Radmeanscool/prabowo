@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-navy-500">
            Menampilkan <span class="font-semibold text-navy-800">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-navy-800">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-navy-800">{{ $paginator->total() }}</span>
        </p>
        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-outline opacity-40" aria-disabled="true">Sebelumnya</span>
            @else
                <a class="btn btn-sm btn-outline" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-navy-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm btn-dark" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="btn btn-sm btn-ghost" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a class="btn btn-sm btn-outline" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
            @else
                <span class="btn btn-sm btn-outline opacity-40" aria-disabled="true">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
