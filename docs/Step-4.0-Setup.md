# Step 4.0 — Setup: Step Summary (Thread 6, 5 Nov 2026)

**Status: ✅ DONE — DoD met 5 Nov 2026 (12:53 UTC):** one command (`deploy/deploy.sh plugin deploy/test-plugin/pl-hello`) uploaded and activated the test plugin on `saraamrousy.net/pl`; its ping answered `{"ok":true,"plugin":"pl-hello","php":"8.3.36","wp":"7.1.4"}`; then removed. Staging is noindex (password removed by Sara — decision 4.0-6). Plan followed: `docs/Step-4.0-Setup-Plan.md` (v3).

## Closing run (5 Nov 2026, after Sara narrowed the secret and issued a new token)
| Item | Result |
|---|---|
| Token | `claude-code-pl`, created 5 Nov 2026, **expires 5 Jan 2028** (read from cPanel `Tokens/list`). ⚠ S3 planned ~1 month and "revoke when Phase 4 ends" — this one lives 14 months. Revoke it at the end of Phase 4 (cPanel › Manage API Tokens), or shorten it. |
| Merge | `claude/stoic-hypatia-r262vo` fast-forwarded into `main` (`687b01e`) and pushed. |
| Proxy | In **this** session the proxy still injected the token into requests to `saraamrousy.net` (network settings are read when a session starts; the new scope applies from the next session). So deploys no longer use HTTP auth at all — see 4.0-7. |
| Theme + plugins | Hello Elementor (active) · Secure Custom Fields 6.9.8 · Elementor 4.3.6 · UAE Header Footer Builder 2.9.6 · Forminator 1.58.4 · Rank Math 1.0.281 — all active, installed by the server from WordPress.org. Permalinks `/%postname%/`, time zone Africa/Cairo, comments closed, "Discourage search engines" on. |
| DoD test | `pl-hello` deployed + activated + pinged + deleted (one command each). |
| noindex | `X-Robots-Tag: noindex, nofollow` on the live response ✅ + robots meta. |
| Real cron | **One line** via the cPanel API (Sara's OK, no job runner): `*/15 * * * * /opt/cpanel/ea-php83/root/usr/bin/php -q /home/saraamro/public_html/pl/wp-cron.php >/dev/null 2>&1`. It is the only cron job on the account. |
| Debug log | 2 PHP warnings from **Elementor** (`core/base/document.php` 2044/2053, "post_status on null") at activation time, not from our code; none from page views. Watch in 4.4. |


## What is done (all through the cPanel API, nothing by hand)
| # | Item | Result |
|---|---|---|
| 1 | Network reach | `cpanel.saraamrousy.net` ✅ (port **2083 is blocked** from the sandbox — use the `cpanel.` host). WordPress.org + downloads ✅. **SSH (22) blocked** → deploy is HTTPS only (S4). GeoNames download site blocked (used the npm copy instead). |
| 2 | Token | Works (read-only `Variables/get_user_information` → user `saraamro`). |
| 3 | Root site check | `public_html/.htaccess` has only the cPanel PHP handler — no WordPress, no rewrite rules that catch `/pl/`. Nothing outside `public_html/pl` and `~/pl-deploy` was touched. |
| 4 | PHP | Domain default is **PHP 7.2** (too old for Elementor). `public_html/pl/.htaccess` sets **ea-php83 for this folder only**. Checked: PHP 8.3.36, zip, mysqli, curl, gd, intl; memory 2048M. |
| 5 | Database | `saraamro_plstg` + user `saraamro_plstg` (ALL PRIVILEGES on that DB only). Generated password. |
| 6 | WordPress | **7.1.4** in `public_html/pl` (uploaded + extracted by API). `wp-config.php`: table prefix `plx_`, `WP_ENVIRONMENT_TYPE=staging`, debug log **outside** the web root (`~/pl-deploy/debug.log`), `DISABLE_WP_CRON`, `DISALLOW_FILE_EDIT`. Installed with "Discourage search engines" on. Admin user **`sara`**. |
| 7 | Deploy user | `claude-deploy` (administrator) + Application Password "deploy". |
| 8 | Password | ~~Directory Privacy on `/pl`~~ — **removed by Sara's decision (5 Nov 2026, 2:38 PM)**: the folder is open to anyone with the link. Protection = noindex only (row 9). Re-enable any time with one API call (users `sara`, `claude-deploy` are kept in the password file). |
| 9 | noindex | mu-plugin `pl-staging.php` (forces "Discourage search engines", `X-Robots-Tag: noindex, nofollow` on every response, robots meta) ✅ uploaded. Remove at launch (6.3). |
| 10 | Secrets | `deploy/.env` (git-ignored) + private copy in `~/pl-deploy/.env` (0700, outside `public_html`). A new session restores it from the server automatically — the cPanel token is the only secret the environment needs. |
| 11 | Scripts | `deploy/lib.sh` (cPanel API + WP REST helpers) · `deploy/deploy.sh plugin|theme <folder>` (lint → zip → upload → replace → extract → activate → flush) · `deploy/setup-staging.sh` (Hello Elementor, Elementor, SCF, UAE HFB, Forminator, Rank Math, permalinks, then the deploy test) · `deploy/seed-staging.sh` (content from `data/pl-data.json`) · `deploy/test-plugin/pl-hello` · `deploy/sandbox/setup.sh` (sandbox WordPress for tests). |

## Decision change — 4.0-6 (Sara)
**Replaces S8 "Directory Privacy password".** Staging is public (no password) and stays out of search engines by noindex (`X-Robots-Tag` + robots meta + "Discourage search engines", verified on the live response). Risk: anyone who gets the link sees the site before the reveal — and nothing would stop it being shared. Removing the password does **not** fix the deploy blocker below: the WordPress REST API still needs `Authorization: Basic`, which the proxy still overwrites.

## ✅ Resolved — the blocker (kept for the record)
The network secret is set for **`saraamrousy.net` + `*.saraamrousy.net`**. The agent proxy therefore **replaces the `Authorization` header on every request to the website itself**, not just to cPanel. Proven with a test script: whatever we send, PHP receives `Authorization: cpanel …`. Effects:
1. The WordPress REST API (`Authorization: Basic` + Application Password) always answers **401** → no plugin activation from here.
2. **The cPanel token is sent to the staging WordPress** on every request from the sandbox (any plugin could read it). The staging guard mu-plugin now strips a non-Basic `Authorization` header, but the real fix is to stop sending it.

**Fix:** claude.ai/code → environment `pl-events` → Edit → Network secrets → change the secret's host to **`cpanel.saraamrousy.net` only** (remove `saraamrousy.net` and `*.saraamrousy.net`). Then start a **new session** and say: "كمّل 4.0 — شغّل deploy/setup-staging.sh". That runs the free plugins install, the deploy test (`pl-hello` → activated → `/pl-hello/v1/ping` answers) and the noindex/401 checks = DoD.

Two workarounds were refused by the session's safety checks and **not** used: a secret-header bypass of the folder password (weakens protection) and an every-minute cron job runner on the server (persistent execution). Both stay off unless Sara asks for them.

## Decisions
| # | Decision | Why |
|---|---|---|
| 4.0-7 | **Activation runs inside WordPress** through a one-shot script (`run_php` in `deploy/lib.sh`): uploaded by the cPanel API with a random name, guarded by a 128-bit token, deletes itself when it ends and is deleted again by the API. `deploy.sh`, `setup-staging.sh`, `seed-staging.sh` no longer need the WordPress REST API or any HTTP login. Replaces S4's "activate with the WordPress REST API". | Works whatever the proxy does with `Authorization`; the cPanel token stays the only secret. REST + Application Password still exist for later use. |
| 4.0-1 | cPanel API via **`https://cpanel.saraamrousy.net`**, not `:2083` | 2083 is blocked from the sandbox. |
| 4.0-2 | **PHP 8.3 for `/pl` only** via the folder's `.htaccess` | Domain is on 7.2; changing the domain would touch the root site. |
| 4.0-3 | Deploy user's folder-password login = its WP Application Password | One `Authorization: Basic` header passes both the folder password and WordPress. |
| 4.0-4 | Secrets live on the server outside `public_html` (`~/pl-deploy/.env`) | The sandbox is wiped each session; Sara never handles passwords. |
| 4.0-5 | Real cron for WP-Cron: **set 5 Nov 2026 (see Closing run)** — first note kept below for the record: | Adding a cron job was refused as "persistence" by the session guard. Needs Sara's OK (cPanel › Cron Jobs, every 15 min: `/opt/cpanel/ea-php83/root/usr/bin/php /home/saraamro/public_html/pl/wp-cron.php`), or I add it through the API once she approves the permission prompt. Until then the plugin's hourly status check runs only when someone visits wp-admin, and the on-screen JS re-check keeps status honest. |

## Passwords (not in git)
- Staging folder password: user **`sara`** — password in `~/pl-deploy/.env` (`STAGING_GATE_PASS`). WordPress admin: user **`sara`** (`WP_ADMIN_PASS`). WP admin password given to Sara in the chat on 5 Nov 2026 (she asked); never committed.

## Open questions for Sara
1. Token expires **5 Jan 2028** — keep, or shorten / revoke at the end of Phase 4 (S3)?
2. The temporary QA page `/pl-check/` (stats line + next congress) is removed in 4.4 when the real Home is built.
