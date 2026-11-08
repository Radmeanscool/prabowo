<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChallengeOwner;
use App\Models\Person;
use App\Models\Proposal;
use App\Services\Audit;
use App\Services\SecureFileStore;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Media publik disajikan dari penyimpanan privat hanya bila objeknya ditandai publik.
 * Tidak memerlukan symlink storage (sering bermasalah di shared hosting).
 */
class MediaController extends Controller
{
    public function productImage(string $slug): BinaryFileResponse
    {
        $product = Proposal::query()->publicProducts()->where('public_slug', $slug)->firstOrFail();
        abort_unless($product->public_image_path, 404);

        return $this->image($product->public_image_path, 'produk');
    }

    public function ownerLogo(string $slug): BinaryFileResponse
    {
        $owner = ChallengeOwner::query()->where('slug', $slug)->where('is_public', true)->firstOrFail();
        abort_unless($owner->logo_path, 404);

        return $this->image($owner->logo_path, 'logo');
    }

    public function personPhoto(string $slug): BinaryFileResponse
    {
        $person = Person::query()->where('slug', $slug)->where('is_public', true)->firstOrFail();
        abort_unless($person->photo_path, 404);

        return $this->image($person->photo_path, 'foto');
    }

    /** PDF proposal hanya bila produk publik DAN admin mengaktifkan public_pdf_enabled. */
    public function productPdf(string $slug): BinaryFileResponse
    {
        $product = Proposal::query()->publicProducts()->where('public_slug', $slug)->where('public_pdf_enabled', true)->firstOrFail();
        $file = $product->currentFile('proposal_pdf');
        abort_unless($file, 404);

        Audit::log('file.public_pdf_downloaded', $product, [], ['file' => $file->uuid], 'Unduhan PDF proposal publik');

        return SecureFileStore::response($file->path, 'application/pdf', Str::slug($product->displayTitle()).'.pdf', true, false);
    }

    private function image(string $path, string $name): BinaryFileResponse
    {
        abort_unless(SecureFileStore::exists($path), 404);
        $mime = mime_content_type(Storage::disk('local')->path($path)) ?: 'application/octet-stream';
        abort_unless(str_starts_with($mime, 'image/'), 404);

        return SecureFileStore::response($path, $mime, $name.'.'.pathinfo($path, PATHINFO_EXTENSION), true, false);
    }
}
