<?php

namespace App\Http\Controllers\Admin;

use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Theme;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class ChallengeController extends CrudController
{
    protected function model(): string
    {
        return Challenge::class;
    }

    protected function routeBase(): string
    {
        return 'admin.challenges';
    }

    protected function title(): string
    {
        return 'Tantangan';
    }

    protected function singular(): string
    {
        return 'Tantangan';
    }

    protected function searchable(): array
    {
        return ['title', 'summary'];
    }

    protected function description(): ?string
    {
        return 'Tantangan nyata dari mitra. Hanya tantangan berstatus "Terbit" yang tampil publik dan dapat dipilih peserta. Data rahasia mitra hanya tampil bila ditandai publik.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->with(['theme', 'owner', 'themes'])->withCount('proposals')->orderBy('sort_order')->orderBy('title');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Tantangan', 'value' => fn (Challenge $c) => new HtmlString('<p class="font-semibold text-navy-950">'.e($c->title).'</p><p class="text-xs text-navy-500">'.e($c->owner?->displayName() ?? 'Mitra belum ditetapkan').'</p>'), 'class' => 'min-w-72'],
            ['label' => 'Tema utama', 'value' => fn (Challenge $c) => $c->theme?->name],
            ['label' => 'Dapat dipilih tema', 'value' => fn (Challenge $c) => $c->themes->pluck('name')->implode(', ') ?: '—'],
            ['label' => 'Status', 'value' => fn (Challenge $c) => new HtmlString('<span class="badge badge-'.($c->status === 'published' ? 'success' : ($c->status === 'archived' ? 'neutral' : 'warning')).'">'.e(Options::label(Options::challengeStatuses(), $c->status)).'</span>')],
            ['label' => 'Proposal', 'value' => fn (Challenge $c) => $c->proposals_count, 'class' => 'tabular-nums'],
        ];
    }

    protected function fields(?Model $model): array
    {
        $themes = Theme::query()->active()->pluck('name', 'id')->all();

        return [
            ['type' => 'section', 'label' => 'Identitas tantangan'],
            ['name' => 'title', 'label' => 'Judul tantangan', 'required' => true, 'span' => 'full'],
            ['name' => 'theme_id', 'label' => 'Tema utama', 'type' => 'select', 'options' => $themes, 'required' => true],
            ['name' => 'challenge_owner_id', 'label' => 'Mitra pemilik tantangan', 'type' => 'select', 'options' => ChallengeOwner::query()->orderBy('name')->pluck('name', 'id')->all(), 'placeholder' => 'Belum ditetapkan'],
            ['name' => 'eligible_themes', 'label' => 'Tampilkan untuk proposal bertema', 'type' => 'multicheck', 'options' => $themes, 'relation' => 'themes',
                'checked' => fn ($m) => $m->exists ? $m->themes->pluck('id')->all() : [], 'help' => 'Peserta yang memilih tema ini dapat memilih tantangan ini. Proposal Hybrid biasanya dapat memilih tantangan pangan maupun kesehatan.'],
            ['name' => 'summary', 'label' => 'Ringkasan singkat (publik)', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'challenge_statement', 'label' => 'Rumusan tantangan', 'type' => 'textarea', 'rows' => 2, 'help' => 'Pola: Bagaimana kita dapat [tindakan] bagi [pengguna] agar [hasil terukur] dengan memperhatikan [batasan]?'],
            ['type' => 'section', 'label' => 'Uraian (publik bila terbit)'],
            ['name' => 'problem_statement', 'label' => 'Pernyataan masalah', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'affected_users', 'label' => 'Pengguna terdampak', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'current_baseline', 'label' => 'Kondisi/proses saat ini (baseline)', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'desired_outcome', 'label' => 'Hasil yang diharapkan', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'minimum_functions', 'label' => 'Fungsi minimum', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'success_indicators', 'label' => 'Indikator keberhasilan', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'constraints', 'label' => 'Batasan (teknis, biaya, regulasi, keselamatan, data, operasional)', 'type' => 'textarea', 'rows' => 3],
            ['type' => 'section', 'label' => 'Data yang mungkin rahasia', 'help' => 'Hanya tampil publik bila kotak "tampilkan publik" dicentang.'],
            ['name' => 'available_data', 'label' => 'Data/fasilitas yang tersedia', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'data_public', 'label' => 'Tampilkan data/fasilitas ke publik', 'type' => 'checkbox'],
            ['name' => 'pilot_location', 'label' => 'Kemungkinan lokasi pilot', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'pilot_location_public', 'label' => 'Tampilkan lokasi pilot ke publik', 'type' => 'checkbox'],
            ['name' => 'contact_info', 'label' => 'Kontak/mentor', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'contact_public', 'label' => 'Tampilkan kontak ke publik', 'type' => 'checkbox'],
            ['name' => 'internal_notes', 'label' => 'Catatan internal (tidak pernah publik)', 'type' => 'textarea', 'rows' => 3],
            ['type' => 'section', 'label' => 'Publikasi'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Options::challengeStatuses(), 'required' => true, 'placeholder' => false],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
        ];
    }

    protected function rules(?Model $model): array
    {
        $text = ['nullable', 'string', 'max:6000'];

        return [
            'title' => ['required', 'string', 'max:190'],
            'theme_id' => ['required', Rule::exists('themes', 'id')],
            'challenge_owner_id' => ['nullable', Rule::exists('challenge_owners', 'id')],
            'eligible_themes' => ['nullable', 'array'],
            'eligible_themes.*' => [Rule::exists('themes', 'id')],
            'summary' => ['nullable', 'string', 'max:500'],
            'challenge_statement' => $text, 'problem_statement' => $text, 'affected_users' => $text,
            'current_baseline' => $text, 'desired_outcome' => $text, 'minimum_functions' => $text,
            'success_indicators' => $text, 'constraints' => $text, 'available_data' => $text,
            'pilot_location' => $text, 'contact_info' => $text, 'internal_notes' => $text,
            'data_public' => ['nullable', 'boolean'], 'pilot_location_public' => ['nullable', 'boolean'], 'contact_public' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys(Options::challengeStatuses()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists || $model->title !== $data['title']) {
            $data['slug'] = $model->exists && $model->status === 'published' ? $model->slug : $this->uniqueSlug($data['title'], $model);
        }
        if (($data['status'] ?? null) === 'published' && ! $model->published_at) {
            $data['published_at'] = now();
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    protected function afterSave(Model $model, Request $request): void
    {
        // Tema utama selalu termasuk tema yang boleh memilih tantangan ini.
        $model->themes()->syncWithoutDetaching([$model->theme_id]);
    }

    protected function canDelete(Model $model): bool
    {
        return ! $model->proposals()->exists();
    }
}
