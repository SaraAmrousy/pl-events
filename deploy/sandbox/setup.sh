#!/usr/bin/env bash
# Sandbox test WordPress (S6): PHP + SQLite + WP-CLI, rebuilt each session.
# Links the repo's plugin + theme in. Serves on http://localhost:8080
set -euo pipefail
REPO="$(cd "$(dirname "$0")/../.." && pwd)"
WPD="${PL_SANDBOX:-/home/user/wp-sandbox}"
BIN="$WPD/.bin"; mkdir -p "$BIN"
[ -f "$BIN/wp" ] || { curl -sSL -o "$BIN/wp" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; chmod +x "$BIN/wp"; }
WP() { php -d memory_limit=512M "$BIN/wp" --allow-root --path="$WPD/site" "$@"; }
if [ ! -f "$WPD/site/wp-load.php" ]; then
  mkdir -p "$WPD/site"; WP core download --skip-content --quiet
  mkdir -p "$WPD/site/wp-content/plugins" "$WPD/site/wp-content/themes" "$WPD/site/wp-content/mu-plugins"
fi
P="$WPD/site/wp-content/plugins"
get() { [ -d "$P/$1" ] || { curl -sSL -o "$WPD/$1.zip" "https://downloads.wordpress.org/plugin/$1.latest-stable.zip"; unzip -q -o "$WPD/$1.zip" -d "$P"; rm "$WPD/$1.zip"; }; }
get sqlite-database-integration; get secure-custom-fields; get elementor; get header-footer-elementor; get forminator
get seo-by-rank-math; get wp-mail-smtp; get litespeed-cache   # same plugin list as staging (guide screenshots)
cp "$P/sqlite-database-integration/db.copy" "$WPD/site/wp-content/db.php"
sed -i "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$P/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" "$WPD/site/wp-content/db.php"
T="$WPD/site/wp-content/themes"
[ -d "$T/hello-elementor" ] || { curl -sSL -o "$WPD/he.zip" https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip; unzip -q -o "$WPD/he.zip" -d "$T"; rm "$WPD/he.zip"; }
ln -sfn "$REPO/wp-content/plugins/pl-events-core" "$P/pl-events-core"
ln -sfn "$REPO/wp-content/themes/hello-pl-child" "$T/hello-pl-child"
ln -sfn "$REPO/reference/prototype/src" "$WPD/site/_proto"
ln -sfn "$REPO/reference/prototype/site/assets" "$WPD/site/_assets"
ln -sfn "$REPO/deploy/sandbox/pl-sandbox.php" "$WPD/site/wp-content/mu-plugins/pl-sandbox.php"
if [ ! -f "$WPD/site/wp-config.php" ]; then
  WP config create --dbname=wp --dbuser=x --dbpass=x --skip-check --quiet --extra-php <<'PHP'
define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_ENVIRONMENT_TYPE', 'local' ); define( 'DISALLOW_FILE_EDIT', true ); // same as staging/live define( 'DB_DIR', __DIR__ . '/wp-content/database/' );
PHP
  WP core install --url=http://localhost:8080 --title="PL sandbox" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email --quiet
  WP option update timezone_string Africa/Cairo --quiet
  WP rewrite structure '/%postname%/' --quiet
fi
WP plugin activate secure-custom-fields elementor header-footer-elementor forminator seo-by-rank-math wp-mail-smtp litespeed-cache --quiet
WP config set DISALLOW_FILE_EDIT true --raw --quiet
WP config set DISABLE_WP_CRON true --raw --quiet   # as on staging; also: no background write after a guide restore
WP theme activate hello-elementor --quiet
echo "sandbox ready: $WPD/site  (wp: php $BIN/wp --allow-root --path=$WPD/site)"
