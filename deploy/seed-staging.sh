#!/usr/bin/env bash
# Seed staging content from the plugin's bundled data/pl-data.json (never a DB dump).
# Safe: existing items are skipped. Pass --overwrite to update them from the file.
. "$(dirname "$0")/lib.sh"
load_env; check_site
ow=0; [ "${1:-}" = "--overwrite" ] && ow=1
run_php "\$r = ( new PL_Importer() )->run( PL_Importer::seed_file(), (bool) $ow ); echo wp_json_encode( \$r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), PHP_EOL; \$s = PL_Data::stats(); echo \"stats: {\$s['events']} events / {\$s['cities']} cities / {\$s['countries']} countries\", PHP_EOL;"
