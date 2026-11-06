# Step 6.1b — Code review, performance, full-page & responsive test (Thread 9, 6 Nov 2026)

**Status: ✅ no Blocker open.** Reviewed as an outsider. Tested on staging `saraamrousy.net/pl`:
- 11 pages × 9 sizes = **99 combinations**: 320×640, 375×740, 414×860, **667×375 landscape**, 768×1024, 1024×768, 1280×800, 1440×900, 1920×1080.
- Plus the 3 motion contexts and the interactions.

Every fix was tested; nothing approved was redesigned. Where a fix changed something visible, it was a broken state, and it's noted.

## Findings (problem → evidence → fix → effect)
| # | Sev. | Problem | Evidence | Fix | Effect (numbers) |
|---|---|---|---|---|---|
| R1 | **Major** | Keyboard focus **hidden under the sticky header + filter bar** on Events (WCAG 2.4.11) | Shift+Tab through the grid: **12/16** stops completely hidden at 375, 2/16 at 1280; `scroll-padding-top: auto` | `core.js setTop()` sets `scroll-padding-top` = sticky header + filter bar + 16 px (re-measured on resize) | **0/16** hidden at 375 and 1280 |
| R2 | **Major** | About explorer: side panel **cut off on phones** ("Jedda…", "Lym…", "…17th AT…") and at 320 "Plan yours here" / "Download our portfolio" off-screen. The chip row grew the panel to 825 px inside a 280 px column; a parent clipped it, so no page scroll showed. | Off-screen check at 320; pixel compare at 375 | `min-width:0` on `.story2 .steps/.step/.chips`, so the chips scroll inside their own row as designed | Full text wraps; 0 off-screen controls at all 9 sizes |
| R3 | **Major** | **Truth:** the About explorer said "Congresses 15" while Home says 13 (decision A) | Staging HTML | Same rule as the stats line. When the numbers differ, both are shown: "13 (of 15 events)", Saudi Arabia "0 (of 1 event)" | Home and About agree. ⚠️ *Visible wording change — tell me if you'd rather have only "Events N"* |
| R4 | Major | 320: Home card buttons **18 px outside the card** | Card 208 px, buttons 205 px + padding | ≤ 360 px only: smaller peek of the next card (28 px), card padding 16 px, buttons may shrink/wrap | Inside the card (both full width, your rule) |
| R5 | Major | 320/340: hero's 2nd button **cut 25 px** ("Request a propos…") | Button right edge 345 > 320 | ≤ 360 px only: the two hero buttons in one column | 276 ≤ 320 |
| R6 | Minor | 320/340: All · Upcoming · Past tabs on **2 lines** | Rows = 2 | ≤ 360: tab padding 11 px; ≤ 330: 8 px | 1 line on Home and Events |
| R7 | Minor | 768–1023: About offices: 2 buttons (346 px) in a 304 px column spilled 2 px past the left edge | `left:-2` | Row wraps inside its column | 0 off-screen |
| R8 | Minor | 320: "Download our portfolio (PDF, 4.7 MB)" one unbreakable line | Off-screen | ≤ 360: button text may wrap | Fits |
| R9 | Minor | Filters on phones: the chip read **"Country: AE"** instead of "UAE" when the filter was picked in the bottom sheet | Interaction test | Label looked up in the whole calendar, not only the form (fields move into the sheet on phones) | "Country: UAE" |
| R10 | Minor | Bottom sheet opened **without moving focus** into it | `focusIn:false` | Focus after the sheet is laid out (2× rAF) | `focusIn:true` |
| R11 | Minor | `/favicon.ico` 404 at the domain root on every page (Lighthouse Best Practices 96, "errors in console"). I don't touch the root site. | Lighthouse | Empty `<link rel="icon" href="data:,">` while no site icon is set. Nothing visible changes. | **Best Practices 96 → 100** on all pages |
| R12 | Minor | Viewfinder recalculated on **every** resize event | Code | rAF-throttled | — |
| R13 | Minor | Rank Math left 3 scheduled tasks from modules we switched off; 13 expired transients | `_get_cron_array()` | Unscheduled (Rank Math reschedules if a module is turned back on) + `delete_expired_transients` | 0 orphan hooks, 0 expired |
| R14 | Minor | `pl.css` carried prototype-only components the site never renders (favourites, modal/help panel, old services stack, old story layout, prototype form, mobile-preview frame) | Selector probe: 123 of 531 selectors never matched; 32 rules confirmed dead | `tools/build-theme-css.py` drops them + minifies (source stays readable) | **59.4 → 46.5 KB raw, 15.1 → 10.0 KB gzip (−34%)**. **22/22 pages pixel-identical** at 375 + 1280 |
| R15 | Minor | Input hygiene on event-form validation (`$_POST['acf']`, `$_GET['post']`) | PHPCS (WordPress standard) | `wp_unslash` + `map_deep(sanitize_text_field)` / `absint` | Same behaviour |

## Item 1 — equal-height cards (DS7, Sara's decision; replaces round-3 f)
- Rows stretch, the card is `height:100%`, the body flexes, and the actions are pinned to the bottom.
- Card titles are capped at **3 lines**. Longer titles show the series short label on the card only, e.g. "MOCAT 2027", "PHLEBOEGYPT 2026", "El Safwa 2026". The full name stays on the event page, in the List view, and in the link's accessible name. The accessible name starts with the visible text ("MOCAT 2027 — MOCAT 6 — Mesopotamia…", WCAG 2.5.3). Never "…". No series → full name.
  - "Short name" = the series' Short name field, or the series name when that's empty, as on the Register buttons.
- Venue capped at 2 lines; longer → dropped from the card (still on the event page).
- **Measured** (Home row + Events grid × All/Upcoming/Past × 1280/1024/768/375, sandbox and staging): every row **Δheight 0 px, Δbutton-y 0 px**, titles ≤ 3, meta ≤ 2, 0 ellipses.
  - At 1280 every full name fits in 3 lines, so no short labels.
  - At 1024/768/375 on Home the 3 long names switch.

## Code review — what was checked and passed
- **PHP** (PHPCS, WordPress security sniffs: escaping, nonces, input, SQL):
  - 31 → 5 findings, all false positives in context: template variables are local (templates are included inside a closure); `map.php` prints numbers through `sprintf('%.1f')`.
  - The only admin action (`admin_post_pl_import`) has a capability check + `check_admin_referer`.
  - REST `/pl/v1/import` is admin-only. `/pl/v1/data` is public and **excludes hidden items** (tested: a hidden event isn't in the feed).
- **debug.log at E_ALL** (WP_DEBUG) after the whole front end (24 URLs, .ics) + 14 admin screens: **0 lines**. Browser console: 0 errors (except the deliberate 404).
- **Queries:**
  - ~150/page warm in the sandbox. Most are Elementor reading settings that don't exist (21 just for "experiments"). Ours ≈ 20 (10 cached transients + menus).
  - **No N+1 in our code.**
  - Visitors don't hit PHP at all (LiteSpeed page cache).
- **PL_Cache:** an edit shows at once on Events and Home; the revert too.
- **WordPress:**
  - autoload 70 KB (biggest: core block CSS list 23 KB, rewrite rules 21 KB);
  - every active plugin is used;
  - **Akismet and Hello Dolly are installed but inactive.** Deleting them is your call (I didn't, it's destructive);
  - PL transients all have an expiry.
- **JS:**
  - every scroll/resize listener is `passive`;
  - scroll work is rAF-throttled;
  - no duplicate listeners;
  - motion modules load only where used (iris = Home, story = About, viewfinder = Home + Events). In Light/Reduced they download and stop at once.
- **Assets:**
  - Brotli on HTML/CSS/JS;
  - `max-age=31536000` on all static files;
  - one font preloaded (Montserrat latin, woff2, subset); italic on demand;
  - hero LCP image preloaded with srcset;
  - WebP crops at quality 72 with srcset/sizes per frame;
  - block-editor CSS no longer loaded.

## Full-page + responsive test (99 combinations) — result
- **No sideways scroll** in any of the 99.
- **No clipped controls, no text outside cards, no control off-screen** (after R2/R4–R8).
- **Images** fill their frames everywhere. One landscape "not loaded" is a poster 2,300 px to the right inside the Home row, so lazy loading is correctly waiting.
- **Tap targets ≥ 44 px** at every size ≤ 768 (the skip link is hidden until focused, by design).
- **Body text ≥ 16 px** on phones. Two fine-print lines are accepted: About "As stated by PL Events…" 15 px; Privacy map credits 14 px.
- The automatic check also flagged a "Results" heading as clipped at every size: it's hidden on purpose for screen readers (false positive).
- **Motion:** Full 1280 / Light 768 + 375 (touch) / Reduced 1280 × 5 pages: **0 elements stuck invisible** (20/20).
- **Interactions:**
  - **Menu:** opens, focus moves inside it, Esc closes it and focus returns to the button.
  - **Tabs** and **row arrows:** previous disabled at the start, next at the end, tab switch resets the row.
  - **Add to calendar:** Esc, outside click, arrow keys.
  - **WhatsApp / Google links** formed correctly.
  - **Forms:** an empty submit shows 3 messages, 3 `aria-invalid`, focus on the first field; prefill `?topic=sponsor&office=dubai` works.
  - **Filters + URL:** `?country=AE` survives a reload; search + empty state; bottom sheet (R9, R10).
  - **Keyboard** from top to bottom: ring on every stop, no trap.
- **Lighthouse mobile, all pages** (lab, through the sandbox proxy):

| Page | Perf | A11y | BP | SEO* | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|
| Home | 90–92 | 100 | 100 | 63* | 2.9 s | 0.001 | 10–70 ms |
| Events | 88 | 100 | 100 | 63* | 3.3–3.5 s | 0.013 | 0 |
| Events list | 96 | 100 | 100 | 63* | 2.0 s | 0.015 | 0 |
| About | 97 | 100 | 100 | 63* | 1.9 s | 0.003 | 0 |
| Services | 96 | 100 | 100 | 63* | 2.3 s | 0.001 | 20 ms |
| Careers | 92 | 100 | 100 | 63* | 2.5 s | 0.001 | 0 |
| Contact | 96 | 100 | 100 | 63* | 1.7–1.8 s | 0.02–0.035 | 0 |
| Privacy | 97 | 100 | 100 | 63* | 1.8 s | 0 | 0 |
| Event (upcoming) | 90–93 | 100 | 100 | 63* | 2.8–3.1 s | 0.001 | 0–50 ms |
| Event (past) | 97 | 100 | 100 | 63* | 1.9 s | 0.014 | 0 |

\* SEO = 63 **only** because of `noindex` (`is-crawlable`), which is required on staging. Every other SEO audit passes, so it's 100 without noindex. BP was 96 before R11.

- **axe-core: 0 violations**, 11 pages × 1280/375.
- LCP over 2.5 s on Home/Events/upcoming event in this lab only (TTFB 0.5–1.3 s via the sandbox proxy). This is still the 6.1 open item: **PageSpeed Insights from you**.

## Screenshots
- `docs/img/6.1b-fixes.jpg`: equal-height rows (Home 1280/1024, Events 1280) + before/after of R4, R5, R2.
- Contact sheets (11 pages × 9 sizes) were sent in the chat; they aren't kept in the repo (≈ 5 MB).

## Files
- **Plugin:**
  - `templates/card-event.php` (title/short label, venue span)
  - `templates/story.php` (R3)
  - `assets/js/pl-events.js` (card fit, R9, R10)
  - `modules/events/module.php` (R15)
- **Theme:**
  - `assets/css/src/wp.css` → `pl.css`
  - `assets/js/core.js` (R1), `assets/js/viewfinder.js` (R12)
  - `inc/setup.php` (R11)
- **Tools:** `tools/build-theme-css.py` (R14 prune + minify).
- **Staging:** deployed with the safe swap; PL cache + Elementor CSS + LiteSpeed purged; R13 done in the database.

## Questions for Sara
1. R3 wording: "Congresses 13 (of 15 events)", OK?
2. On phones the sticky header + filter bar take ~305 px of a 740 px screen while scrolling Events (approved design). Keep it, or let the filter bar scroll away on phones (with the Filters button staying reachable)?
3. Delete the inactive Akismet + Hello Dolly on staging?
4. A real favicon: use the PL monogram?
