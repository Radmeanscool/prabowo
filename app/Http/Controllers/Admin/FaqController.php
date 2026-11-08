<?php

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FaqController extends CrudController
{
    protected function model(): string
    {
        return Faq::class;
    }

    protected function routeBase(): string
    {
        return 'admin.faqs';
    }

    protected function title(): string
    {
        return 'FAQ';
    }

    protected function singular(): string
    {
        return 'FAQ';
    }

    protected function searchable(): array
    {
        return ['question', 'answer'];
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Urutan', 'value' => fn (Faq $f) => $f->sort_order, 'class' => 'tabular-nums'],
            ['label' => 'Pertanyaan', 'value' => fn (Faq $f) => $f->question, 'class' => 'min-w-80 font-medium'],
            ['label' => 'Kategori', 'value' => fn (Faq $f) => $f->category ?: '—'],
            ['label' => 'Terbit', 'value' => fn (Faq $f) => $f->is_published ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'question', 'label' => 'Pertanyaan', 'required' => true, 'span' => 'full'],
            ['name' => 'category', 'label' => 'Kategori'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'answer', 'label' => 'Jawaban', 'type' => 'markdown', 'rows' => 6, 'required' => true],
            ['name' => 'is_published', 'label' => 'Terbit', 'type' => 'checkbox', 'value' => fn ($m) => $m->exists ? $m->is_published : true],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'answer' => ['required', 'string', 'max:10000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
