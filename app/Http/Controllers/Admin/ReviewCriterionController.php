<?php

namespace App\Http\Controllers\Admin;

use App\Models\ReviewCriterion;
use App\Models\ReviewPhase;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kriteria penilaian per fase: deskripsi/panduan, skor maksimum, bobot, dan tema (opsional). */
class ReviewCriterionController extends CrudController
{
    protected function model(): string
    {
        return ReviewCriterion::class;
    }

    protected function routeBase(): string
    {
        return 'admin.criteria';
    }

    protected function title(): string
    {
        return 'Kriteria penilaian';
    }

    protected function singular(): string
    {
        return 'Kriteria';
    }

    protected function columns(): array
    {
        return [];
    }

    protected function indexUrl(): string
    {
        return route('admin.review-phases.index');
    }

    protected function prepareNew(Model $model): void
    {
        $model->review_phase_id = (int) request('phase') ?: null;
        $model->max_score = 5;
    }

    public function store(Request $request): RedirectResponse
    {
        parent::store($request);

        return redirect()->route('admin.review-phases.index')->with('success', 'Kriteria ditambahkan.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        parent::update($request, $id);

        return redirect()->route('admin.review-phases.index')->with('success', 'Kriteria diperbarui. Skor yang sudah dikirim tidak dihitung ulang otomatis; buka kembali penilaian bila perlu.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $criterion = $this->find($id);
        if ($criterion->hasScores()) {
            $criterion->forceFill(['is_active' => false])->save();

            return redirect()->route('admin.review-phases.index')->with('warning', 'Kriteria sudah dipakai dalam penilaian sehingga dinonaktifkan, bukan dihapus.');
        }
        parent::destroy($id);

        return redirect()->route('admin.review-phases.index')->with('success', 'Kriteria dihapus.');
    }

    public function index(Request $request): View
    {
        abort(404);
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'review_phase_id', 'label' => 'Fase', 'type' => 'select', 'options' => ReviewPhase::query()->ordered()->pluck('name', 'id')->all(), 'required' => true],
            ['name' => 'theme_id', 'label' => 'Khusus tema', 'type' => 'select', 'options' => Theme::query()->active()->pluck('name', 'id')->all(), 'placeholder' => 'Semua tema'],
            ['name' => 'name', 'label' => 'Nama kriteria', 'required' => true, 'span' => 'full'],
            ['name' => 'description', 'label' => 'Panduan penilaian', 'type' => 'textarea', 'rows' => 3, 'help' => 'Ditampilkan kepada reviewer di formulir penilaian.'],
            ['name' => 'max_score', 'label' => 'Skor maksimum', 'type' => 'number', 'min' => 1, 'max' => 10, 'required' => true],
            ['name' => 'weight', 'label' => 'Bobot (%)', 'type' => 'number', 'min' => 0, 'max' => 100, 'step' => '0.5', 'required' => true],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'requires_comment', 'label' => 'Wajib komentar/bukti', 'type' => 'checkbox'],
            ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'value' => fn ($m) => $m->exists ? $m->is_active : true],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'review_phase_id' => ['required', Rule::exists('review_phases', 'id')],
            'theme_id' => ['nullable', Rule::exists('themes', 'id')],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_score' => ['required', 'integer', 'between:1,10'],
            'weight' => ['required', 'numeric', 'between:0,100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'requires_comment' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
