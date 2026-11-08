#!/usr/bin/env bash
# Membangun paket rilis DEEPNEST untuk shared hosting yang tidak menyediakan Composer/Node.
# Jalankan di komputer lokal (butuh PHP 8.3+, Composer, dan zip):
#   bash scripts/build-release.sh            -> deepnest-release.zip
#   bash scripts/build-release.sh /tmp/a.zip -> berkas tujuan lain
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/deepnest-release.zip}"
WORK="$(mktemp -d)"
APP="$WORK/deepnest"
trap 'rm -rf "$WORK"' EXIT

echo "→ Menyalin kode sumber"
mkdir -p "$APP"
tar -C "$ROOT" \
    --exclude=./.git --exclude=./node_modules --exclude=./vendor --exclude=./tests \
    --exclude=./.env --exclude='./deepnest-release*.zip' --exclude=./storage/logs/'*.log' \
    --exclude=./storage/app/private/proposals --exclude=./storage/app/private/public-products \
    --exclude=./storage/framework/sessions/'*' --exclude=./storage/framework/views/'*.php' \
    --exclude=./storage/framework/cache/data \
    -cf - . | tar -C "$APP" -xf -

if command -v npm >/dev/null 2>&1 && [ -d "$ROOT/node_modules" ]; then
    echo "→ Membangun aset (npm run build)"
    (cd "$ROOT" && npm run build >/dev/null)
    rm -rf "$APP/public/build" && cp -R "$ROOT/public/build" "$APP/public/build"
fi
[ -f "$APP/public/build/manifest.json" ] || { echo "public/build belum ada. Jalankan npm ci && npm run build."; exit 1; }

echo "→ Memasang dependensi produksi (composer install --no-dev)"
(cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet)

echo "→ Membersihkan berkas pengembangan"
# Buang riwayat git, suite tes, dan dokumentasi di dalam paket vendor (tidak dipakai saat runtime).
find "$APP/vendor" -mindepth 1 -maxdepth 3 -type d \( -name .git -o -name .github \) -prune -exec rm -rf {} +
find "$APP/vendor" -mindepth 3 -maxdepth 3 -type d \( -name tests -o -name docs \) -prune -exec rm -rf {} +
rm -rf "$APP/node_modules" "$APP/phpunit.xml" "$APP/package-lock.json" "$APP/.editorconfig" "$APP/.npmrc"
mkdir -p "$APP/storage/app/private" "$APP/storage/framework/"{cache/data,sessions,views} "$APP/storage/logs" "$APP/bootstrap/cache"
rm -f "$APP/bootstrap/cache/"*.php

echo "→ Mengemas $OUT"
rm -f "$OUT"
(cd "$WORK" && zip -qr "$OUT" deepnest)
echo "Selesai: $OUT ($(du -h "$OUT" | cut -f1))"
