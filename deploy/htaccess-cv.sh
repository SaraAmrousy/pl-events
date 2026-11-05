#!/usr/bin/env bash
# Blocks direct web access to files uploaded through the forms (CVs) in pl/wp-content/uploads/forminator.
# The CV reaches the office as an email attachment; it must not be downloadable by URL (privacy, Step 1.1 B2/B3).
# This folder only. Idempotent.
. "$(dirname "$0")/lib.sh"
load_env
cur=$(cp_read "$STAGING_DIR" .htaccess)
if printf '%s' "$cur" | grep -q '# BEGIN PL private uploads'; then say "CV block already there"; exit 0; fi
block='# BEGIN PL private uploads
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^wp-content/uploads/forminator/.+\.(pdf|docx?|rtf|odt)$ - [F,NC,L]
</IfModule>
# END PL private uploads'
cp_write "$STAGING_DIR" .htaccess "$(printf '%s\n\n%s\n' "$block" "$cur")"
say "CV block added"
