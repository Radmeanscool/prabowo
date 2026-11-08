<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\Person;
use App\Models\Theme;
use App\Models\User;
use App\Rules\FileContentType;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

/** Direktori mentor, juri, dan narasumber. Juri tidak otomatis menjadi mentor. */
class PersonController extends CrudController
{
    protected function model(): string
    {
        return Person::class;
    }

    protected function routeBase(): string
    {
        return 'admin.people';
    }

    protected function title(): string
    {
        return 'Mentor, juri, dan narasumber';
    }

    protected function singular(): string
    {
        return 'Profil';
    }

    protected function searchable(): array
    {
        return ['name', 'affiliation', 'expertise'];
    }

    protected function uploadDirectory(): string
    {
        return 'people';
    }

    protected function description(): ?string
    {
        return 'Rencana program: 30 mentor (15 dosen Unhas + 15 praktisi mitra) dan 7 juri (4 internal Unhas, 3 eksternal). Hubungkan profil juri ke akun reviewer agar sistem dapat mendeteksi konflik bila juri juga membimbing tim. Penugasan mentor ke tim dilakukan di halaman Tim.';
    }

    protected function orderQuery(Builder $query): Builder
    {
        $query->with(['theme', 'user'])->withCount('mentorAssignments')->orderBy('type')->orderBy('sort_order')->orderBy('name');
        if ($type = request('type')) {
            $query->where('type', $type);
        }

        return $query;
    }

    protected function indexActions(): array
    {
        return collect(Options::personTypes())->map(fn ($label, $key) => ['Filter: '.$label, route('admin.people.index', ['type' => $key])])->values()->all();
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Nama', 'value' => fn (Person $p) => new HtmlString('<p class="font-semibold text-navy-950">'.e($p->name).'</p><p class="text-xs text-navy-500">'.e(trim($p->position.' · '.$p->affiliation, ' ·')).'</p>'), 'class' => 'min-w-64'],
            ['label' => 'Peran', 'value' => fn (Person $p) => trim($p->typeLabel().($p->category ? ' · '.$p->categoryLabel() : ''))],
            ['label' => 'Bidang', 'value' => fn (Person $p) => $p->theme?->name ?? '—'],
            ['label' => 'Akun reviewer', 'value' => fn (Person $p) => $p->user?->email ?? '—'],
            ['label' => 'Tim dibimbing', 'value' => fn (Person $p) => $p->mentor_assignments_count, 'class' => 'tabular-nums'],
            ['label' => 'Publik', 'value' => fn (Person $p) => new HtmlString($p->is_public ? '<span class="badge badge-success">Tampil</span>' : '<span class="badge badge-neutral">Tersembunyi</span>')],
        ];
    }

    protected function fields(?Model $model): array
    {
        $reviewers = User::query()->where('role', UserRole::Reviewer->value)->orderBy('name')->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->email.')'])->all();

        return [
            ['name' => 'name', 'label' => 'Nama lengkap dan gelar', 'required' => true, 'span' => 'full'],
            ['name' => 'type', 'label' => 'Peran', 'type' => 'select', 'options' => Options::personTypes(), 'required' => true],
            ['name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'options' => Options::personCategories(), 'placeholder' => '—'],
            ['name' => 'affiliation', 'label' => 'Instansi'],
            ['name' => 'position', 'label' => 'Jabatan'],
            ['name' => 'theme_id', 'label' => 'Bidang', 'type' => 'select', 'options' => Theme::query()->active()->pluck('name', 'id')->all(), 'placeholder' => 'Lintas bidang'],
            ['name' => 'user_id', 'label' => 'Akun reviewer terkait', 'type' => 'select', 'options' => $reviewers, 'placeholder' => 'Tidak ada', 'help' => 'Untuk juri yang menilai lewat portal.'],
            ['name' => 'expertise', 'label' => 'Keahlian', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'bio', 'label' => 'Profil singkat', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'photo', 'label' => 'Foto (JPEG/PNG/WebP, maks. 2 MB)', 'type' => 'image', 'column' => 'photo_path', 'accept' => 'image/jpeg,image/png,image/webp',
                'preview' => fn ($m) => route('admin.media', ['type' => 'person', 'id' => $m->id])],
            ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
            ['name' => 'is_public', 'label' => 'Tampilkan di direktori publik', 'type' => 'checkbox', 'help' => 'Tampilkan hanya profil yang sudah terkonfirmasi.'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(array_keys(Options::personTypes()))],
            'category' => ['nullable', Rule::in(array_keys(Options::personCategories()))],
            'affiliation' => ['nullable', 'string', 'max:190'],
            'position' => ['nullable', 'string', 'max:190'],
            'theme_id' => ['nullable', Rule::exists('themes', 'id')],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::Reviewer->value), Rule::unique('people', 'user_id')->ignore($model?->id)],
            'expertise' => ['nullable', 'string', 'max:2000'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', new FileContentType(['image/jpeg', 'image/png', 'image/webp']), 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists) {
            $data['slug'] = $this->uniqueSlug($data['name'], $model);
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
