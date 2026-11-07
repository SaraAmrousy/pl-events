#!/usr/bin/env bash
# PL guide — one command to remake every screenshot, pairing shot, video and the exports:
#   bash tools/guide/run.sh                 everything
#   bash tools/guide/run.sh --only=event-add,office-phone   some tasks
#   bash tools/guide/run.sh --no-video      screenshots only (fast)
# Sandbox only (http://localhost:8080; deploy/sandbox/setup.sh builds it). It changes data while it
# records, so it: saves the database + uploads list → adds a temporary QA admin + test data →
# records (restoring the data after every task) → puts the database and uploads back exactly as they
# were → proves it (same content hash, same user list) → builds the exports (tools/guide/build.js).
set -euo pipefail
REPO="$(cd "$(dirname "$0")/../.." && pwd)"
WPD="${PL_SANDBOX:-/home/user/wp-sandbox}/site"
WP() { php -d memory_limit=512M "${PL_SANDBOX:-/home/user/wp-sandbox}/.bin/wp" --allow-root --path="$WPD" "$@" 2>/dev/null; }
OUT="$REPO/tools/guide/out"; mkdir -p "$OUT/tmp"
DB=$(ls "$WPD"/wp-content/database/.ht.*/.ht.sqlite)
UP="$WPD/wp-content/uploads"; mkdir -p "$UP"
curl -sf -o /dev/null http://localhost:8080/ || { echo "Sandbox not running on :8080 (php -S localhost:8080 deploy/sandbox/router.php inside $WPD)"; exit 1; }

say() { printf '\033[1;34m▸ %s\033[0m\n' "$*"; }
# Content fingerprint: every row of posts, post meta, terms, users, user meta (minus login sessions) and the
# PL/plugin options (minus timers and caches).
fingerprint() { WP eval 'global $wpdb; $h = hash_init( "sha256" );
foreach ( array( "posts" => "ID", "postmeta" => "meta_id", "terms" => "term_id", "term_taxonomy" => "term_taxonomy_id", "term_relationships" => "object_id,term_taxonomy_id", "termmeta" => "meta_id", "users" => "ID" ) as $t => $o ) { foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}$t ORDER BY $o", ARRAY_N ) as $r ) { hash_update( $h, $t . json_encode( $r ) ); } }
foreach ( $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key NOT IN (\"session_tokens\") ORDER BY umeta_id", ARRAY_N ) as $r ) { hash_update( $h, json_encode( $r ) ); }
foreach ( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE \"%transient%\" AND option_name NOT IN (\"cron\") ORDER BY option_name", ARRAY_N ) as $r ) { hash_update( $h, json_encode( $r ) ); }
echo substr( hash_final( $h ), 0, 16 );'; }

say "save the data as it is now"
cp "$DB" "$OUT/tmp/pristine.sqlite"
find "$UP" -type f -not -path "*/uploads/pl-help/*" | sort > "$OUT/tmp/pristine-uploads.txt"
BEFORE=$(fingerprint)
cleanup() {
  say "put the data back exactly as it was"
  cp "$OUT/tmp/pristine.sqlite" "$DB"
  find "$UP" -type f -not -path "*/uploads/pl-help/*" | sort | comm -13 "$OUT/tmp/pristine-uploads.txt" - | while read -r f; do rm -f "$f"; done   # PL Help media (build.js) stays
  AFTER=$(fingerprint)
  if [ "$BEFORE" = "$AFTER" ]; then echo "✅ data restored (fingerprint $AFTER, same as before)"; else echo "❌ data differs after the run ($BEFORE → $AFTER)"; exit 1; fi
}
trap cleanup EXIT

say "temporary QA admin + test data (sandbox only)"
export PL_QA_USER="pl-guide-qa" PL_QA_PASS="$(python3 -c 'import secrets;print(secrets.token_urlsafe(24))')"
WP user create "$PL_QA_USER" qa-guide@example.invalid --role=administrator --user_pass="$PL_QA_PASS" --display_name="PL Team" --first_name=PL --last_name=Team >/dev/null
WP eval-file "$REPO/tools/guide/demo-data.php"
node "$REPO/tools/guide/demo-forms.js"

export PL_GUIDE_DB="$DB" PL_GUIDE_UPLOADS="$UP"
say "record (data restored after every task)"
rc=0; node "$REPO/tools/guide/record.js" "$@" || rc=$?
say "build exports"
node "$REPO/tools/guide/build.js" || rc=1
exit $rc
