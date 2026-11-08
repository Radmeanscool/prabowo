<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Memastikan jenis berkas dideteksi dari ISI berkas (finfo), bukan dari nama atau
 * header kiriman browser. Mencegah berkas .php/.exe yang diberi nama .pdf/.jpg.
 */
class FileContentType implements ValidationRule
{
    /** @param array<int, string> $allowed */
    public function __construct(private array $allowed) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $detected = self::detect($value);

        if ($detected === null || ! in_array($detected, $this->allowed, true)) {
            $fail('Isi berkas tidak sesuai dengan jenis yang diizinkan.');
        }
    }

    public static function detect(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? $mime : null;
    }
}
