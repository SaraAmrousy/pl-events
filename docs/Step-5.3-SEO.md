# Step 5.3 — SEO: Step Summary (Thread 8, 6 Nov 2026)

**Status: ✅ DONE (closed 6 Nov 2026).** Live on staging and checked on `saraamrousy.net/pl` (22 URLs). The 301 map is filled in from `docs/301-Sources-Step-1.1-2.1.md`, ready for launch, and **not applied on staging**. Skill: `pl-content-qa`.

## What is live on staging
| Item | How | Check |
|---|---|---|
| Site name | `blogname` = **PL Events** (was "PL Events (staging)") | `<title>` on the 404 page reads "… – PL Events" |
| 7 page titles + descriptions | Rank Math `rank_math_title` / `rank_math_description`. Source: `tools/build-elementor.py` → `SEO`, then `elementor/pl-*.json` → `pl.seo`, then `plt_provision_seo()`. Empty fields only: text an editor typed in Rank Math is never overwritten | 7/7 unique · titles 26–57 chars · descriptions 117–151 chars |
| 15 event pages | `PL_SEO` in the plugin builds title + description from the event data: name + city + year, status ("Held" / "Registration is open."), venue, type, specialty. If an editor fills the Rank Math fields, the editor's text wins | 15/15 unique · descriptions ≤ 154 chars · whole sentences, never cut with "…" |
| Organization schema (every page) | `PL_SEO::organization()` → name, logo, slogan, both offices (2 PostalAddress + 2 ContactPoint with phone, email, area served), 4 social profiles. It is merged into Rank Math's own graph | 22/22 pages |
| Event schema (every event page) | `PL_SEO::event_schema()` → start/end dates, Place (venue, city, country), status, organiser → `#organization`, `sameAs` society site, `offers` = registration link (only for events that aren't past; InStock when registration is open). Rank Math's own Event snippet for the CPT is **off** (no duplicates) | 15/15, one Event each |
| Without Rank Math | the same JSON-LD and the event meta description are printed by the plugin | sandbox, Rank Math off ✅ |
| noindex stays on | blog_public 0 + `X-Robots-Tag` + `wp_robots` + **new**: `rank_math/frontend/robots` forced to noindex, nofollow (mu-plugin `deploy/mu/pl-staging.php`) | 22/22 `noindex, nofollow` |
| Sitemap ready, **not submitted** | Rank Math sitemap: pages + events only (posts, attachments, taxonomies, authors, HTML sitemap off; the WP default "Sample Page" excluded). Search Console / Bing: nothing submitted. **Instant Indexing (IndexNow) module off.** The mu-plugin also blocks every HTTP request to IndexNow / Bing / Google ping / Indexing API / Yandex from staging, and logs it | `sitemap_index.xml` → `page-sitemap.xml` (7) + `event-sitemap.xml` (15), all 200 |
| Rank Math modules | kept: sitemap, rich-snippet, seo-analysis. Off: analytics, content-ai, ai-visibility, instant-indexing, link-counter, woocommerce, buddypress, bbpress, acf, web-stories, role-manager | — |
| Alt text / H1 | 272 images on 22 URLs: 0 without alt (decorative ones are `alt=""` / aria-hidden) · one H1 per page | ✅ |

### Fixed on the way
- **Rank Math was not running on the front end at all.** Its setup wizard had never been finished or skipped, so `registration->invalid` stopped it before the module manager loaded. Result: no titles, no descriptions, no sitemap (all 404). Fixed by setting `rank_math_registration_skip` (the same as clicking "Skip" in the wizard). No account is connected.
- The event title template repeated the year ("ESOIRS Annual Congress 2024 — Alexandria 2024"). Now the year and city are added only when the name doesn't already contain them.

## Decisions
| # | Decision | Why |
|---|---|---|
| 5.3-1 | Event titles are `{name} — {city} {year} \| PL Events`. When that is over 62 characters, the title is the name alone, without the brand | Three names are 70 characters (El Safwa, PHLEBOEGYPT, MOCAT 6). Google shows about 60. The name matters more than the brand |
| 5.3-2 | Page SEO text lives in the build script next to the page copy, and provisioning fills empty fields only | One source; an editor's Rank Math edits survive re-provisioning |
| 5.3-3 | Schema comes from `PL_Data` (the same data the page shows), not from Rank Math's Schema builder | It can never disagree with the page. Rank Math Free's Event builder needs manual fields per post |
| 5.3-4 | `eventStatus` is always `EventScheduled` | Past events happened as scheduled. Status (past/live/upcoming) is shown by dates, which Google reads |

## 301 map (for launch — **not applied on staging**, plevents.org not touched)
- Source: `docs/301-Sources-Step-1.1-2.1.md` (Steps 1.1 + 2.1). File: `deploy/launch/redirects.csv` → **27 rows**: 15 × 301 (3 of them waiting for an old slug), 10 × **410 gone**, 2 × keep (same path).
  - 2.1 renames: `/about-us/` → `/about/` · `/our-events/` → `/events/` · `/career/` → `/careers/` · `/our-services/` → `/services/` · `/contact/` stays.
  - `/registration/` (every old "Register Now") → `/events/?tab=upcoming`.
  - Old Events Calendar: `/event/deoss-congress-2027/` → `/events/deoss-atmo-2027/`; any other `/event/…` (Starts with) → `/events/?tab=past`.
  - Orphan event pages: `/el-safwa/` → `/events/el-safwa-2026/` (PL to confirm the old page is the 2026 edition). `/delta-meeting/` and `/conference-of-ophthalmology-departments/` → `/events/?tab=past` until PL adds them (5.2). **`/pan-alex-2027/` → `/events/`, not `?tab=past`**: a 2027 event isn't past, so I departed from the rule here on purpose.
  - **410:** `/author/…` (B2, the staff Gmail) + `/wp-sitemap-users-1.xml` (same Gmail), `/upload/` (B3), `/login/`, `/register/`, `/account/`, `/password-reset/`, `/reset-password/`, `/test/`, `/oral-presentation/`. `/wp-sitemap.xml` → `/sitemap_index.xml`.
  - **Rank Math Free supports this** (checked in the plugin code on staging, read only): 410 and 451, and "Starts with" / regex matches. It can also export the list as `.htaccess`.
  - Portfolio PDF (B5): same path, kept (34.9 MB — compress before launch).
- **3 rows still TBC** (old slugs for Egyptian Lymphedema, 8th Repro 2025, Alex Eye Hospital). The source says these come from the live `plevents.org/wp-sitemap.xml`, read only, **at launch (6.3)**. Their targets are already set.
- Every target was checked on staging: all 200 except the PDF (not on staging; it moves at launch).
- Checker `deploy/launch/check-redirects.py` (read only): 301 = exactly one hop to the target, which answers 200; 410; keep = 200; "start" rows tested with a sample path. Proven against a local mock server that applies the CSV: **24/24 OK, 3 skipped (TBC)**. Against staging it fails, as it should, because nothing is applied there.
- At launch (6.3): read the live sitemap → fill the 3 TBC slugs → add any URL the lists missed → import into Rank Math › Redirections → `check-redirects.py https://plevents.org` must report 0 failed (DoD "no broken old URLs remain").

New URLs: `/` · `/events/` · `/events/{slug}/` (15: esoirs-2024, asaic-2024, deoss-2025, egyptlymphedema-2025, asaic-2025, meom-2025, srs-2026, el-safwa-2026, phleboegypt-2026, bridge-retina-2026, esoirs-2026, asaic-2026, meom-2026, deoss-atmo-2027, mocat-6-2027) · `/about/` · `/services/` (+ `#sponsorship`) · `/careers/` · `/contact/` (+ `?topic=` / `?office=`) · `/privacy/` · `/sitemap_index.xml`.

## Files
- `wp-content/plugins/pl-events-core/includes/class-pl-seo.php` (new), `class-pl-plugin.php` (boots it), `class-pl-event-page.php` (fallback meta uses the same description)
- `wp-content/themes/hello-pl-child/inc/setup.php` (`pl_brand_logo` → logo for the schema), `inc/provision.php` (`plt_provision_seo()`, called by `plt_provision()`)
- `tools/build-elementor.py` → `elementor/pl-*.json` (`pl.seo`)
- `deploy/mu/pl-staging.php` (Rank Math noindex + search-engine request block)
- `deploy/launch/redirects.csv`, `deploy/launch/check-redirects.py`

## Open (for 6.3 launch — not blockers for 5.3)
1. The 3 TBC old slugs + a full read of the live `wp-sitemap.xml` (read only).
2. Canonical tags: Rank Math leaves them out while a page is noindex. Check that they appear after noindex is removed.
3. Event posters are typographic (no image), so Event schema has no `image`. Google may warn about it (a warning, not an error). Real posters from PL (5.2) fix this automatically.
4. At launch: blog_public 1, remove the staging mu-plugin, submit `sitemap_index.xml` in Search Console. Not before.

## Critique
1. The three 70-character event titles are long because the names are long. If PL gives short names (the Series "Short name"), the title could use them. That is PL's choice in 5.2.
2. The Dubai office's `areaServed` (AE, SA, IQ) comes from the office "covers" field. It's correct for the data we have, but PL should confirm it.
