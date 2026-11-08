<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProgramStage;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProgramStageController extends CrudController
{
    protected function model(): string
    {
        return ProgramStage::class;
    }

    protected function routeBase(): string
    {
        return 'admin.stages';
    }

    protected function title(): string
    {
        return 'Tahapan program';
    }

    protected function singular(): string
    {
        return 'Tahapan';
    }

    protected function description(): ?string
    {
        return 'Sebelas tahapan program 12 bulan dalam 4 fase. Tahap lanjutan (pendanaan purwarupa hingga adopsi) sudah tersedia di model data agar program berlanjut tanpa desain ulang.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->withCount('proposals')->orderBy('sort_order');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Urutan', 'value' => fn (ProgramStage $s) => $s->sort_order, 'class' => 'tabular-nums'],
            ['label' => 'Tahapan', 'value' => fn (ProgramStage $s) => new HtmlString('<p class="font-semibold text-navy-950">'.e($s->name).'</p><p class="text-xs text-navy-500">'.e($s->phase_number ? 'Fase '.$s->phase_number.' · '.$s->phase_name : $s->phase_name).'</p>'), 'class' => 'min-w-64'],
            ['label' => 'Waktu', 'value' => fn (ProgramStage $s) => $s->month_label],
            ['label' => 'Status', 'value' => fn (ProgramStage $s) => $s->statusLabel()],
            ['label' => 'Proposal', 'value' => fn (ProgramStage $s) => $s->proposals_count, 'class' => 'tabular-nums'],
            ['label' => 'Publik', 'value' => fn (ProgramStage $s) => $s->is_public ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama tahapan', 'required' => true],
            ['name' => 'name_id', 'label' => 'Nama dalam bahasa Indonesia'],
            ['name' => 'phase_number', 'label' => 'Nomor fase (1–4)', 'type' => 'number', 'min' => 1, 'max' => 9, 'help' => 'Kosongkan untuk tahap lintas fase.'],
            ['name' => 'phase_name', 'label' => 'Nama fase'],
            ['name' => 'month_label', 'label' => 'Label waktu', 'placeholder' => 'Bulan 2–4'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Options::stageStatuses(), 'placeholder' => false],
            ['name' => 'start_month', 'label' => 'Bulan mulai (1–12)', 'type' => 'number', 'min' => 1, 'max' => 24],
            ['name' => 'end_month', 'label' => 'Bulan selesai (1–12)', 'type' => 'number', 'min' => 1, 'max' => 24],
            ['name' => 'starts_on', 'label' => 'Tanggal mulai (bila sudah pasti)', 'type' => 'date'],
            ['name' => 'ends_on', 'label' => 'Tanggal selesai (bila sudah pasti)', 'type' => 'date'],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'activities', 'label' => 'Kegiatan (satu per baris)', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'output', 'label' => 'Luaran', 'span' => 'full'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'is_public', 'label' => 'Tampilkan di situs publik', 'type' => 'checkbox', 'value' => fn ($m) => $m->exists ? $m->is_public : true],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'name_id' => ['nullable', 'string', 'max:190'],
            'phase_number' => ['nullable', 'integer', 'between:1,9'],
            'phase_name' => ['nullable', 'string', 'max:190'],
            'month_label' => ['nullable', 'string', 'max:64'],
            'status' => ['required', Rule::in(array_keys(Options::stageStatuses()))],
            'start_month' => ['nullable', 'integer', 'between:1,24'],
            'end_month' => ['nullable', 'integer', 'between:1,24', 'gte:start_month'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:4000'],
            'activities' => ['nullable', 'string', 'max:4000'],
            'output' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_public' => ['nullable', 'boolean'],
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
        // Tahap inti dipakai pemetaan status proposal; hanya tahap tambahan yang boleh dihapus.
        $core = ['challenge_discovery', 'open_call', 'bootcamp', 'mentoring_sprint', 'prototype_development_sprint', 'demo_day', 'prototype_funding', 'pilot_project', 'incubation_downstreaming', 'adoption_impact', 'monitoring_evaluation'];

        return ! in_array($model->code, $core, true) && ! $model->proposals()->exists();
    }
}
