<?php

namespace App\Http\Controllers\Admin;

use App\Models\AgendaItem;
use App\Models\ProgramStage;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class AgendaController extends CrudController
{
    protected function model(): string
    {
        return AgendaItem::class;
    }

    protected function routeBase(): string
    {
        return 'admin.agenda';
    }

    protected function title(): string
    {
        return 'Agenda';
    }

    protected function singular(): string
    {
        return 'Agenda';
    }

    protected function searchable(): array
    {
        return ['title', 'location'];
    }

    protected function description(): ?string
    {
        return 'Agenda dikelompokkan per tahapan, tanggal, dan sesi di halaman publik. Gunakan status "Rencana" untuk jadwal yang belum pasti, dan label tanggal bila tanggal belum ditetapkan.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->with('stage')->ordered();
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Agenda', 'value' => fn (AgendaItem $a) => new HtmlString('<p class="font-semibold text-navy-950">'.e($a->title).'</p><p class="text-xs text-navy-500">'.e($a->stage?->name ?? 'Tanpa tahapan').'</p>'), 'class' => 'min-w-72'],
            ['label' => 'Waktu', 'value' => fn (AgendaItem $a) => trim($a->dateText().' '.($a->timeText() ? '· '.$a->timeText() : ''))],
            ['label' => 'Lokasi', 'value' => fn (AgendaItem $a) => $a->location ?: '—'],
            ['label' => 'Status', 'value' => fn (AgendaItem $a) => $a->statusLabel()],
            ['label' => 'Publik', 'value' => fn (AgendaItem $a) => $a->is_public ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Judul sesi/kegiatan', 'required' => true, 'span' => 'full'],
            ['name' => 'program_stage_id', 'label' => 'Tahapan program', 'type' => 'select', 'options' => ProgramStage::query()->ordered()->pluck('name', 'id')->all(), 'placeholder' => 'Tanpa tahapan'],
            ['name' => 'session_type', 'label' => 'Jenis sesi', 'placeholder' => 'diskusi, workshop, demo…'],
            ['name' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
            ['name' => 'date_label', 'label' => 'Label tanggal (bila belum pasti)', 'placeholder' => 'November 2026'],
            ['name' => 'start_time', 'label' => 'Jam mulai (WITA)', 'type' => 'time'],
            ['name' => 'end_time', 'label' => 'Jam selesai (WITA)', 'type' => 'time'],
            ['name' => 'location', 'label' => 'Lokasi'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Options::agendaStatuses(), 'placeholder' => false],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'speakers', 'label' => 'Narasumber/penanggung jawab', 'type' => 'textarea', 'rows' => 2, 'help' => 'Isi hanya nama yang sudah terkonfirmasi.'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'is_public', 'label' => 'Tampilkan di situs publik', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'program_stage_id' => ['nullable', Rule::exists('program_stages', 'id')],
            'session_type' => ['nullable', 'string', 'max:40'],
            'date' => ['nullable', 'date'],
            'date_label' => ['nullable', 'string', 'max:100'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:190'],
            'status' => ['required', Rule::in(array_keys(Options::agendaStatuses()))],
            'description' => ['nullable', 'string', 'max:4000'],
            'speakers' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
