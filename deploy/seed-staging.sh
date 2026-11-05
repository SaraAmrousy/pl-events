#!/usr/bin/env bash
# Seed staging content from the plugin's bundled data/pl-data.json (never a DB dump).
# Safe: existing items are skipped. Pass --overwrite to update them from the file.
. "$(dirname "$0")/lib.sh"
load_env; check_gate
ow=0; [ "${1:-}" = "--overwrite" ] && ow=1
wp_rest POST /pl/v1/import -d "overwrite=$ow" | python3 -m json.tool
