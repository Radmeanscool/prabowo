<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

class PageController extends CrudController
{
    protected function model(): string
    {
        return Page::class;
    }

    protected function routeBase(): string
    {
        return 'admin.pages';
    }

    protected function title(): string
    {
        return 'Halaman';
    }

    protected function singular(): string
    {
        return 'Halaman';
    }

    protected function searchable(): array
    {
        return ['title'];
    }

    protected function description(): ?string
    {
        return 'Halaman Tentang, Ketentuan, Privasi, dan Syarat. Hanya cantumkan informasi yang sudah dikonfirmasi panitia.';
    }

    protected function indexActions(): array
    {
        return [['Kelola FAQ', route('admin.faqs.index')]];
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->orderBy('title');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Halaman', 'value' => fn (Page $p) => new HtmlString('<p class="font-semibold text-navy-950">'.e($p->title).'</p><p class="font-mono text-xs text-navy-500">/'.e($p->slug).'</p>')],
            ['label' => 'Status', 'value' => fn (Page $p) => $p->is_published ? 'Terbit' : 'Draf'],
            ['label' => 'Diperbarui', 'value' => fn (Page $p) => fmt_date($p->updated_at, true)],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Judul', 'required' => true, 'span' => 'full'],
            ['name' => 'summary', 'label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'body', 'label' => 'Isi', 'type' => 'markdown', 'rows' => 20, 'required' => true],
            ['name' => 'is_published', 'label' => 'Terbit', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        $data['updated_by'] = $request->user()->id;

        return $data;
    }
}
