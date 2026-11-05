#!/usr/bin/env bash
# Adds long cache headers for static files to public_html/pl/.htaccess (this folder only).
# URLs of theme/plugin assets carry ?ver=…, media files never change in place. Idempotent.
. "$(dirname "$0")/lib.sh"
load_env
cur=$(cp_read "$STAGING_DIR" .htaccess)
if printf '%s' "$cur" | grep -q '# BEGIN PL static cache'; then say "cache block already there"; exit 0; fi
block='# BEGIN PL static cache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType text/javascript "access plus 1 year"
</IfModule>
<IfModule mod_headers.c>
  <FilesMatch "\.(jpe?g|png|webp|svg|woff2|css|js)$">
    Header set Cache-Control "public, max-age=31536000"
  </FilesMatch>
</IfModule>
# END PL static cache'
cp_write "$STAGING_DIR" .htaccess "$(printf '%s\n\n%s\n' "$cur" "$block")"
say "cache block added"
