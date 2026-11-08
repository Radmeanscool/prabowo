<?php

namespace App\Http\Controllers\Admin;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Tiga tema inti (Ketahanan Pangan, Kesehatan, Hybrid) dapat diubah namanya, tidak dihapus. */
class ThemeController extends CrudController
{
    protected function model(): string
    {
        return Theme::class;
    }

    protected function routeBase(): string
    {
        return 'admin.themes';
    }

    protected function title(): string
    {
        return 'Tema proposal';
    }

    protected function singular(): string
    {
        return 'Tema';
    }

    protected function description(): ?string
    {
        return 'Tema adalah data terkendali: menentukan tantangan yang tampil, lencana proposal, kategori produk publik, filter laporan, serta pertanyaan dan kriteria khusus tema.';
    }

    protected function indexActions(): array
    {
        return [['Pertanyaan khusus tema', route('admin.theme-questions.index')]];
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Tema', 'value' => fn (Theme $t) => new HtmlString('<span class="badge '.$t->cssClass().'">'.e($t->name).'</span> <span class="text-xs text-navy-500">'.e($t->name_en).'</span>')],
            ['label' => 'Kode', 'value' => fn (Theme $t) => $t->code, 'class' => 'font-mono text-xs'],
            ['label' => 'Deskripsi', 'value' => fn (Theme $t) => Str::limit($t->description, 140), 'class' => 'min-w-80 text-navy-600'],
            ['label' => 'Proposal', 'value' => fn (Theme $t) => $t->proposals()->count(), 'class' => 'tabular-nums'],
            ['label' => 'Aktif', 'value' => fn (Theme $t) => $t->is_active ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama (Indonesia)', 'required' => true],
            ['name' => 'name_en', 'label' => 'Nama (Inggris)'],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'color', 'label' => 'Warna', 'type' => 'color'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'is_active', 'label' => 'Aktif (dapat dipilih peserta)', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
