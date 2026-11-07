#!/usr/bin/env bash
# PL Help media → staging: docs/guide/{shots,pairs,video} → wp-content/uploads/pl-help/
#   deploy/deploy-help.sh
# The folder is outside the plugin (plugin deploys stay small) and outside Media (not in the
# library, no attachment pages, not in any sitemap). Direct web access is denied by .htaccess;
# PL Help serves the files to signed-in staff through admin-post.php?action=pl_help_media.
# Only this folder is replaced; nothing else in uploads is touched.
. "$(dirname "$0")/lib.sh"
load_env; check_site
SRC="$REPO_DIR/docs/guide"
[ -d "$SRC/shots" ] || die "no $SRC/shots — run: bash tools/guide/run.sh"
UPL="$STAGING_DIR/wp-content/uploads"
stage="$SCRATCH/pl-help"; rm -rf "$stage" "$SCRATCH/pl-help.zip"; mkdir -p "$stage"
cp -r "$SRC/shots" "$SRC/pairs" "$SRC/video" "$stage/"
printf '# PL Help media: staff only, served by admin-post.php?action=pl_help_media\nRequire all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n' > "$stage/.htaccess"
printf '<?php // Silence.\n' > "$stage/index.php"
( cd "$SCRATCH" && zip -qr pl-help.zip pl-help )
say "upload pl-help.zip ($(du -h "$SCRATCH/pl-help.zip" | cut -f1)) → $UPL"
cp_upload "$SCRATCH/pl-help.zip" "$UPL"
old="pl-help.old-$(date +%s)"
cp_ls "$UPL" | grep -qx pl-help && cp_rename "$UPL/pl-help" "$old"
cp_extract "$UPL/pl-help.zip" "$UPL"
for try in 1 2 3; do cp_ls "$UPL/pl-help" 2>/dev/null | grep -qx shots && break; sleep $((try * 3)); cp_extract "$UPL/pl-help.zip" "$UPL"; done
cp_ls "$UPL/pl-help" | grep -qx .htaccess || { cp_ls "$UPL" | grep -qx "$old" && { cp_delete "$UPL/pl-help"; cp_rename "$UPL/$old" pl-help; }; die "extract failed — old media restored"; }
cp_ls "$UPL" | grep -qx "$old" && cp_delete "$UPL/$old"
cp_delete "$UPL/pl-help.zip"
n=$(cp_ls "$UPL/pl-help/video" | grep -c '\.mp4$' || true)
say "PL Help media on staging: $(cp_ls "$UPL/pl-help/shots" | grep -c png) shots, $(cp_ls "$UPL/pl-help/pairs" | grep -c png) pairs, $n videos"
code=$(curl -s -o /dev/null -w '%{http_code}' "$STAGING_URL/wp-content/uploads/pl-help/shots/$(ls "$SRC/shots" | head -1)")
[ "$code" = 403 ] || [ "$code" = 404 ] || die "direct access answered $code (expected 403): the folder is NOT protected"
say "direct access → $code (blocked, as it should be)"
