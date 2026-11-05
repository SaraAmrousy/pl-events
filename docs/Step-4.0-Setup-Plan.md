# Step 4.0 — Setup plan v3: hands-off, browser only (agreed in Thread 5, 5 Nov 2026)

**Goal (Sara):** Sara does not work by hand and **cannot use the Claude Code desktop app** — everything runs from the browser. Claude Code **on the web** (claude.ai/code) does all setup, build and deploy work in a cloud sandbox linked to a private GitHub repo. Sara's part: a few clicks (repo, environment, one cPanel API token), approvals, and review on the staging site.

## Host facts (from Sara's cPanel screenshot, 5 Nov 2026)
- cPanel (Jupiter) on a shared WHM server; user `saraamro`; primary domain `saraamrousy.net`; SSL active; shared IP 51.89.126.113.
- Available: **Manage API Tokens**, SSH Access, Terminal, Cron Jobs, MultiPHP Manager, Directory Privacy, JetBackup 5, Git Version Control.
- Unknown until tested: whether the cloud sandbox can reach cPanel on port 2083 (else `https://cpanel.saraamrousy.net`), whether SSH works through the sandbox proxy, WP-CLI on the server.

## Decisions
| # | Decision | Why |
|---|---|---|
| S1 | **Work runs in Claude Code on the web** (claude.ai/code, browser), on the private GitHub repo **`SaraAmrousy/pl-events`**. Every change is a commit; Sara never handles files. | No desktop app, no local installs. |
| S2 | **One custom cloud environment `pl-events`**: Network access = **Custom**, keep the default list ("Also include default list…") and add `saraamrousy.net`, `*.saraamrousy.net`, `wordpress.org`, `*.wordpress.org`, `api.wordpress.org`, `downloads.wordpress.org`. **The cPanel token is a Network secret, not an environment variable:** host `saraamrousy.net` (+ `cpanel.saraamrousy.net`), header `Authorization`, prefix `cpanel`, value `saraamro:<token>`. The agent proxy adds it to requests; the session never sees it. | Reach the host and WordPress.org without opening everything. |
| S3 | **cPanel driven by its HTTPS API** (UAPI, API 2 where needed) with one token named `claude-code-pl`, **expiry ~1 month**, revoked when Phase 4 ends: database + user, Directory Privacy on the `pl` folder, cron. **No subdomain:** staging = **`https://saraamrousy.net/pl`**, in the folder Sara already made (`public_html/pl` — verify the real path). Check first whether the root of `saraamrousy.net` runs its own WordPress/`.htaccess` rules and make sure they don't catch `/pl/` (and never touch the root site). | Sara never clicks through cPanel; her choice of path (5 Nov). |
| S4 | **Deploy over HTTPS, not SSH:** build zips of `pl-events-core` + `hello-pl-child` → upload + extract with the cPanel API → activate/flush with the **WordPress REST API** (Application Password, kept as an env var). If SSH works through the sandbox, WP-CLI over SSH may be used too. | The sandbox proxy is HTTP(S); SSH may not pass. |
| S5 | **WordPress on staging:** installed once via cPanel API (WordPress core zip upload + extract + `wp-config.php` written by Claude Code) or Softaculous via its API — whichever the host allows; then the free plugins via the REST API. | Scripted. |
| S6 | **Test WordPress inside the sandbox** (PHP + SQLite integration + WP-CLI, rebuilt each session by a setup script) for PHP lint, activation and shortcode output. **Sara reviews only on staging.** | She can't open a local site from a cloud sandbox. |
| S7 | **Content is never synced as a DB dump.** Seeded by the plugin importer (REST route or WP-CLI) from `data/pl-data.json`; Elementor pages by template JSON (Thread 7). | No overwrites. |
| S8 | **Staging private:** "Discourage search engines" + `X-Robots-Tag: noindex` (mu-plugin) + Directory Privacy password; real cron every 15 min; JetBackup before risky deploys. | Surprise for PL. |

## Sara does (≈ 10 minutes, browser only)
1. ✅ Repo `SaraAmrousy/pl-events` created; Claude pushed the kit (5 Nov).
2. **claude.ai/code** → connect GitHub if asked → choose repo `SaraAmrousy/pl-events`.
3. Environment selector → **Add cloud environment** `pl-events` → Network access **Custom** → tick "Also include default list…" → paste the domains in S2 → Create.
4. **cPanel › Security › Manage API Tokens › Create** `claude-code-pl` with an expiry date → copy → edit the `pl-events` environment → **Network secrets › Add secret** (S2) → Add. No environment variables.
5. Start a session in that environment with the starter message; approve prompts; open the staging link to review.

## Claude Code does (everything else)
1. Check the environment (network reach to cPanel/WordPress.org, token works with a read-only UAPI call).
2. Find the `pl` folder path; check the root site's `.htaccess`; database + user (generated password given to Sara, never committed).
3. Install WordPress on staging; create an Application Password for itself; install Hello Elementor + free plugins; noindex; Directory Privacy; cron.
4. Write `deploy/deploy.sh` (zip → upload → extract → activate) and deploy a test plugin `pl-hello` → activate → show proof → remove.
5. Sandbox test WordPress setup script for 4.1 onwards.

## DoD
One command in a Claude Code web session deploys and activates a test plugin on `saraamrousy.net/pl`; staging is noindex and password-protected; Sara only did the 5 browser items above.
