<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use App\Support\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class AnnouncementController extends CrudController
{
    protected function model(): string
    {
        return Announcement::class;
    }

    protected function routeBase(): string
    {
        return 'admin.announcements';
    }

    protected function title(): string
    {
        return 'Pengumuman, berita, dan cerita';
    }

    protected function singular(): string
    {
        return 'Pengumuman';
    }

    protected function searchable(): array
    {
        return ['title', 'excerpt'];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Judul', 'value' => fn (Announcement $a) => new HtmlString('<p class="font-semibold text-navy-950">'.e($a->title).'</p><p class="text-xs text-navy-500">'.e($a->categoryLabel()).($a->is_pinned ? ' · disematkan' : '').'</p>'), 'class' => 'min-w-72'],
            ['label' => 'Audiens', 'value' => fn (Announcement $a) => Options::label(Options::audiences(), $a->audience)],
            ['label' => 'Status', 'value' => fn (Announcement $a) => new HtmlString($a->is_published ? '<span class="badge badge-success">Terbit</span>' : '<span class="badge badge-warning">Draf</span>')],
            ['label' => 'Tanggal terbit', 'value' => fn (Announcement $a) => $a->published_at ? fmt_date($a->published_at, true) : '—'],
        ];
    }

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Judul', 'required' => true, 'span' => 'full'],
            ['name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'options' => Options::announcementCategories(), 'placeholder' => false],
            ['name' => 'audience', 'label' => 'Audiens', 'type' => 'select', 'options' => Options::audiences(), 'placeholder' => false],
            ['name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'body', 'label' => 'Isi', 'type' => 'markdown', 'rows' => 12, 'required' => true],
            ['name' => 'published_at', 'label' => 'Tanggal terbit', 'type' => 'datetime', 'help' => 'Kosongkan untuk memakai waktu saat diterbitkan.'],
            ['name' => 'is_published', 'label' => 'Terbitkan', 'type' => 'checkbox'],
            ['name' => 'is_pinned', 'label' => 'Sematkan di atas', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', Rule::in(array_keys(Options::announcementCategories()))],
            'audience' => ['required', Rule::in(array_keys(Options::audiences()))],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }

    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        if (! $model->exists) {
            $data['slug'] = $this->uniqueSlug($data['title'], $model);
            $data['created_by'] = $request->user()->id;
        }
        if ($request->boolean('is_published') && empty($data['published_at'])) {
            $data['published_at'] = $model->published_at ?? now();
        }

        return $data;
    }
}
