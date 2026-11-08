# Step 6.3 — Launch checklist (Thread 9, 6 Nov 2026)

**Status: ✍️ WRITTEN ONLY — nothing has been done on plevents.org.** Reveal ✅ 8 Nov 2026. Thread 10 starts with **§0b round 8**. Every step that touches PL's live server (**🔒**) runs only after Sara approves *that* step in the chat. Each step lists what it changes, how to check it, and how to undo it.

Skill: `pl-events-data-wp`. Sources:
- 2.2 D9 (cron, status check, cache purge)
- 4.0 setup plan
- 5.3 (301 map, sitemap)
- 6.1 QA
- 4.5 (forms/SMTP)

---

## 0b. Round 8 — before launch (first thing in Thread 10)
Small fixes found while closing 6.1b round 7 / 6.2c. Done on the sandbox → tested → deployed to **staging** (not plevents.org), then the launch starts.
| # | Fix | Where | Done when |
|---|---|---|---|
| 8.1 | **Email subjects without brackets**: "Password Reset", not "[PL Events] Password Reset" (all WordPress emails; ours already have none) | `PL_Mail` (`wp_mail` filter / core subject filters) | The 8 emails in `docs/emails-6.1b-r7.md` re-captured: no `[` at the start of any subject |
| 8.2 | **Emails 3 + 4** (password changed / email changed) say "contact … at **Website support email**" (Company › Website support; empty = head office), **not** `admin_email`. On the live site, `admin_email` (Settings › General) = **PL Team's** address, never a person's | `PL_Mail` (`password_change_email` / `email_change_email` filters); launch §3 sets `admin_email` | Both emails show the support email; live `admin_email` checked = PL Team |
| 8.3 | **Email 7** (Contact notification): "Updates" shows **Yes / No**, never empty | Forminator notification / our field value | Two test messages (box ticked / not ticked) → "Yes" / "No" |
| 8.4 | **bidi** (mixed Arabic/English direction) in the Help drawings: English words, numbers and `px` inside Arabic lines come out in the wrong order | `tools/guide/diagrams/*.html` (`<bdi>` / `dir="ltr"` spans) | Drawings re-rendered and checked by eye at 100% |
| 8.5 | **Contradictions in guide.json**: (a) the "?" button is **bottom right** everywhere (some texts say otherwise); (b) Hero sizes/texts come **only from the spec** (`{{spec:hero.*}}`), no typed numbers | `tools/guide/guide.json` → plugin `help/guide.json` | A text search finds no other "?" position and no hand-typed hero size |
| 8.6 | **Sandbox name while recording**: screenshots/videos show "PL sandbox" (title bar, emails) → set the site name to **PL Events** during the run, restore after | `tools/guide/run.sh` / `record.js` | OCR audit: "sandbox" appears in no shot / frame / caption |

After 8.1–8.6: re-record only the tasks touched (`run.sh --only=…`), OCR audit clean, deploy-help + deploy + purge, admin-check + click sweep 100%, docs (6.1b round 8), commit + push on main.

## 0. Decisions Sara must take before we start
| # | Decision | Options | Recommendation |
|---|---|---|---|
| L1 | **Where does the new site run?** | (a) PL's current host (plevents.org's cPanel), (b) Sara's host, with the domain pointed there | (a), if PL gives us a cPanel API token for plevents.org (same method as staging). Needs **PL's** approval. |
| L2 | **How does the content move?** | (a) Fresh WordPress on the live host + our plugin/theme + content **exported from staging** (`wp pl export` → the same JSON shape → importer), with media copied; (b) full-site copy (DB + files) with search-replace | **(a)**: the project rule is "content is never pushed as a database dump". It's also a clean install with no staging leftovers. |
| L3 | Launch window | A quiet day, not during a congress | A weekday morning, Cairo time, with PL reachable. |
| L4 | Who receives the forms | Contact → the first email of the office the visitor picks (PL Website › Offices); Careers → **PL Website › Company › CVs go to** (empty = head office). Both follow the data at once since 6.2b. | **PL to name the HR address**, then type it in *CVs go to*: no code. |
| L5 | Analytics | None / privacy-friendly (no cookie banner) / GA4 (needs cookie consent) | Privacy-friendly or none. GA4 means a consent banner and privacy text. |
| L6 | **Newsletter** (Step 4.6 §7) | PL runs campaigns from **both** Zoho and Mailchimp → Destination **Both**. Open: do all sign-ups go to both, or are some campaigns tied to a specific list (tags → lists)? Which Mailchimp audience and which Zoho list (its signup form must be **off**, or a separate "Website sign-ups" list)? Who owns each account and connects it on launch day? Existing tags/groups to map? Is the Mailchimp audience GDPR-enabled? | Answers from **PL**. |

## 1. Content readiness (T-7 days) — from PL (5.2)
- [ ] All events entered/checked by PL in wp-admin (5.2). **No "Sample" items left** (PL Website › Company counter = 0).
- [ ] Open TBCs closed (Step 5.1 list):
  - showreel: real footage, or keep the stills and drop the tag;
  - **office opening hours**;
  - **privacy policy text + CV retention period**;
  - ESOIRS 2024 edition;
  - founding year (2011 vs 2013);
  - registration state of upcoming events;
  - El Safwa old page = 2026 edition?
- [ ] Society/sponsor logos uploaded, with permission (3.4a).
- [ ] Portfolio PDF compressed (it's 34.9 MB today) → upload.
- [ ] **PageSpeed Insights (mobile) on staging**: LCP < 2.5 s on Home, Events, one event page (6.1 open item).
- [ ] Sara clicks Add to calendar → Google / Outlook once (6.1 open item).

## 2. Freeze + backups (T-1)
- [ ] Content freeze on staging (PL stops editing; exported in step 4).
- [ ] 🔒 **Full backup of the current plevents.org**: files + database, with cPanel "Full Backup" or the host's tool, downloaded and kept off the server. *Undo for everything below.*
- [ ] 🔒 Read the live `plevents.org/wp-sitemap.xml` (**read only**) → fill the **3 TBC old slugs** in `deploy/launch/redirects.csv` (Egyptian Lymphedema, 8th Repro 2025, Alex Eye Hospital) and add any old URL the 1.1/2.1 lists missed.
- [ ] Lower the DNS TTL to 300 s (only if L1 = b).

## 3. Build the new site (launch day, morning)
- [ ] 🔒 New WordPress (L1/L2): PHP 8.3, HTTPS, the same free plugins as staging (Elementor, SCF, UAE HFB, Forminator, Rank Math, LiteSpeed Cache, WP Mail SMTP). **Not** the staging mu-plugin.
- [ ] 🔒 `deploy/deploy.sh plugin …` and `… theme …` against the live host (safe swap: verify → roll back on failure).
- [ ] 🔒 Content: `wp pl export` on staging → `pl-data.json` + media → importer on live → check counts on PL Website › Overview (events / congresses / countries / cities match staging).
- [ ] 🔒 `plt_provision()`: pages, header/footer, menus, forms, page SEO (`plt_provision_seo`).
- [ ] 🔒 **Accounts:** PL's real accounts are created **on live, on launch day, with their own email addresses** (PL Editor for the editor; Administrator only for whoever PL names). Each person sets their own password through the "set your password" email. **Staging users are never copied** (not `sara`, not `PL@Admin`, not `claude-deploy`): content moves by export/import, users don't.
- [ ] 🔒 **Sign-in address = staging:** `pl-events-core` serves sign-in at `https://plevents.org/plevents/`; `/wp-admin` and `/wp-login.php` → 404 when signed out; 5 wrong passwords → 15-minute pause per IP. Check after deploy: `/plevents/` 200, `/wp-admin/` 404 signed out, password-reset email link points to `/plevents/?action=rp…`. Tell PL the address (Editor Guide). No extra plugin needed. A different slug can be set with `define( 'PL_LOGIN_SLUG', '…' )` in wp-config.php. **Rate-limit IP:** on live, check that `REMOTE_ADDR` is the visitor's public IP (as on staging). Only if PL's host or a CDN (e.g. Cloudflare) sits in front, set `define( 'PL_TRUSTED_PROXIES', '…' )` with **that proxy's** IP ranges; otherwise everyone shares one lock. Then run `tools/admin-check.js --lock` once.
- [ ] 🔒 Settings:
  - Site title "PL Events";
  - permalinks `/%postname%/`;
  - timezone Africa/Cairo;
  - Rank Math as on staging (modules: sitemap, rich-snippet, seo-analysis; wizard skipped; Event rich snippet off; Organization = PL Events + logo);
  - Privacy page set.
- [ ] 🔒 **No code editing in wp-admin** (6.2b): `define( 'DISALLOW_FILE_EDIT', true );` in the live `wp-config.php` (staging has it). Check: no **Theme File Editor** / **Plugin File Editor** in the menu, `/wp-admin/theme-editor.php` refuses. `tools/admin-check.js` tests it.
- [ ] **Website support** (round 7): PL Website › Company › Website support — email / WhatsApp = PL's team contact for the staff (empty = the head office's). Check PL Help › تكلموا مين shows both buttons.
- [ ] **Page texts** (round 7): nothing to do; content moves with the pages. After `plt_provision()` check PL Website › Page texts opens with 42 fields and the pages look as on staging (they print `[pl_t]`).
- [ ] 🔒 **PL Help media** (6.2b): `deploy/deploy-help.sh` against the live host (it uploads `docs/guide/{shots,pairs,video}` to `wp-content/uploads/pl-help/` with its `.htaccess`). Check: PL Website › Help shows pictures + videos when signed in; `…/wp-content/uploads/pl-help/shots/event-add-1.png` answers **403** signed out; `admin-post.php?action=pl_help_media…` is not 200 signed out. Not in Media, not in the sitemap. If the live site looks different from staging later, re-record (`bash tools/guide/run.sh`) and run it again.

## 4. Mail
- [ ] 🔒 A mailbox on PL's domain for the site (e.g. `website@plevents.org`), with SPF/DKIM on the domain. WP Mail SMTP by constants (password in a file outside the web root, like `~/pl-deploy/.env`), **never in wp-admin or the chat**.
- [ ] **Remove the staging catch-all** (every email → Sara). It lives in the staging mu-plugin, which is not installed on live. Check: `plt_mail_override` is empty.
- [ ] **Newsletter (4.6, L6):** remove Sara's test keys; connect PL's Mailchimp audience + a Zoho list with the **signup form off**; Test connection; Destination **Both**; **connect both webhooks on live** (new secret on the live site) → both **Connected**. The confirmation email goes out through the site's mailbox (above), so SPF/DKIM/DMARC must be set first. Also authenticate plevents.org in both providers. The staging mu-plugin (`PL_NL_TEST_ONLY`) is not on live. **Test:** sign up → one email → confirm → in both lists with tags → unsubscribe in one → gone from the other; delete the test contact. Steps: `docs/Step-4.6-Newsletter.md` §7.
- [ ] Emails speak as the team (round 7, in code): From name **PL Events Team**, signed "— PL Events Team". Send one password reset to yourself and check From, sign-off and that the link is on `/plevents/`.
- [ ] Forms: Contact routing by office (first email of each office); Careers → **Company › CVs go to** (L4); CV direct-URL block (`deploy/htaccess-cv.sh`). **Test:** one real message per form, received at the right address with the CV attached.

## 5. Switch on (launch)
- [ ] 🔒 Point the domain / swap the document root (L1). HTTPS valid on `plevents.org` and `www` (one canonical; the other 301s to it).
- [ ] 🔒 **Remove noindex:** Settings › Reading › "Discourage search engines" **off** (`blog_public = 1`). There's no staging mu-plugin on live, so no X-Robots-Tag. Check: `curl -I https://plevents.org/` has **no** `noindex`, and the page source shows `robots: index, follow`.
- [ ] 🔒 **301 map:** import `deploy/launch/redirects.csv` into Rank Math › Redirections (410s as "Content deleted", `/event/` and `/author/` as "Starts with"). Then run `python3 deploy/launch/check-redirects.py https://plevents.org` → it **must report 0 failed** (5.3 DoD).
- [ ] 🔒 Real server cron: cPanel cron every 15 min → `wp-cron.php` (as on staging), plus `DISABLE_WP_CRON` true. Check the **hourly status check** is scheduled and runs (`wp cron event list` shows `pl_hourly_status_check` with a next run within the hour; `wp pl status_check` runs it by hand) and that the **page-cache purge** on save works (edit an event → the cached page refreshes).
- [ ] 🔒 LiteSpeed Cache: the staging settings (page cache on; /contact/ and /careers/ excluded; CSS/JS optimisation off) + `deploy/htaccess-cache.sh`. Purge once.
- [ ] Rank Math: canonicals now present (they were hidden while noindex). Sitemap `/sitemap_index.xml` 200.

## 6. Check right after launch (same day)
- [ ] The 6.1 crawl against `https://plevents.org`, with **external links included** (they couldn't be checked from the sandbox):
  - 0 broken internal links;
  - 0 external 4xx/5xx;
  - one H1 per page;
  - no overflow at 375/768/1280.
- [ ] axe 0 violations; Lighthouse/PSI mobile: LCP < 2.5 s, CLS < 0.1.
- [ ] Event schema valid (Rich Results Test on one event page); Organization with both offices.
- [ ] Forms: one real test each (step 4).
- [ ] Old URLs: `check-redirects.py` = 0 failed.
- [ ] Staging: keep it, **still noindex**, as PL's test copy. Or retire it (Sara decides). Either way its emails keep going to Sara only.

## 7. Search engines (T+0 → T+1)
- [ ] 🔒 Google Search Console: verify the domain (DNS TXT), submit `https://plevents.org/sitemap_index.xml`, request indexing for Home + Events.
- [ ] Bing Webmaster Tools: import from Search Console.
- [ ] Analytics (L5) installed, if chosen.

## 8. Monitoring (T+1 → T+30)
- [ ] Uptime monitor (free, e.g. UptimeRobot) on `/` and `/events/`, alerts to Sara + PL.
- [ ] Search Console: Coverage / 404 report on days 2, 7 and 30. Add any old URL that still gets traffic to the 301 map.
- [ ] Weekly for the first month: backups run; the status check moves events to "Past" on their own (watch an event end).
- [ ] T+30 handover call with PL's editor (`docs/Editor-Guide.md`).

## Rollback
- Before step 5: nothing is public, so just stop.
- After step 5:
  - point the domain / document root back to the old site (backup from step 2);
  - Search Console: nothing to undo (only the sitemap was submitted);
  - 301s live inside the new site only, so they disappear with it.

## Still open (owners)
| Item | Owner |
|---|---|
| L1–L6 decisions | Sara (+ PL for L1, L4, L6) |
| Newsletter test accounts (Zoho + Mailchimp, free) + keys + webhooks in wp-admin (4.6 §6) | Sara |
| Passwords for `sara` and `PL@Admin` on staging (wp-admin › Users › Edit › Set new password) | Sara |
| PL data (5.2), TBCs, logos + permission, PDF | PL |
| PSI LCP numbers on staging; Google/Outlook click test | Sara |
| plevents.org cPanel access (API token, as a network secret like staging) | PL → Sara |
