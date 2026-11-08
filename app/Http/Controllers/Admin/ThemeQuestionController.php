<?php

namespace App\Http\Controllers\Admin;

use App\Models\Theme;
use App\Models\ThemeQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ThemeQuestionController extends CrudController
{
    protected function model(): string
    {
        return ThemeQuestion::class;
    }

    protected function routeBase(): string
    {
        return 'admin.theme-questions';
    }

    protected function title(): string
    {
        return 'Pertanyaan khusus tema';
    }

    protected function singular(): string
    {
        return 'Pertanyaan';
    }

    protected function searchable(): array
    {
        return ['question'];
    }

    protected function description(): ?string
    {
        return 'Pertanyaan tambahan (opsional) yang muncul di formulir proposal sesuai tema. Kosongkan tema untuk pertanyaan yang berlaku bagi semua tema.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->with('theme')->orderBy('theme_id')->orderBy('sort_order');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Pertanyaan', 'value' => fn (ThemeQuestion $q) => $q->question, 'class' => 'min-w-80 font-medium'],
            ['label' => 'Tema', 'value' => fn (ThemeQuestion $q) => $q->theme?->name ?? 'Semua tema'],
            ['label' => 'Wajib', 'value' => fn (ThemeQuestion $q) => $q->is_required ? 'Ya' : 'Tidak'],
            ['label' => 'Aktif', 'value' => fn (ThemeQuestion $q) => $q->is_active ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'theme_id', 'label' => 'Tema', 'type' => 'select', 'options' => Theme::query()->active()->pluck('name', 'id')->all(), 'placeholder' => 'Semua tema'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'question', 'label' => 'Pertanyaan', 'type' => 'textarea', 'rows' => 2, 'required' => true],
            ['name' => 'help_text', 'label' => 'Panduan pengisian', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'word_limit', 'label' => 'Batas kata', 'type' => 'number', 'min' => 10, 'help' => 'Kosongkan untuk memakai batas kata umum.'],
            ['name' => 'is_required', 'label' => 'Wajib dijawab sebelum proposal dikirim', 'type' => 'checkbox'],
            ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'value' => fn ($m) => $m->exists ? $m->is_active : true],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'theme_id' => ['nullable', Rule::exists('themes', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'question' => ['required', 'string', 'max:1000'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'word_limit' => ['nullable', 'integer', 'min:10', 'max:2000'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
