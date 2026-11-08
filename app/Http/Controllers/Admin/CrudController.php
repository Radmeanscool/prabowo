<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use App\Services\SecureFileStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Dasar CRUD admin untuk sumber daya sederhana (konten, tahapan, mitra, dsb.).
 * Subkelas cukup mendefinisikan model, kolom tabel, isian formulir, dan aturan validasi.
 *
 * Tipe isian: text, email, url, number, date, datetime, time, color, textarea,
 * markdown, select, checkbox, multicheck, image, file, section.
 */
abstract class CrudController extends Controller
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Nama dasar rute, mis. "admin.challenge-owners". */
    abstract protected function routeBase(): string;

    abstract protected function title(): string;

    abstract protected function singular(): string;

    /** @return array<int, array{label: string, value: callable}> */
    abstract protected function columns(): array;

    /** @return array<int, array<string, mixed>> */
    abstract protected function fields(?Model $model): array;

    abstract protected function rules(?Model $model): array;

    protected function description(): ?string
    {
        return null;
    }

    /** Kolom yang dicari pada kotak pencarian. */
    protected function searchable(): array
    {
        return ['name'];
    }

    protected function query(Request $request): Builder
    {
        $query = $this->model()::query();

        if ($term = trim((string) $request->query('q'))) {
            $query->where(function (Builder $q) use ($term) {
                foreach ($this->searchable() as $column) {
                    $q->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        return $this->orderQuery($query);
    }

    protected function orderQuery(Builder $query): Builder
    {
        return $query->latest('id');
    }

    /** Folder privat untuk unggahan gambar/berkas sumber daya ini. */
    protected function uploadDirectory(): string
    {
        return Str::plural(Str::snake(class_basename($this->model())));
    }

    protected function canDelete(Model $model): bool
    {
        return true;
    }

    /** Tujuan setelah simpan/hapus (dapat diganti subkelas). */
    protected function indexUrl(): string
    {
        return route($this->routeBase().'.index');
    }

    protected function auditName(): string
    {
        return Str::snake(class_basename($this->model()));
    }

    public function index(Request $request): View
    {
        return view('admin.crud.index', [
            'title' => $this->title(),
            'singular' => $this->singular(),
            'description' => $this->description(),
            'routeBase' => $this->routeBase(),
            'columns' => $this->columns(),
            'items' => $this->query($request)->paginate(25)->withQueryString(),
            'canCreate' => Route::has($this->routeBase().'.create'),
            'canDeleteRoute' => Route::has($this->routeBase().'.destroy'),
            'controller' => $this,
            'extraActions' => $this->indexActions(),
        ]);
    }

    /** Tombol tambahan di kepala halaman indeks: [[label, url], ...] */
    protected function indexActions(): array
    {
        return [];
    }

    public function create(): View
    {
        $model = new ($this->model());
        $this->prepareNew($model);

        return $this->form($model);
    }

    protected function prepareNew(Model $model): void
    {
        //
    }

    public function store(Request $request): RedirectResponse
    {
        $model = new ($this->model());
        $data = $request->validate($this->rules(null));
        $this->persist($model, $data, $request);
        Audit::log($this->auditName().'.created', $model, [], $this->auditable($model));

        return redirect()->to($this->indexUrl())->with('success', $this->singular().' berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return $this->form($this->find($id));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $model = $this->find($id);
        $data = $request->validate($this->rules($model));
        $before = $this->auditable($model);
        $this->persist($model, $data, $request);
        $after = $this->auditable($model->fresh() ?? $model);

        $old = array_diff_assoc(array_map('strval', array_filter($before, 'is_scalar')), array_map('strval', array_filter($after, 'is_scalar')));
        if ($old) {
            Audit::log($this->auditName().'.updated', $model, array_intersect_key($before, $old), array_intersect_key($after, $old));
        }

        return redirect()->to($this->indexUrl())->with('success', $this->singular().' berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $model = $this->find($id);
        if (! $this->canDelete($model)) {
            return back()->with('error', $this->singular().' tidak dapat dihapus karena masih dipakai.');
        }

        Audit::log($this->auditName().'.deleted', $model, $this->auditable($model), []);
        $model->delete();

        return redirect()->to($this->indexUrl())->with('success', $this->singular().' dihapus.');
    }

    protected function form(Model $model): View
    {
        return view('admin.crud.form', [
            'title' => $model->exists ? 'Ubah '.Str::lower($this->singular()) : 'Tambah '.Str::lower($this->singular()),
            'singular' => $this->singular(),
            'routeBase' => $this->routeBase(),
            'model' => $model,
            'fields' => $this->fields($model),
            'action' => $model->exists ? route($this->routeBase().'.update', $model) : route($this->routeBase().'.store'),
            'method' => $model->exists ? 'PUT' : 'POST',
            'hasFiles' => collect($this->fields($model))->contains(fn ($f) => in_array($f['type'] ?? 'text', ['image', 'file'], true)),
            'backUrl' => $this->indexUrl(),
        ]);
    }

    protected function find(string $id): Model
    {
        $instance = new ($this->model());
        $model = $instance->resolveRouteBinding($id);
        abort_if($model === null, 404);

        return $model;
    }

    /** Simpan data tervalidasi. Kotak centang, unggahan, dan pivot ditangani otomatis. */
    protected function persist(Model $model, array $data, Request $request): void
    {
        $fields = collect($this->fields($model))->reject(fn ($f) => ($f['type'] ?? '') === 'section');

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            if ($type === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }

            if (in_array($type, ['image', 'file'], true)) {
                unset($data[$name]);
                if ($request->hasFile($name)) {
                    $stored = SecureFileStore::store($request->file($name), $this->uploadDirectory());
                    SecureFileStore::delete($model->{$field['column']} ?? null);
                    $model->{$field['column']} = $stored['path'];
                    foreach (($field['meta'] ?? []) as $metaKey => $column) {
                        $model->{$column} = $stored[$metaKey];
                    }
                } elseif ($request->boolean($name.'_remove') && ! empty($model->{$field['column']})) {
                    SecureFileStore::delete($model->{$field['column']});
                    $model->{$field['column']} = null;
                }
            }

            if ($type === 'multicheck') {
                unset($data[$name]);
            }
        }

        $model->forceFill($this->beforeFill($model, $data, $request));
        $model->save();

        foreach ($fields->where('type', 'multicheck') as $field) {
            $model->{$field['relation']}()->sync(array_map('intval', $request->input($field['name'], [])));
        }

        $this->afterSave($model, $request);
    }

    /** Kesempatan subkelas mengubah data sebelum disimpan (mis. slug otomatis). */
    protected function beforeFill(Model $model, array $data, Request $request): array
    {
        return $data;
    }

    protected function afterSave(Model $model, Request $request): void
    {
        //
    }

    protected function auditable(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['created_at', 'updated_at', 'deleted_at', 'body', 'answer', 'description'])
            ->all();
    }

    protected function uniqueSlug(string $value, ?Model $ignore = null, string $column = 'slug'): string
    {
        $base = Str::slug(Str::limit($value, 80, '')) ?: Str::lower(class_basename($this->model()));
        $slug = $base;
        $i = 2;
        $query = fn ($candidate) => $this->model()::query()
            ->when(in_array(SoftDeletes::class, class_uses_recursive($this->model()), true), fn ($q) => $q->withTrashed())
            ->where($column, $candidate)
            ->when($ignore?->exists, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->exists();

        while ($query($slug)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
