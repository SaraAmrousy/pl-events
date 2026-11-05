#!/usr/bin/env bash
# PL staging deploy library (sourced by the other scripts).
# Talks to Sara's cPanel through its HTTPS API. The cPanel token is a network
# secret: the agent proxy adds the Authorization header, so no auth here.
# Everything else (DB password, WP passwords) lives in deploy/.env (git-ignored)
# with a private copy on the server in ~/pl-deploy/.env (outside public_html).
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "$DEPLOY_DIR/.." && pwd)"
ENV_FILE="$DEPLOY_DIR/.env"
CP_HOST="https://cpanel.saraamrousy.net"
CP_USER="saraamro"
REMOTE_PRIVATE="/home/saraamro/pl-deploy"
STAGING_DIR="/home/saraamro/public_html/pl"
STAGING_URL="https://saraamrousy.net/pl"
SCRATCH="${PL_SCRATCH:-${TMPDIR:-/tmp}/pl-deploy}"
mkdir -p "$SCRATCH"

say() { printf '\033[1;34m▸ %s\033[0m\n' "$*" >&2; }
die() { printf '\033[1;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

# --- cPanel API ---------------------------------------------------------------
# uapi Module/function [curl args...]   → prints JSON, fails on status 0
uapi() {
  local fn="$1"; shift
  local out
  out=$(curl -sS --fail-with-body -X POST "$CP_HOST/execute/$fn" "$@") || die "cPanel $fn failed (HTTP). 401 = token secret missing/wrong."
  if [ "$(printf '%s' "$out" | python3 -c 'import json,sys;print(json.load(sys.stdin).get("status",0))' 2>/dev/null)" != "1" ]; then
    die "cPanel $fn error: $(printf '%s' "$out" | head -c 400)"
  fi
  printf '%s' "$out"
}
# api2 Module function key=value...      (API 2, for fileop/mkdir)
api2() {
  local mod="$1" fn="$2"; shift 2
  local args=(-d cpanel_jsonapi_user=$CP_USER -d cpanel_jsonapi_apiversion=2 -d cpanel_jsonapi_module="$mod" -d cpanel_jsonapi_func="$fn")
  local kv; for kv in "$@"; do args+=(--data-urlencode "$kv"); done
  curl -sS --get "$CP_HOST/json-api/cpanel" "${args[@]}"
}

cp_upload()  { # local_file remote_dir
  uapi Fileman/upload_files -F "dir=$2" -F "overwrite=1" -F "file-1=@$1" >/dev/null
}
cp_extract() { # remote_zip remote_dest
  api2 Fileman fileop op=extract "sourcefiles=$1" "destfiles=$2" doubledecode=0 >/dev/null
}
cp_delete()  { # remote_path (file or dir) → moved out for good (no trash)
  api2 Fileman fileop op=unlink "sourcefiles=$1" doubledecode=0 >/dev/null
}
cp_write()   { # remote_dir file content
  uapi Fileman/save_file_content --data-urlencode "dir=$1" --data-urlencode "file=$2" --data-urlencode "content=$3" >/dev/null
}
cp_read()    { # remote_dir file
  curl -sS --get "$CP_HOST/execute/Fileman/get_file_content" --data-urlencode "dir=$1" --data-urlencode "file=$2" \
    | python3 -c 'import json,sys;d=json.load(sys.stdin);print((d.get("data") or {}).get("content") or "",end="")'
}
cp_ls()      { # remote_dir → names
  curl -sS --get "$CP_HOST/execute/Fileman/list_files" --data-urlencode "dir=$1" --data-urlencode "show_hidden=1" \
    | python3 -c 'import json,sys;[print(f["file"]) for f in (json.load(sys.stdin).get("data") or [])]'
}

# --- secrets --------------------------------------------------------------------
load_env() {
  if [ ! -s "$ENV_FILE" ]; then
    say "deploy/.env missing — restoring from the server's private copy"
    ( umask 077; cp_read "$REMOTE_PRIVATE" .env > "$ENV_FILE" )
    [ -s "$ENV_FILE" ] || die "No secrets on the server either (run deploy/setup-staging.sh first)."
  fi
  set -a; . "$ENV_FILE"; set +a
}
save_env_remote() { cp_write "$REMOTE_PRIVATE" .env "$(cat "$ENV_FILE")"; }
env_set() { # KEY VALUE  (add or replace in deploy/.env)
  ( umask 077; touch "$ENV_FILE"; grep -v "^$1=" "$ENV_FILE" > "$ENV_FILE.tmp" || true
    printf '%s=%s\n' "$1" "$2" >> "$ENV_FILE.tmp"; mv "$ENV_FILE.tmp" "$ENV_FILE" )
}

# --- HTTP to staging --------------------------------------------------------------
# REQUIRES the cPanel network secret to be scoped to cpanel.saraamrousy.net ONLY.
# If it also covers saraamrousy.net, the proxy replaces our Authorization header
# with the cPanel token → every call here gets 401 (and the token reaches WP).
# The folder is behind Directory Privacy. The deploy htpasswd user has the same
# name + password as the WP Application Password, so one Basic header passes both.
site_curl() { curl -sS -u "${WP_DEPLOY_USER}:${WP_DEPLOY_APPPASS}" "$@"; }
wp_rest() { # METHOD route [curl args...]
  local m="$1" r="$2"; shift 2
  site_curl -X "$m" "$STAGING_URL/?rest_route=$r" "$@"
}
check_gate() {
  local code; code=$(site_curl -o /dev/null -w '%{http_code}' "$STAGING_URL/?rest_route=/wp/v2/users/me")
  [ "$code" = 200 ] || die "Staging answered $code with the deploy login. 401 = the cPanel network secret still covers saraamrousy.net — scope it to cpanel.saraamrousy.net only, then start a new session."
}

# run_php 'php code' → uploads a one-shot script (random name + 128-bit token,
# behind the folder password), runs it inside WordPress as claude-deploy, deletes it.
run_php() {
  local code="$1" tok name f out
  tok=$(python3 -c 'import secrets;print(secrets.token_hex(16))')
  name="_plrun_$(python3 -c 'import secrets;print(secrets.token_hex(6))').php"
  f="$SCRATCH/$name"
  cat > "$f" <<PHP
<?php
if ( ! isset( \$_GET['t'] ) || ! hash_equals( '$tok', (string) \$_GET['t'] ) ) { http_response_code( 404 ); exit; }
register_shutdown_function( function () { @unlink( __FILE__ ); } );
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
\$u = get_user_by( 'login', 'claude-deploy' );
if ( \$u ) { wp_set_current_user( \$u->ID ); }
$code
PHP
  cp_upload "$f" "$STAGING_DIR"; rm -f "$f"
  if [ -n "${WP_DEPLOY_APPPASS:-}" ]; then out=$(site_curl "$STAGING_URL/$name?t=$tok"); else out=$(curl -sS "$STAGING_URL/$name?t=$tok"); fi
  cp_delete "$STAGING_DIR/$name" || true
  printf '%s\n' "$out"
}

# zip_dir src_dir out.zip  → zip with the folder name as the top level
zip_dir() { local src="$1" out="$2"; rm -f "$out"; ( cd "$(dirname "$src")" && zip -qr "$out" "$(basename "$src")" -x '*/.DS_Store' '*/node_modules/*' '*/tests/*' ); }
