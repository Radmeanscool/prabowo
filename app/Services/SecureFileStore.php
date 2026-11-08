<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Penyimpanan berkas privat. Semua unggahan disimpan di storage/app/private
 * (di luar public_html) dengan nama acak, lalu disajikan lewat controller
 * yang memeriksa otorisasi.
 */
class SecureFileStore
{
    public const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public const PDF_MIMES = [
        'application/pdf' => 'pdf',
    ];

    private const DISK = 'local';

    public static function storeProposalFile(Proposal $proposal, UploadedFile $file, string $type, User $user, bool $internal = false): ProposalFile
    {
        $stored = self::store($file, 'proposals/'.$proposal->id.'/'.$type);

        return ProposalFile::create([
            'proposal_id' => $proposal->id,
            'type' => $type,
            'disk' => self::DISK,
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'checksum' => $stored['checksum'],
            'is_internal' => $internal,
            'uploaded_by' => $user->id,
        ]);
    }

    /**
     * Simpan berkas yang sudah tervalidasi ke direktori privat.
     *
     * @return array{path: string, original_name: string, mime_type: string, size: int, checksum: string}
     */
    public static function store(UploadedFile $file, string $directory): array
    {
        // Jenis berkas ditentukan dari isi berkas (finfo), bukan dari nama/ekstensi kiriman pengguna.
        $mime = (string) $file->getMimeType();
        $extension = (self::IMAGE_MIMES + self::PDF_MIMES)[$mime] ?? null;
        if ($extension === null) {
            throw new InvalidArgumentException('Jenis berkas tidak diizinkan.');
        }

        $directory = trim($directory, '/');
        $name = Str::random(40).'.'.$extension;
        $path = $directory.'/'.$name;
        $disk = Storage::disk(self::DISK);

        $contents = null;
        if (isset(self::IMAGE_MIMES[$mime])) {
            // Tulis ulang gambar: membuang metadata EXIF (mis. lokasi GPS) dan konten tersisip.
            $contents = self::reencodeImage($file->getRealPath(), $mime);
        }

        if ($contents !== null) {
            $disk->put($path, $contents);
        } else {
            $disk->putFileAs($directory, $file, $name);
        }

        return [
            'path' => $path,
            'original_name' => self::cleanName($file->getClientOriginalName(), $extension),
            'mime_type' => $mime,
            'size' => (int) $disk->size($path),
            'checksum' => hash_file('sha256', $disk->path($path)) ?: '',
        ];
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Respons unduhan dengan header keamanan. Path penyimpanan tidak pernah dikirim ke klien.
     */
    public static function response(string $path, string $mime, string $downloadName, bool $inline = false, bool $private = true): BinaryFileResponse
    {
        abort_unless(self::exists($path), 404);

        $fallback = Str::ascii($downloadName) ?: 'berkas';
        $fallback = preg_replace('/[^A-Za-z0-9._-]+/', '-', $fallback) ?: 'berkas';

        $response = new BinaryFileResponse(Storage::disk(self::DISK)->path($path), 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $private ? 'private, no-store, max-age=0' : 'public, max-age=3600',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
            'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $downloadName, $fallback),
        ]);

        return $response;
    }

    public static function cleanName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = Str::limit(trim(preg_replace('/[^\pL\pN ._-]+/u', '', $base) ?? ''), 120, '');

        return ($base !== '' ? $base : 'berkas').'.'.$extension;
    }

    private static function reencodeImage(string $path, string $mime): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $image = @imagecreatefromstring($data);
        if ($image === false) {
            return null;
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
            $rotated = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => null,
            };
            if ($rotated) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 2400;
        if ($width > $max || $height > $max) {
            $scale = min($max / $width, $max / $height);
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        } else {
            imagesavealpha($image, true);
        }

        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png' => imagepng($image, null, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, null, 82) : false,
            default => false,
        };
        $output = ob_get_clean();
        imagedestroy($image);

        return $ok && $output ? $output : null;
    }
}
