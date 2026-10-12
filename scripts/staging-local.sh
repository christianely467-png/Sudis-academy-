#!/usr/bin/env bash
# Staging local de SUDIS Academy: WordPress + SQLite + sudis-academy-core.
#
# No toca ningún sitio publicado. Todo se crea dentro de ./.staging/
#
#   scripts/staging-local.sh          # instala (si hace falta) y arranca en http://localhost:8080
#   scripts/staging-local.sh --reset  # borra el staging y lo crea de cero
#
# Si pones el .zip de LearnDash en vendor-plugins/, se instala y activa.
# Los correos no salen: se guardan en .staging/mail/ (con el PDF adjunto).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGE="$ROOT/.staging"
WP_DIR="$STAGE/wordpress"
WP_VERSION="${WP_VERSION:-6.8.3}"
PORT="${PORT:-8080}"
URL="http://localhost:$PORT"
WPCLI="$STAGE/wp-cli.phar"
PLUGIN_SRC="$ROOT/wp-content/plugins/sudis-academy-core"

wp() { php "$WPCLI" --path="$WP_DIR" $( [[ $EUID -eq 0 ]] && echo --allow-root ) "$@"; }

if [[ "${1:-}" == "--reset" ]]; then
  rm -rf "$STAGE"
fi
mkdir -p "$STAGE/mail"

# 1. WordPress core, SQLite driver, WP-CLI --------------------------------
if [[ ! -f "$WP_DIR/wp-load.php" ]]; then
  git -c advice.detachedHead=false clone -q --depth 1 --branch "$WP_VERSION" https://github.com/WordPress/WordPress.git "$WP_DIR"
  rm -rf "$WP_DIR/.git"
fi
SQLITE="$WP_DIR/wp-content/plugins/sqlite-database-integration"
if [[ ! -d "$SQLITE" ]]; then
  git -c advice.detachedHead=false clone -q --depth 1 --branch v2.2.9 https://github.com/WordPress/sqlite-database-integration.git "$SQLITE"
  rm -rf "$SQLITE/.git"
fi
if [[ ! -f "$WP_DIR/wp-content/db.php" ]]; then
  sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$SQLITE#" -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
    "$SQLITE/db.copy" > "$WP_DIR/wp-content/db.php"
fi
if [[ ! -f "$WPCLI" ]]; then
  curl -sSL -o "$WPCLI" https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar
fi

# 2. Plugin dependencies (Dompdf, QR) -------------------------------------
if [[ ! -f "$PLUGIN_SRC/vendor/autoload.php" ]]; then
  composer install -q --no-dev --no-interaction --working-dir="$PLUGIN_SRC"
fi

# 3. Install WordPress ------------------------------------------------------
if [[ ! -f "$WP_DIR/wp-config.php" ]]; then
  wp config create --dbname=sudis --dbuser=sudis --dbpass=sudis --skip-check --quiet
  wp config set WP_ENVIRONMENT_TYPE local --quiet
  wp config set WP_DEBUG true --raw --quiet
  wp config set WP_DEBUG_LOG true --raw --quiet
  wp config set WP_DEBUG_DISPLAY false --raw --quiet
fi
if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$URL" --title="SUDIS Institute" --admin_user=admin --admin_password=admin \
    --admin_email=admin@sudis.local --skip-email --quiet
  wp option update home "$URL" --quiet
  wp option update siteurl "$URL" --quiet
  wp option update timezone_string "America/Puerto_Rico" --quiet
  wp option update date_format "j \\d\\e F \\d\\e Y" --quiet
  wp option update users_can_register 1 --quiet
  wp option update blogdescription "Educación continua para profesionales" --quiet
  wp rewrite structure '/%postname%/' --quiet
  wp language core install es_ES --activate --quiet 2>/dev/null || echo "Aviso: no se pudo descargar el idioma español (sin red); se usa inglés en el escritorio."
fi

# 4. Local-only helpers: capture outgoing mail -----------------------------
mkdir -p "$WP_DIR/wp-content/mu-plugins"
cat > "$WP_DIR/wp-content/mu-plugins/sudis-local-mail.php" <<PHP
<?php
// Staging local: guarda los correos en disco en vez de enviarlos.
add_filter( 'pre_wp_mail', function ( \$null, \$atts ) {
	\$dir = '$STAGE/mail/' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 4, false );
	wp_mkdir_p( \$dir );
	\$to = is_array( \$atts['to'] ) ? implode( ', ', \$atts['to'] ) : \$atts['to'];
	file_put_contents( \$dir . '/headers.txt', "To: {\$to}\nSubject: {\$atts['subject']}\n" . implode( "\n", (array) \$atts['headers'] ) . "\n" );
	file_put_contents( \$dir . '/body.html', \$atts['message'] );
	foreach ( (array) \$atts['attachments'] as \$file ) {
		if ( \$file && is_readable( \$file ) ) {
			copy( \$file, \$dir . '/' . basename( \$file ) );
		}
	}
	return true;
}, 10, 2 );
PHP

# 5. Plugins ------------------------------------------------------------------
ln -sfn "$PLUGIN_SRC" "$WP_DIR/wp-content/plugins/sudis-academy-core"
shopt -s nullglob
for zip in "$ROOT"/vendor-plugins/*.zip; do
  wp plugin install "$zip" --activate --force --quiet && echo "Instalado: $(basename "$zip")"
done
shopt -u nullglob
wp plugin activate sudis-academy-core --quiet

# 6. Academy pages (idempotent) -------------------------------------------------
wp eval-file "$ROOT/scripts/setup-academy.php"

# 7. Serve ---------------------------------------------------------------------
cat > "$STAGE/router.php" <<'PHP'
<?php
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $_SERVER['DOCUMENT_ROOT'] . $path;
if ( $path !== '/' && file_exists( $file ) && ! is_dir( $file ) ) {
	return false;
}
if ( is_dir( $file ) && file_exists( rtrim( $file, '/' ) . '/index.php' ) ) {
	require rtrim( $file, '/' ) . '/index.php';
	return true;
}
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
PHP

if [[ "${NO_SERVE:-}" == "1" ]]; then
  echo "Staging listo en $WP_DIR (sin servidor)."
  exit 0
fi
echo "SUDIS Academy (staging local): $URL/academia/   ·   Escritorio: $URL/wp-admin/  (admin / admin)"
exec php -S "localhost:$PORT" -t "$WP_DIR" "$STAGE/router.php"
