# Step 4.0 — Setup: Step Summary (Thread 6, 5 Nov 2026)

**Status: ⏳ 90 % — one setting on Sara's side blocks the DoD.** Everything on the server is done and the one-command deploy is written; it cannot authenticate to staging until the cPanel network secret is narrowed (below). Plan followed: `docs/Step-4.0-Setup-Plan.md` (v3).

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
| 8 | Password | **Directory Privacy on `/pl`** ("PL staging"), users `sara` and `claude-deploy`. Anonymous visitors get **401** ✅. |
| 9 | noindex | mu-plugin `pl-staging.php` (forces "Discourage search engines", `X-Robots-Tag: noindex, nofollow` on every response, robots meta) ✅ uploaded. Remove at launch (6.3). |
| 10 | Secrets | `deploy/.env` (git-ignored) + private copy in `~/pl-deploy/.env` (0700, outside `public_html`). A new session restores it from the server automatically — the cPanel token is the only secret the environment needs. |
| 11 | Scripts | `deploy/lib.sh` (cPanel API + WP REST helpers) · `deploy/deploy.sh plugin|theme <folder>` (lint → zip → upload → replace → extract → activate → flush) · `deploy/setup-staging.sh` (Hello Elementor, Elementor, SCF, UAE HFB, Forminator, Rank Math, permalinks, then the deploy test) · `deploy/seed-staging.sh` (content from `data/pl-data.json`) · `deploy/test-plugin/pl-hello` · `deploy/sandbox/setup.sh` (sandbox WordPress for tests). |

## ⛔ The blocker — and the fix (Sara, 1 minute, browser)
The network secret is set for **`saraamrousy.net` + `*.saraamrousy.net`**. The agent proxy therefore **replaces the `Authorization` header on every request to the website itself**, not just to cPanel. Proven with a test script: whatever we send, PHP receives `Authorization: cpanel …`. Effects:
1. The folder password and the WordPress REST API (both use `Authorization: Basic`) always answer **401** → no plugin activation from here.
2. **The cPanel token is sent to the staging WordPress** on every request from the sandbox (any plugin could read it). The staging guard mu-plugin now strips a non-Basic `Authorization` header, but the real fix is to stop sending it.

**Fix:** claude.ai/code → environment `pl-events` → Edit → Network secrets → change the secret's host to **`cpanel.saraamrousy.net` only** (remove `saraamrousy.net` and `*.saraamrousy.net`). Then start a **new session** and say: "كمّل 4.0 — شغّل deploy/setup-staging.sh". That runs the free plugins install, the deploy test (`pl-hello` → activated → `/pl-hello/v1/ping` answers) and the noindex/401 checks = DoD.

Two workarounds were refused by the session's safety checks and **not** used: a secret-header bypass of the folder password (weakens protection) and an every-minute cron job runner on the server (persistent execution). Both stay off unless Sara asks for them.

## Decisions
| # | Decision | Why |
|---|---|---|
| 4.0-1 | cPanel API via **`https://cpanel.saraamrousy.net`**, not `:2083` | 2083 is blocked from the sandbox. |
| 4.0-2 | **PHP 8.3 for `/pl` only** via the folder's `.htaccess` | Domain is on 7.2; changing the domain would touch the root site. |
| 4.0-3 | Deploy user's folder-password login = its WP Application Password | One `Authorization: Basic` header passes both the folder password and WordPress. |
| 4.0-4 | Secrets live on the server outside `public_html` (`~/pl-deploy/.env`) | The sandbox is wiped each session; Sara never handles passwords. |
| 4.0-5 | Real cron for WP-Cron: **not set yet** | Adding a cron job was refused as "persistence" by the session guard. Needs Sara's OK (cPanel › Cron Jobs, every 15 min: `/opt/cpanel/ea-php83/root/usr/bin/php /home/saraamro/public_html/pl/wp-cron.php`), or I add it through the API once she approves the permission prompt. Until then the plugin's hourly status check runs only when someone visits wp-admin, and the on-screen JS re-check keeps status honest. |

## Passwords (not in git)
- Staging folder password: user **`sara`** — password in `~/pl-deploy/.env` (`STAGING_GATE_PASS`). WordPress admin: user **`sara`** (`WP_ADMIN_PASS`). I'll paste both in the chat when Sara asks; never committed.

## Open questions for Sara
1. Narrow the network secret (above) — the only thing blocking the DoD.
2. OK to add the real cron job (every 15 min, `wp-cron.php` via PHP CLI)?
3. Token expiry: S3 said ~1 month — confirm the date it was set to.
