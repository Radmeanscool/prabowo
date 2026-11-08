<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Audit;
use App\Services\SecureFileStore;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        return view('public.resources', [
            'documents' => Document::query()->published()->where('visibility', 'public')->get()->groupBy(fn ($d) => $d->categoryLabel()),
        ]);
    }

    public function download(Request $request, string $slug): BinaryFileResponse
    {
        $document = Document::query()->where('slug', $slug)->firstOrFail();
        abort_unless($document->isVisibleTo($request->user()) && $document->hasFile(), 404);

        if ($document->visibility !== 'public') {
            Audit::log('document.downloaded', $document, [], ['visibility' => $document->visibility]);
        }

        return SecureFileStore::response($document->file_path, $document->mime_type ?? 'application/pdf', $document->original_name ?? $document->slug.'.pdf', false, $document->visibility !== 'public');
    }
}
