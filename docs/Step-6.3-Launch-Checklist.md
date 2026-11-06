# Step 6.3 — Launch checklist (Thread 9, 6 Nov 2026)

**Status: ✍️ WRITTEN ONLY — nothing has been done on plevents.org.** Every step that touches PL's live server (**🔒**) runs only after Sara approves *that* step in the chat. Each step lists what it changes, how to check it, and how to undo it.

Skill: `pl-events-data-wp`. Sources:
- 2.2 D9 (cron, status check, cache purge)
- 4.0 setup plan
- 5.3 (301 map, sitemap)
- 6.1 QA
- 4.5 (forms/SMTP)

---

## 0. Decisions Sara must take before we start
| # | Decision | Options | Recommendation |
|---|---|---|---|
| L1 | **Where does the new site run?** | (a) PL's current host (plevents.org's cPanel), (b) Sara's host, with the domain pointed there | (a), if PL gives us a cPanel API token for plevents.org (same method as staging). Needs **PL's** approval. |
| L2 | **How does the content move?** | (a) Fresh WordPress on the live host + our plugin/theme + content **exported from staging** (`wp pl export` → the same JSON shape → importer), with media copied; (b) full-site copy (DB + files) with search-replace | **(a)**: the project rule is "content is never pushed as a database dump". It's also a clean install with no staging leftovers. |
| L3 | Launch window | A quiet day, not during a congress | A weekday morning, Cairo time, with PL reachable. |
| L4 | Who receives the forms | Contact → office emails (routing exists); Careers → HR address? | **PL to name the HR address.** |
| L5 | Analytics | None / privacy-friendly (no cookie banner) / GA4 (needs cookie consent) | Privacy-friendly or none. GA4 means a consent banner and privacy text. |

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
- [ ] 🔒 Settings:
  - Site title "PL Events";
  - permalinks `/%postname%/`;
  - timezone Africa/Cairo;
  - Rank Math as on staging (modules: sitemap, rich-snippet, seo-analysis; wizard skipped; Event rich snippet off; Organization = PL Events + logo);
  - Privacy page set.

## 4. Mail
- [ ] 🔒 A mailbox on PL's domain for the site (e.g. `website@plevents.org`), with SPF/DKIM on the domain. WP Mail SMTP by constants (password in a file outside the web root, like `~/pl-deploy/.env`), **never in wp-admin or the chat**.
- [ ] **Remove the staging catch-all** (every email → Sara). It lives in the staging mu-plugin, which is not installed on live. Check: `plt_mail_override` is empty.
- [ ] Forms: Contact routing by office; Careers → HR (L4); CV direct-URL block (`deploy/htaccess-cv.sh`). **Test:** one real message per form, received at the right address with the CV attached.

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
| L1–L5 decisions | Sara (+ PL for L1, L4) |
| PL data (5.2), TBCs, logos + permission, PDF | PL |
| PSI LCP numbers on staging; Google/Outlook click test | Sara |
| plevents.org cPanel access (API token, as a network secret like staging) | PL → Sara |
