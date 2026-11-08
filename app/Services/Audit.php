<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pencatat log audit untuk setiap perubahan penting (status, publikasi, penugasan,
 * penilaian, peran, pengaturan, dan akses berkas sensitif).
 */
class Audit
{
    public static function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $description = null,
        ?User $user = null,
    ): AuditLog {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description !== null ? Str::limit($description, 490) : null,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }

    /** Ambil hanya kolom yang berubah: [old, new]. */
    public static function diff(Model $model, array $only = []): array
    {
        $changes = $model->getDirty();
        if ($only) {
            $changes = array_intersect_key($changes, array_flip($only));
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $original = $model->getOriginal($key);
            $old[$key] = $original instanceof \BackedEnum ? $original->value : $original;
        }

        $new = array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $changes);

        return [$old, $new];
    }
}
