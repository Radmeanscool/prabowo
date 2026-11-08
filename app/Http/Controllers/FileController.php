<?php

namespace App\Http\Controllers;

use App\Models\ProposalFile;
use App\Services\Audit;
use App\Services\SecureFileStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Satu pintu unduhan berkas proposal privat untuk semua peran (dicek oleh ProposalFilePolicy).
 */
class FileController extends Controller
{
    public function show(Request $request, string $file): BinaryFileResponse
    {
        $record = ProposalFile::query()->where('uuid', $file)->firstOrFail();
        $this->authorize('view', $record);

        if ($record->isSensitive()) {
            Audit::log('file.accessed', $record->proposal, [], [
                'file' => $record->uuid,
                'type' => $record->type,
                'role' => $request->user()->role->value,
            ], 'Akses berkas: '.$record->typeLabel());
        }

        $inline = $record->isImage() || $record->isPdf();

        return SecureFileStore::response($record->path, $record->mime_type, $record->original_name, $inline);
    }
}
