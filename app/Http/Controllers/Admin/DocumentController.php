<?php

namespace App\Http\Controllers\Admin;

use App\Models\Document;
use App\Rules\FileContentType;
use App\Support\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class DocumentController extends CrudController
{
    protected function model(): string
    {
        return Document::class;
    }

    protected function routeBase(): string
    {
        return 'admin.documents';
    }

    protected function title(): string
    {
        return 'Dokumen dan sumber daya';
    }

    protected function singular(): string
    {
        return 'Dokumen';
    }

    protected function searchable(): array
    {
        return ['title', 'description'];
    }

    protected function uploadDirectory(): string
    {
        return 'documents';
    }

    protected function description(): ?string
    {
        return 'Panduan, templat, kebijakan, dan materi. Atur visibilitas: publik, peserta, reviewer, atau admin saja. Materi hasil visitasi legal bersifat rahasia: simpan dengan visibilitas "Admin saja".';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Dokumen', 'value' => fn (Document $d) => new HtmlString('<p class="font-semibold text-navy-950">'.e($d->title).'</p><p class="text-xs text-navy-500">'.e($d->categoryLabel()).' · '.($d->hasFile() ? e($d->original_name).' ('.format_bytes($d->size).')' : e($d->external_url ?? '—')).'</p>'), 'class' => 'min-w-72'],
            ['label' => 'Visibilitas', 'value' => fn (Document $d) => $d->visibilityLabel()],
            ['label' => 'Status', 'value' => fn (Document $d) => new HtmlString($d->is_published ? '<span class="badge badge-success">Terbit</span>' : '<span class="badge badge-warning">Draf</span>')],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Judul', 'required' => true, 'span' => 'full'],
            ['name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'options' => Options::documentCategories(), 'placeholder' => false],
            ['name' => 'visibility', 'label' => 'Visibilitas', 'type' => 'select', 'options' => Options::documentVisibilities(), 'placeholder' => false],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'file', 'label' => 'Berkas PDF (maks. 20 MB)', 'type' => 'file', 'column' => 'file_path', 'accept' => 'application/pdf',
                'meta' => ['original_name' => 'original_name', 'mime_type' => 'mime_type', 'size' => 'size'], 'current' => fn ($m) => $m->original_name],
            ['name' => 'external_url', 'label' => 'Atau tautan eksternal', 'type' => 'url', 'placeholder' => 'https://'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'is_published', 'label' => 'Terbitkan', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', Rule::in(array_keys(Options::documentCategories()))],
            'visibility' => ['required', Rule::in(array_keys(Options::documentVisibilities()))],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', new FileContentType(['application/pdf']), 'max:20480'],
            'external_url' => ['nullable', 'url:http,https', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists) {
            $data['slug'] = $this->uniqueSlug($data['title'], $model);
            $data['uploaded_by'] = $request->user()->id;
        }
        if ($request->boolean('is_published') && ! $model->published_at) {
            $data['published_at'] = now();
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
