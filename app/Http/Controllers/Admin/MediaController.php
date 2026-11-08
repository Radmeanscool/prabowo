<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChallengeOwner;
use App\Models\Document;
use App\Models\Person;
use App\Models\Proposal;
use App\Services\SecureFileStore;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Pratinjau berkas konten untuk admin (termasuk yang belum publik). */
class MediaController extends Controller
{
    public function show(string $type, int $id): BinaryFileResponse
    {
        [$path, $name] = match ($type) {
            'owner' => [ChallengeOwner::withTrashed()->findOrFail($id)->logo_path, 'logo'],
            'person' => [Person::withTrashed()->findOrFail($id)->photo_path, 'foto'],
            'document' => (fn ($d) => [$d->file_path, $d->original_name])(Document::withTrashed()->findOrFail($id)),
            'product' => [Proposal::withTrashed()->findOrFail($id)->public_image_path, 'produk'],
            default => abort(404),
        };

        abort_unless($path, 404);
        $mime = mime_content_type(Storage::disk('local')->path($path)) ?: 'application/octet-stream';

        return SecureFileStore::response($path, $mime, $name, true);
    }
}
