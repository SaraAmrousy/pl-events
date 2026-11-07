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

## Questions for Sara → answered 6 Nov 2026 (round 2 below)


---

# Round 2 — Sara's answers (6 Nov 2026)
| # | Decision | Done | Evidence |
|---|---|---|---|
| 1 | R3 wording: two separate numbers, each with its label, no brackets | About explorer `dl`: **Congresses 13 · Events 15** as two rows in the panel's existing style; every country the same (Saudi Arabia: Congresses 0 · Events 1) | Staging HTML + screenshot |
| 2 | Phones (≤ 767 px): only one line stays fixed | New `#ebar-m`: **Filters** (count only when > 0, e.g. "Filters (2)") · **"N events"** · Grid/List (icons, aria-labels "Grid view"/"List view"). Tabs + search are part of the page and scroll away; the full filters stay in the bottom sheet. Without JS the Grid/List buttons still work (`form="ebar"`). `scroll-padding-top` follows the new bar. Tablet/desktop unchanged. | Sticky area at 320/375/414: **289 → 124 px** (header 65 + bar 59, target ≤ 130). R1: 0/16 focus stops hidden (375 and 1280). Bar 768 = 160 px, 1024/1280 = 183 px, still sticky, phone bar `display:none` there. |
| 3 | Delete Akismet + Hello Dolly | Deleted on staging (both were inactive) | Plugin folder list |
| 4 | Favicon: PL monogram, white on #2C4894, small radius | Monogram traced to a vector (potrace) → `assets/img/icon/favicon.svg` + PNG 32/180/192/512. The 512 is the **WordPress Site Icon** (`plt_provision_site_icon()`, using WordPress' own icon sizes), so WordPress prints 32/192 + apple-touch 180 + 270, all under `/pl`. SVG link added for browsers that use it. `data:,` (R11) removed. Nothing at the domain root. | Head tags on staging; `favicon.svg` 200 `image/svg+xml` |

## Found and fixed while doing round 2
| # | Sev. | Problem | Fix | Evidence |
|---|---|---|---|---|
| R16 | **Blocker** | **Links inside the Events results didn't open on click** (Grid and List): event titles, Register now, Website, WhatsApp. `#results` carries `data-view` (grid/list state), and the calendar's click handler matched it as the Grid/List button and cancelled the click. Older than this round; my earlier checks tested link *addresses*, not *clicks*. | The handler and the toggle update now only match `button[data-view]` | Real clicks on Events Grid, Events List and Home: title navigates ✓, Register now / Website / WhatsApp open a new tab ✓, Add to calendar opens ✓. axe found the same root cause (`aria-pressed` on `#results`) → 0 violations after the fix. |
| R17 | Major | The deploy's safe swap left an old plugin copy (`pl-events-core.old-…`) when cPanel's delete failed silently, so wp-admin listed the plugin twice | Removed. `deploy.sh` now checks the delete, retries 3×, and warns loudly if it's still there | Next deploys: no `.old-` folder |

## Still open — a question for Sara
- **Tablet (768–1023 px) has no way to filter by specialty/country/year/type.** The filter row is hidden below 1024 (prototype rule), and the "Filters" button was phones-only (also prototype). Tabs and search work. You said tablet must not change, so I left it. **Recommendation:** show the phone bar's Filters button on tablet too (the bottom sheet already works up to 1023 px).

## Files (round 2)
- Plugin:
  - `templates/story.php` (explorer rows)
  - `templates/calendar.php` (phone bar)
  - `assets/js/pl-events.js` (phone bar count/filters; R16)
- Theme:
  - `assets/css/src/wp.css` → `pl.css`
  - `assets/js/core.js` (scroll-padding follows the phone bar)
  - `inc/setup.php` (SVG icon link; `data:,` removed)
  - `inc/provision.php` (`plt_provision_site_icon`)
  - `assets/img/icon/*`
- `deploy/deploy.sh` (verified old-copy delete).
- Screenshot: `docs/img/6.1b-round2.jpg`.

---

# Round 3 — 7 Nov 2026
| # | Request | Done | Evidence |
|---|---|---|---|
| 1 | Tablet filters (768–1023 px) | The phone bar `#ebar-m` (Filters + count when > 0 · "N events" · Grid/List) now shows up to **1023 px** and opens the same bottom sheet. The big filter row stays hidden below 1024. Desktop (≥ 1024) unchanged. | Staging: sticky area **124 px** at 768 and 1023 (target ≤ 130); sheet opens; R1 **0/16** focus stops hidden. 1024/1280: masthead + full bar as before, phone bar `display:none`, R1 0/16. |
| 2 | Click sweep | `tools/click-sweep.js`: real Playwright clicks on every interactive element of every page at 375 / 768 / 1280 × Full / Reduced motion. Outside hosts are stubbed (nothing reaches Google/Outlook/WhatsApp); forms are only submitted empty or invalid. Exit code 1 on any ❌. Re-run after each deploy: `node tools/click-sweep.js https://saraamrousy.net/pl docs/click-sweep-report.md` | `docs/click-sweep-report.md`: **330/330 ✅** on staging (and on the sandbox) |
| 3 | Newsletter (Zoho + Mailchimp) | See `docs/Step-4.6-Newsletter.md`: own adapter (Forminator has no Zoho, no queue, no consent record), Settings › PL Newsletter, footer + event + Contact forms, double opt-in, local record + cron retry, encrypted write-only keys, staging tag `pl-staging-test` | Mock tests all ✓; staging end-to-end at 375 → queued + success message |

## Found by the click sweep and fixed
| # | Sev. | Problem | Fix |
|---|---|---|---|
| R18 | Major | "Add to calendar" menus opened **upward** and, near the top of the Events list, went under the sticky bar, so Google/Apple/Outlook couldn't be clicked | Menus open downward; upward only in the Home rail (where there's room) |
| R19 | Major | With Full motion, an open calendar menu went **under the next card/row** (the reveal animation creates a stacking context) | The row/card holding an open menu is lifted (`:has(details.calm[open])`, z-index 25). Same look, menus now on top |
| R20 | Minor | Newsletter records: email read back as "Private: …" (WordPress' private-title prefix) | Raw title read; caught before any real provider |

## Files (round 3)
- Theme:
  - `assets/css/src/wp.css` → `pl.css` (tablet bar, menus, newsletter styles)
  - `inc/chrome.php` (footer form)
  - `inc/forms.php` (Contact checkbox)
- Plugin:
  - newsletter classes + `templates/newsletter-form.php`
  - `templates/event-page.php`
  - `assets/js/pl-events.js`
  - `includes/class-pl-plugin.php`
- `tools/click-sweep.js`, `docs/click-sweep-report.md`
- `deploy/mu/pl-staging.php` (`PL_NL_TEST_ONLY`).
- Staging: plugin + theme + mu-plugin deployed with the safe swap (no `.old-` left); PL cache + Elementor CSS + LiteSpeed purged.

---

# Round 4 — 7 Nov 2026
| # | Request | Done | Evidence |
|---|---|---|---|
| 1 | Newsletter: PL uses **both** Zoho and Mailchimp | Redesigned (`docs/Step-4.6-Newsletter.md`): **one** confirmation email from the site, a signed link valid 7 days → on confirm, added to **both** as subscribed with tags (Mailchimp + Email marketing permission; Zoho with no second confirmation, which works with a list whose signup form is off, per Zoho's docs) · consent record includes the confirmation time · unsubscribe sync by webhooks (Mailchimp unsubscribe/cleaned, Zoho unsubscribe/hard bounce; secret in the URL) + the site's own unsubscribe link + an admin row action · "Both" warning only while a webhook isn't connected · CSV export (admins) | `tools/nl-mock/nltest.py` **44/44** on mocks; click sweep on the sandbox with mocks **384/384** (sign up → one email → confirm → both → unsubscribe → both) |
| 2 | Theme thumbnail | `hello-pl-child/screenshot.png` 1200×900: paper background, the PL monogram (vector, from the favicon trace) + **EVENTS** 800, "Hello PL Child — PL Events theme" in Montserrat, a #2C4894 rule and side band. Source: `tools/theme-screenshot.html` | Staging Appearance › Themes shows it (1200×900), checked by `tools/admin-check.js` |
| 3 | Staging user `PL@Admin` | Created inside WordPress (`wp_insert_user`, which sends **no** "new user" email): Administrator, display name "PL Admin", email `pl-staging+pladmin@saraamrousy.net`, random 40-character password, never printed or saved anywhere. Your password unchanged. | Users on staging: `claude-deploy`, `PL@Admin`, `sara` |
| 4 | Sign-in at `/plevents/` + rate limit | Own module `PL_Login` in pl-events-core (comparison below). `/wp-admin`, `/wp-login.php`, `/login`, `/admin` → **404** when signed out. 5 wrong passwords → 15-minute pause per IP, no CAPTCHA, a message that blames nobody | `tools/admin-check.js` on staging **17/17** + rate-limit run **11/11** (see the list below) |
| 5 | Click sweep again | + the sign-in address, 404s, tampered newsletter links; newsletter flow on the mocks | Staging **372/372 ✅** (`docs/click-sweep-report.md`) · sandbox + mocks **384/384 ✅** (`docs/click-sweep-report-sandbox.md`) |

## Login: WPS Hide Login vs our own code
| | WPS Hide Login (free) | Own code in pl-events-core (chosen) |
|---|---|---|
| How | Serves WordPress' own `wp-login.php` at a new slug | **The same technique** (~150 lines, `includes/class-pl-login.php`) |
| `/wp-admin` signed out | Redirects to a page you pick (a 302, then a 404) | A real **404** |
| Rate limit | Not included → a second plugin (e.g. Limit Login Attempts) | In the same module: 5 wrong → 15 min per IP |
| Updates / attack surface | One more third-party plugin to keep updated | Nothing extra; versioned in our repo; tested by our tools |
| If it's switched off | Normal wp-login.php comes back | Same (fail-open; nobody gets locked out of the site) |
| Live | Install + configure again | Comes with the plugin deploy; same slug (or `PL_LOGIN_SLUG`) |

**Verdict:** our code. It's equally safe, since the method is the same and WordPress' own login code runs unchanged. It's simpler for PL: one plugin fewer, and the rate limit sits in the same place.

**Tested on staging** (a temporary QA administrator, deleted afterwards):
- **Sign-in and redirects:** sign in at `/plevents/` → dashboard; signed in, `/wp-admin/` works.
- **Log out:** the logout link goes to `/plevents/?action=logout`, then `/plevents/?loggedout=true`; after that, `/wp-admin/` → 404.
- **Password reset:** the email link is `/pl/plevents/?login=…&key=…&action=rp` (built without sending).
- **REST API + application password:** 200, tested from the server itself, because the agent proxy rewrites Authorization headers from the sandbox.
- **Elementor editor:** opens Home and the preview loads.
- **Forminator:** a real Contact submission → `success: true` (the email went to Sara through the safety net; the entry was deleted).
- **admin-ajax / wp-cron:** admin-ajax answers logged out; `wp-cron.php` 200, 0 cron events overdue.
- **Deploy:** `deploy.sh` plugin + theme ran through the change (its health check now reads `/plevents/`).
- **Rate limit:** the right password after 5 wrong ones → "Sign-in is paused for 15 minutes…", then cleared.
- In the sandbox too: the reset email arrives with a `/plevents/` link; application password REST → 200.

## Files (round 4)
- **Plugin `pl-events-core`:**
  - `includes/class-pl-login.php` (new)
  - `class-pl-nl-links.php` (new), `class-pl-nl-hooks.php` (new)
  - newsletter classes updated
  - `class-pl-plugin.php`
- **Theme `hello-pl-child`:** `screenshot.png`, `assets/css/src/wp.css` → `pl.css`.
- **Deploy:** `deploy/lib.sh` (health check).
- **Tools:**
  - `tools/admin-check.js` (new)
  - `tools/click-sweep.js`
  - `tools/nl-mock/` (new)
  - `tools/theme-screenshot.html`
- **Docs:**
  - `docs/Step-4.6-Newsletter.md` (rewritten)
  - `docs/Step-6.3-Launch-Checklist.md` (L6, accounts, sign-in address)
  - `docs/Editor-Guide.md` (sign-in address, newsletter)
  - `docs/click-sweep-report*.md`

---

# Round 5 — 7 Nov 2026
| # | Request | Done | Evidence |
|---|---|---|---|
| 1 | Confirm / unsubscribe button pages | **Decision: they stay** (no one-click from the email). Recorded in Step 4.6 §1 | — |
| 2 | Where the rate-limit IP comes from | It was already `REMOTE_ADDR` only; now one shared service, `PL_Client_IP`, is used by both the login limit and the newsletter limit. `X-Forwarded-For` is read **only** when `REMOTE_ADDR` is a proxy listed in `PL_TRUSTED_PROXIES` (wp-config, IPs/CIDR, IPv4+IPv6); then it's read from the right, skipping our proxies, so forged left-hand entries are never taken. `X-Real-IP`, `CF-Connecting-IP`, `True-Client-IP` and `Forwarded` are **never** read. | Unit checks 13/13 in the sandbox (no proxies: all headers ignored; trusted proxy: right-most non-trusted hop; forged left entry ignored; untrusted `REMOTE_ADDR` → headers ignored; garbage hop → `REMOTE_ADDR`; IPv6 range; /8 edge). `tools/admin-check.js --lock` now changes `X-Forwarded-For` / `X-Real-IP` / `CF-Connecting-IP` / `True-Client-IP` / `Forwarded` on **every** try → still paused after 5: sandbox 11/11, **staging 11/11**, one lock key = one IP. Staging admin check 17/17. |
| 3 | Real-account newsletter test | **Not run yet:** staging has no keys, no destination, 0 records and no webhook calls. Prepared: masked webhook samples (last 3 per provider) so the real Zoho shape can be checked and the parser tightened; checklist in Step 4.6 §6 | Mocks 44/44 after the change |

**Staging finding:** `REMOTE_ADDR` on staging is the public client IP and **no** forwarding headers arrive, so there's no reverse proxy in front. `PL_TRUSTED_PROXIES` stays unset. On live: check the same thing on PL's host (6.3); set the constant only if the host sits behind a proxy or CDN.

**Files:**
- `includes/class-pl-client-ip.php` (new)
- `class-pl-login.php`, `class-pl-newsletter.php` (use it)
- `class-pl-nl-hooks.php` (masked samples)
- `tools/admin-check.js`
- docs
