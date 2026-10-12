#!/usr/bin/env bash
# Empaqueta sudis-academy-core en dist/sudis-academy-core.zip (con librerías incluidas),
# listo para subir en WordPress → Plugins → Añadir nuevo → Subir plugin.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/wp-content/plugins/sudis-academy-core"
BUILD="$(mktemp -d)"
trap 'rm -rf "$BUILD"' EXIT

mkdir -p "$BUILD/sudis-academy-core" "$ROOT/dist"
cp -R "$SRC"/{sudis-academy-core.php,composer.json,includes,templates,assets} "$BUILD/sudis-academy-core/"
[[ -f "$SRC/composer.lock" ]] && cp "$SRC/composer.lock" "$BUILD/sudis-academy-core/"
composer install -q --no-dev --no-interaction --optimize-autoloader --working-dir="$BUILD/sudis-academy-core"
rm -f "$ROOT/dist/sudis-academy-core.zip"
(cd "$BUILD" && zip -qr "$ROOT/dist/sudis-academy-core.zip" sudis-academy-core -x '*/.git*')
echo "dist/sudis-academy-core.zip ($(du -h "$ROOT/dist/sudis-academy-core.zip" | cut -f1))"
