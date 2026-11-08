<?php

use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('md')) {
    /**
     * Render Markdown dengan aman: HTML mentah dibuang dan tautan berbahaya diblokir.
     */
    function md(?string $text): HtmlString
    {
        if ($text === null || trim($text) === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }
}

if (! function_exists('paragraphs')) {
    /**
     * Tampilkan teks biasa (isian peserta) sebagai paragraf HTML yang sudah di-escape.
     */
    function paragraphs(?string $text): HtmlString
    {
        if ($text === null || trim($text) === '') {
            return new HtmlString('<p class="text-muted">—</p>');
        }

        $blocks = preg_split("/\R{2,}/", trim($text)) ?: [];
        $html = collect($blocks)
            ->map(fn ($block) => '<p>'.nl2br(e(trim($block)), false).'</p>')
            ->implode('');

        return new HtmlString($html);
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(mixed $date, bool $withTime = false): string
    {
        if (! $date) {
            return '—';
        }

        $carbon = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $carbon = $carbon->timezone(config('app.timezone'))->locale('id');

        return $withTime
            ? $carbon->translatedFormat('j F Y, H.i').' WITA'
            : $carbon->translatedFormat('j F Y');
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(?int $bytes): string
    {
        if (! $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power > 0 ? 1 : 0, ',', '.').' '.$units[$power];
    }
}

if (! function_exists('word_count')) {
    function word_count(?string $text): int
    {
        if ($text === null) {
            return 0;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }
}
