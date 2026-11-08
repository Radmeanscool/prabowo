<?php

namespace App\Http\Controllers\Admin;

use App\Models\ReviewPhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReviewPhaseController extends CrudController
{
    protected function model(): string
    {
        return ReviewPhase::class;
    }

    protected function routeBase(): string
    {
        return 'admin.review-phases';
    }

    protected function title(): string
    {
        return 'Rubrik dan kriteria';
    }

    protected function singular(): string
    {
        return 'Fase penilaian';
    }

    protected function columns(): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        return view('admin.review-phases.index', [
            'phases' => ReviewPhase::query()->ordered()->with(['criteria.theme'])->withCount('assignments')->get(),
        ]);
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama fase', 'required' => true],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'starts_at', 'label' => 'Mulai penilaian', 'type' => 'datetime'],
            ['name' => 'ends_at', 'label' => 'Batas penilaian', 'type' => 'datetime', 'help' => 'Dipakai sebagai batas default penugasan reviewer.'],
            ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'value' => fn ($m) => $m->exists ? $m->is_active : true],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists) {
            $data['code'] = Str::limit(Str::snake(Str::slug($data['name'], ' ')), 40, '').'_'.Str::lower(Str::random(4));
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    protected function canDelete(Model $model): bool
    {
        return ! $model->assignments()->exists();
    }
}
