#!/usr/bin/env bash
# Finishes staging setup (idempotent). Already done on 5 Nov 2026 by hand-run API
# calls (see docs/Step-4.0-Setup.md): PHP 8.3 for /pl, DB saraamro_plstg,
# WordPress 7.1.4, wp-config, admin "sara", deploy user + App Password,
# Directory Privacy. This script adds: staging guard mu-plugin, Hello Elementor
# + free plugins, pretty permalinks, and runs the deploy test.
. "$(dirname "$0")/lib.sh"
load_env

say "mu-plugin: staging guard (noindex)"
cp_ls "$STAGING_DIR/wp-content" | grep -qx mu-plugins || api2 Fileman mkdir "path=$STAGING_DIR/wp-content" name=mu-plugins >/dev/null
cp_upload "$DEPLOY_DIR/mu/pl-staging.php" "$STAGING_DIR/wp-content/mu-plugins"

check_gate

say "theme: Hello Elementor (parent)"
curl -sS -L -o "$SCRATCH/hello-elementor.zip" https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip
cp_upload "$SCRATCH/hello-elementor.zip" "$STAGING_DIR/wp-content/themes"
cp_extract "$STAGING_DIR/wp-content/themes/hello-elementor.zip" "$STAGING_DIR/wp-content/themes"
cp_delete "$STAGING_DIR/wp-content/themes/hello-elementor.zip"
run_php "switch_theme( 'hello-elementor' ); echo 'theme=', get_stylesheet(), PHP_EOL;"

for slug in elementor secure-custom-fields header-footer-elementor forminator seo-by-rank-math; do
  say "plugin: $slug"
  wp_rest POST /wp/v2/plugins -d "slug=$slug" -d status=active | python3 -c 'import json,sys;d=json.load(sys.stdin);print(" ",d.get("plugin") or d.get("code"),d.get("status") or d.get("message"))'
done

say "settings: permalinks /%postname%/, timezone Africa/Cairo, no comments by default"
run_php "update_option('permalink_structure','/%postname%/'); update_option('timezone_string','Africa/Cairo'); update_option('default_comment_status','closed'); flush_rewrite_rules(); echo 'ok';"

say "deploy test (4.0 DoD)"
"$DEPLOY_DIR/deploy.sh" plugin "$DEPLOY_DIR/test-plugin/pl-hello"
wp_rest GET /pl-hello/v1/ping; echo
say "noindex header: $(site_curl -sI "$STAGING_URL/" | grep -i x-robots-tag)"
say "anonymous visitor gets: $(curl -s -o /dev/null -w '%{http_code}' "$STAGING_URL/") (must be 401)"
