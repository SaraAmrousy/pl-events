# Step 4.1 — `pl-events-core` plugin: Step Summary (Thread 6, 5 Nov 2026)

**Status: ✅ DONE — 5 Nov 2026.** Deployed to staging with one command (`deploy/deploy.sh plugin wp-content/plugins/pl-events-core` → `active: pl-events-core 0.1.0`) and seeded with `deploy/seed-staging.sh` (47 created + company, 7 photos, 0 errors). **Checked as a visitor on `saraamrousy.net/pl`:** `/pl-check/` shows "15 congresses. 7 cities. 5 countries. One name." and "Next congress · DEOSS 2027 · Tunis · in 85 days"; `/events/` shows "Calendar · 15 events since Jun 2024", tabs All · 15 / Upcoming · 2 / Past · 13, Country filter = Egypt, UAE, Saudi Arabia, Tunisia, Iraq; `/events/esoirs-2026/` 200, `/events/deoss-atmo-2027/ics/` `text/calendar`, unknown event 404, `/?rest_route=/pl/v1/data` JSON. The editor test passed in the sandbox (below); Sara can repeat it on staging (PL Website › ＋ Add event). Staging still runs plain Hello Elementor — the look comes in 4.2.

## Structure (Step 2.2 §8 — binding, followed)
| Principle | Where |
|---|---|
| One module per content type | `modules/events`, `offices`, `societies`, `sponsors`, `services`, `jobs`, `memberships`, `company` — each `module.php` + `fields.php` |
| One field registry | `fields.php` arrays → `PL_Fields` builds the SCF form (registered in PHP), validation, data output and importer |
| One data layer | `PL_Data` — `events()`, `stats()`, `company()`, `offices()`… and `all()` = shape of `data/pl-data.json` (verified field-by-field against the seed) |
| Shared services | `PL_Dates`, `PL_Geo`, `PL_Phones`, `PL_Cache` |
| Overridable templates | `templates/*.php` ← `{theme}/pl-events/*.php` |
| Attributes, hooks | per 3.4b §5; filters/actions listed in the plugin README |
| Stable keys + `db_version` | meta key `pl_{name}`, field key `field_pl_{type}_{name}`; `PL_Plugin::upgrade_{n}()` |
| Data survives the plugin | standard posts/terms/meta in SCF format; deactivate → reactivate kept 47/47 items (tested). No uninstall routine deletes anything. The data layer reads meta directly, so the site keeps rendering even if SCF is off. |

## Content model as built
- **`event`** CPT: no archive, `/events/{slug}/` (shares the `events` slug with the Elementor page — tested: page 200, singles 200, unknown slug 404). Fields = 2.2 §2 + **Edition** (it is in the seed data). Slug fixed at first publish from series + year (or the name).
- Lists: **Specialties, Series** (editor adds; case-insensitive duplicate guard) · **Event types** (admin; switches *Online* and *Counts as a congress*; 8 defaults seeded) · **Job types** (admin). Series have an optional **Short name** ("BRC") — replaces the prototype's hard-coded "Bridge Retina → BRC"; added to the seed as `"series"` (one line in `data/pl-data.json`).
- **Services:** one item per *service group* with its list of services (the JSON shape). Only an admin can add or delete a group; the PL Editor edits text, photo and items. *(Replaces 2.2 §3 "Service = Name + Group taxonomy": the prototype, the JSON and the zigzag layout are all per group, so a separate taxonomy would only duplicate it.)*
- **Company** = SCF Options Page (PL Website › Company): headline (text + emphasis), signature, founding year (→ "{N}+ years" computed), figures, portfolio PDF (size computed) or link, showreel video/poster/stills + Sample switch, social links (name detected from the address), Show "Sample" tags, Sample items left (read-only).
- Every item: **Show on site**, **Order** (page attributes), **Sample** (admin-only — hidden from the PL Editor, tested).
- **PL Editor role:** sees Dashboard · PL Website · Media · Profile only (tested); can add events, offices, societies, sponsors, jobs, memberships, Specialties, Series; cannot add service groups, event/job types, pages, plugins, users.
- **Countries/cities:** country dropdown with flags (130 countries: Europe, Africa, Middle East); city = text with suggestions (cities already used + the bundled list for that country). No coordinates anywhere; no match → country centre + admin "Unmatched cities" list. Data: mledoze/countries (ODbL), GeoNames cities ≥ 15k (CC BY 4.0) via the `cities15000` npm copy (geonames.org is blocked from the sandbox), flag-icons (MIT). Rebuild: `tools/build-geo.py`.
- **Phones:** repeater label · code (flag + dial) · number · On WhatsApp → `+20 102 555 9003`, `tel:`, `wa.me` (tested).
- **What blocks Publish (D13):** end before start · Registration Open without a link · a link that is `#` / not `https://` · no country/city on a non-online type · membership without proof link. Tested in the browser as the PL Editor.
- **Gentle reminders** (yellow, never block): upcoming without website/registration · city not found · Pin to Home on more than one event.
- **Admin list (§6):** Start date · Country (flag) · City (⚠ if unmatched) · Status badge · Registration · Show on site; newest first; filters by year and status.

## Status (D1, D9)
Computed on every call in the **event country's time zone** (online → Cairo); never stored. Tested: Dubai event turns **Live at Dubai midnight** while UTC is still the day before, "day 1 of 3", Past the day after the end. Hourly WP-Cron check: if any status changed → cache version bump + page-cache purge (SG Optimizer, LiteSpeed, WP Rocket, W3TC, WP Super Cache + `pl_purge_page_cache`). Shortcode HTML is cached per hour, and `pl-events.js` re-checks status on screen (badge text, removes Register/Add to calendar when past).

## Shortcodes (3.4b §5) — all server-rendered; no-JS = finished section
`[pl_upcoming layout tabs default limit type specialty country year title]` · `[pl_calendar default search filters views view heading intro]` (GET form; URL state `?tab=&q=&specialty=&country=&yr=&type=&view=` — `yr` because `year` is reserved by WordPress) · `[pl_story layout="sticky explore" aspect]` (chapters carry the camera viewBox for the theme's story.js; explorer chips are links) · `[pl_stats field | layout="line|sponsor_line|number"]` · `[pl_company field="hero_headline|tagline|portfolio_url|portfolio_size|portfolio_link|years|founding_year"]` · `[pl_company_figures layout="inline|big"]` · `[pl_next_event layout="strip|card"]` (countdown; hours in the last 48 h; "Happening now") · `[pl_logos type="society|sponsor|award"]` · `[pl_services layout="numbered|zigzag"]` · `[pl_offices layout="default|compact|split" hide_on="contact"]` · `[pl_careers]` · `[pl_live_ribbon]`. Event page template (poster, facts, actions, mini map with arc from the head office, editions, contact, more upcoming) + **`.ics`** at `/events/{slug}/ics/`. **No `[pl_help]`, no saved-list, no `[pl_hero_map]`** (tested: `[pl_help]` prints nothing).

## Importer
PL Website › Overview › Import from JSON (file upload or the bundled seed), `wp pl import [--overwrite]`, `POST /pl/v1/import`. Existing items (stable key `_pl_key`) are **skipped** unless Overwrite. Media (service photos, showreel stills) sideloaded once and re-used. Result on the seed: 47 items + company + 7 photos, 0 errors. `GET /pl/v1/data` exports the live data in the JSON shape.

## Tests run (evidence in the session)
- `php -l` on every PHP file ✅ · WP debug log empty ✅ · `node --check` on the JS ✅
- `tests/test-data-layer.php`: **ALL PASSED** — shape, every event field = seed, offices/phones/emails, services, company, social labels, time zones, New Alamein matched, Live/Past at local midnight, 2 upcoming today, no past Register, hero event, date texts, BRC label, 15 / 7 / 5, every shortcode renders, attribute filtering.
- Browser (Playwright, sandbox, prototype CSS loaded for comparison): Events / About / event page match the prototype at **1280 / 768 / 375**; Events page has no horizontal overflow; calendar filters, search, chips, Clear all, Grid/List, URL state, empty states and the mobile bottom sheet (Esc closes, fields move back) work with **no JS errors**; Home tabs switch without reload.
- **Editor test (as PL Editor):** form shows 22 fields with plain-English help; Publish blocked with "The end date is before the start date." + "Registration is "Open" — add the registration link first."; after fixing → published; the new Amman event appears in Events (16), Upcoming 3, **About H1 "From Alexandria to 6 countries."**, Jordan in the explorer — same result as the 3.4b DoD data test.
- Activate → deactivate → activate: 47/47 items kept; hourly check scheduled.

## For 4.2 (theme) — found while testing
1. About on mobile: `.story2 .steps` overflows to 845 px because the explorer chips don't shrink — **same in the prototype** (hidden there by `.pl{overflow:hidden}`). Fix in CSS: `.story2 .steps{min-width:0}`.
2. The plugin ships only functional CSS (`[hidden]` wins, sheet scroll lock, `.dim` pins). All look = `hello-pl-child` with the prototype class names (already used by the templates). The site wrapper needs the `pl` class (tokens are scoped to `.pl`).
3. Viewfinder float (`.vf-float`) and all motion are theme modules; the plugin emits `pl:swap` and `pl:frame` events for them.
4. Montserrat is self-hosted in the theme (the sandbox used Google Fonts only for the comparison).

## Critique (as if I didn't build it)
1. **Calendar renders every event twice** (grid + list) and filters by `hidden`. Fine for 15–200 events; past ~300 add server pagination.
2. **SCF taxonomy fields load options by AJAX**; on a slow host the Type/Specialty dropdowns take a beat to open.
3. **Unmatched-city fallback** pins on the country centre, which for big countries (Saudi Arabia) is far from the city — the admin list makes it visible, but spelling matters.
4. **Map projection covers lon −14…64, lat 5…52.** A congress in the UK, Scandinavia or South Africa would sit off the dotted map (same as the prototype's dot grid). Needs a bigger dot grid if PL goes there.
5. Seed photos are bundled in the plugin (1.2 MB) for the importer; drop `seed/assets` at launch.
6. Cache purge covers the common free cache plugins; PL's host cache is still unknown (2.2 open item).

## Open questions
None new for PL. For Sara: the 4.0 blocker (network secret scope) and the cron OK.
