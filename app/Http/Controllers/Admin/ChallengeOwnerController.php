<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChallengeOwner;
use App\Models\Theme;
use App\Rules\FileContentType;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class ChallengeOwnerController extends CrudController
{
    protected function model(): string
    {
        return ChallengeOwner::class;
    }

    protected function routeBase(): string
    {
        return 'admin.challenge-owners';
    }

    protected function title(): string
    {
        return 'Mitra challenge owner';
    }

    protected function singular(): string
    {
        return 'Mitra';
    }

    protected function searchable(): array
    {
        return ['name', 'short_name'];
    }

    protected function uploadDirectory(): string
    {
        return 'challenge-owners';
    }

    protected function description(): ?string
    {
        return 'Pemilik masalah nyata: merumuskan tantangan, menyediakan data/fasilitas, mendampingi, memvalidasi purwarupa, dan menjadi calon pengguna. Mitra bukan reviewer. Nama dan logo hanya tampil publik setelah ada izin.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->with('theme')->withCount('challenges')->orderBy('sort_order')->orderBy('name');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Mitra', 'value' => fn (ChallengeOwner $o) => new HtmlString('<p class="font-semibold text-navy-950">'.e($o->name).'</p><p class="text-xs text-navy-500">'.e($o->typeLabel()).'</p>'), 'class' => 'min-w-72'],
            ['label' => 'Bidang', 'value' => fn (ChallengeOwner $o) => $o->theme?->name ?? '—'],
            ['label' => 'Partisipasi', 'value' => fn (ChallengeOwner $o) => Options::label(Options::participationStatuses(), $o->participation_status)],
            ['label' => 'Publik', 'value' => fn (ChallengeOwner $o) => new HtmlString($o->is_public ? '<span class="badge badge-success">Tampil</span>' : '<span class="badge badge-neutral">Tersembunyi</span>')],
            ['label' => 'Tantangan', 'value' => fn (ChallengeOwner $o) => $o->challenges_count, 'class' => 'tabular-nums'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama lengkap', 'required' => true, 'span' => 'full'],
            ['name' => 'short_name', 'label' => 'Nama singkat'],
            ['name' => 'type', 'label' => 'Jenis', 'type' => 'select', 'options' => Options::challengeOwnerTypes(), 'required' => true],
            ['name' => 'theme_id', 'label' => 'Bidang utama', 'type' => 'select', 'options' => Theme::query()->active()->pluck('name', 'id')->all(), 'placeholder' => 'Belum ditetapkan'],
            ['name' => 'participation_status', 'label' => 'Status partisipasi', 'type' => 'select', 'options' => Options::participationStatuses(), 'required' => true, 'placeholder' => false],
            ['name' => 'description', 'label' => 'Profil mitra (publik)', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'initial_focus', 'label' => 'Fokus tantangan (publik)', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'website', 'label' => 'Situs web', 'type' => 'url', 'placeholder' => 'https://'],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'logo', 'label' => 'Logo (JPEG/PNG/WebP, maks. 2 MB)', 'type' => 'image', 'column' => 'logo_path', 'accept' => 'image/jpeg,image/png,image/webp',
                'preview' => fn ($m) => route('admin.media', ['type' => 'owner', 'id' => $m->id])],
            ['name' => 'is_public', 'label' => 'Tampilkan nama dan logo mitra di situs publik', 'type' => 'checkbox', 'help' => 'Aktifkan hanya setelah mitra memberi izin publikasi.'],
            ['type' => 'section', 'label' => 'Kontak internal (tidak pernah publik)'],
            ['name' => 'contact_name', 'label' => 'Nama narahubung'],
            ['name' => 'contact_position', 'label' => 'Jabatan'],
            ['name' => 'contact_email', 'label' => 'Surel', 'type' => 'email'],
            ['name' => 'contact_phone', 'label' => 'Telepon'],
            ['name' => 'internal_notes', 'label' => 'Catatan internal', 'type' => 'textarea', 'rows' => 3],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Options::challengeOwnerTypes()))],
            'theme_id' => ['nullable', Rule::exists('themes', 'id')],
            'participation_status' => ['required', Rule::in(array_keys(Options::participationStatuses()))],
            'description' => ['nullable', 'string', 'max:4000'],
            'initial_focus' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', new FileContentType(['image/jpeg', 'image/png', 'image/webp']), 'max:2048'],
            'is_public' => ['nullable', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_position' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists) {
            $data['slug'] = $this->uniqueSlug($data['short_name'] ?: $data['name'], $model);
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    protected function canDelete(Model $model): bool
    {
        return ! $model->challenges()->exists();
    }
}
