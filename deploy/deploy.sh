#!/usr/bin/env bash
# One command: deploy a plugin or theme folder to staging and activate it.
#   deploy/deploy.sh plugin wp-content/plugins/pl-events-core
#   deploy/deploy.sh theme  wp-content/themes/hello-pl-child
#   deploy/deploy.sh plugin deploy/test-plugin/pl-hello      (4.0 DoD test)
#   deploy/deploy.sh remove-plugin pl-hello
# lint → zip → cPanel upload → replace folder → extract → activate inside WordPress.
# Content is never pushed as a DB dump; the plugin importer seeds it.
. "$(dirname "$0")/lib.sh"
load_env; check_site
kind="${1:?plugin|theme|remove-plugin}"; src="${2:?folder or slug}"

case "$kind" in
  plugin|theme)
    [ -d "$src" ] || die "no folder $src"
    slug=$(basename "$src")
    for f in $(find "$src" -name '*.php'); do php -l "$f" >/dev/null || die "PHP lint failed: $f"; done
    dest="$STAGING_DIR/wp-content/${kind}s"
    zip="$SCRATCH/$slug.zip"; zip_dir "$(cd "$src" && pwd)" "$zip"
    say "upload $slug ($(du -h "$zip" | cut -f1)) → $dest"
    cp_upload "$zip" "$dest"
    cp_delete "$dest/$slug" || true          # replace code only; data lives in the DB
    cp_extract "$dest/$slug.zip" "$dest"
    cp_delete "$dest/$slug.zip"
    if [ "$kind" = plugin ]; then
      main=$(grep -l "Plugin Name:" "$src"/*.php | head -1); main=$(basename "$main" .php)
      say "activate $slug/$main"
      run_php "\$r = activate_plugin( '$slug/$main.php' ); echo is_wp_error( \$r ) ? 'ERROR ' . \$r->get_error_message() : 'active: $slug ' . ( get_plugin_data( WP_PLUGIN_DIR . '/$slug/$main.php' )['Version'] ?? '' ), PHP_EOL;"
    else
      say "activate theme $slug"
      run_php "switch_theme( '$slug' ); echo get_stylesheet();"
    fi
    run_php 'flush_rewrite_rules(); wp_cache_flush(); echo "flushed";'
    ;;
  remove-plugin)
    run_php "deactivate_plugins( '$src/$src.php' ); \$r = delete_plugins( array( '$src/$src.php' ) ); echo is_wp_error( \$r ) ? 'ERROR ' . \$r->get_error_message() : 'deleted: $src', PHP_EOL;"
    ;;
  *) die "unknown kind $kind" ;;
esac
say "done → $STAGING_URL"
