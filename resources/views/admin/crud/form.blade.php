<x-layouts.portal :title="$title">
    <x-page-header :eyebrow="$singular" :title="$title">
        <x-slot:actions>
            <a href="{{ $backUrl }}" class="btn btn-ghost"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $action }}" @if ($hasFiles) enctype="multipart/form-data" @endif class="card card-body max-w-4xl">
        @csrf
        @if ($method === 'PUT') @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach ($fields as $field)
                @include('admin.crud.field', ['field' => $field, 'model' => $model])
            @endforeach
        </div>
        <div class="mt-6 flex gap-2 border-t border-navy-100 pt-5">
            <button type="submit" class="btn btn-dark">Simpan</button>
            <a href="{{ $backUrl }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.portal>
