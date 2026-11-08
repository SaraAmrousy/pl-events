#!/usr/bin/env bash
# Proves the guide's restore + checks catch leftovers (round 7): changes an option (Company › CVs go to),
# an office email (post meta) and the Contact form's email routing (Forminator), checks that the
# fingerprint names those groups and the seed check lists them, restores, and checks everything is back.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/../.." && pwd)"; WPD="${PL_SANDBOX:-/home/user/wp-sandbox}/site"
WP() { php -d memory_limit=512M "${PL_SANDBOX:-/home/user/wp-sandbox}/.bin/wp" --allow-root --path="$WPD" "$@" 2>/dev/null; }
DB=$(ls "$WPD"/wp-content/database/.ht.*/.ht.sqlite); BAK=$(mktemp)
fp() { WP eval-file "$REPO/tools/guide/fingerprint.php"; }
A=$(fp); python3 "$REPO/tools/guide/dbsnap.py" save "$DB" "$BAK"
WP eval 'update_option( "options_pl_careers_email", "hr@example.com" ); update_post_meta( 9, "pl_emails_0_email", "new-alex@example.com" ); do_action( "acf/save_post", 9 ); do_action( "acf/save_post", "options" );' >/dev/null
B=$(fp); echo "▸ after the changes, groups that differ:"; diff <(echo "$A") <(echo "$B") | grep '^>' | sed 's/^> /   /' || true
echo "▸ seed check:"; WP eval-file "$REPO/tools/guide/seed-check.php" | sed 's/^/   /'
python3 "$REPO/tools/guide/dbsnap.py" restore "$BAK" "$DB"; rm -f "$BAK"
C=$(fp); echo "▸ after the restore:"
[ "$A" = "$C" ] && echo "   ✅ every group identical to before" || { echo "   ❌ still different:"; diff <(echo "$A") <(echo "$C") | grep '^>'; exit 1; }
S=$(WP eval-file "$REPO/tools/guide/seed-check.php"); echo "   $S"; [ "$S" = "SEED OK" ]
