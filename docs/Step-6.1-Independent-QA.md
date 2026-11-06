# Step 6.1 — Independent QA: Step Summary (Thread 9, 6 Nov 2026)

**Status: ✅ no Blocker open.** One Blocker and five Majors were found; all are fixed and verified on staging. **One Major is still to verify on a real network**: mobile LCP from Google PageSpeed Insights, which this sandbox can't reach. Skill: `pl-content-qa`. Reviewed as if I hadn't built it, on `saraamrousy.net/pl`: 24 URLs (7 pages, 15 events, list view, a 404) × 375 / 768 / 1280.

## Findings
| # | Severity | Finding (evidence) | Fix | Status |
|---|---|---|---|---|
| B1 | **Blocker** | **CTA links went to 404.** "Request a proposal", "Sponsor a congress", "All services", "Explore the record", "See sponsorship" on Home / Events / About / Services had `href="[pl_url to="contact"…]"`. Double quotes inside the attribute → WordPress left the shortcode unprocessed (crawl: `404 /pl/services/[pl_url%20to=`). | Single quotes inside `[pl_url]` in `tools/build-elementor.py`. On staging only those 10 shortcodes were replaced in the Elementor data (decode → fix → encode). | ✅ 0 raw `[pl_url` on all pages; contact links resolve |
| M1 | Major | **Mobile LCP over 2.5 s.** Events 4.2 s (first poster `loading=lazy`: 2.5 s load delay), event page 3.6 s, Home 2.9 s. | First visible card's poster eager + `fetchpriority=high`; correct card `sizes`; crop quality 72 (posters ~35% lighter); block-editor CSS no longer loaded on the front end. | **Partly:** Events 3.2 s · event page 2.9–3.1 s · Home 2.7 s · Services 2.6 s · About 2.0 s · List 2.1 s. TTFB measured from here is 0.5–1.3 s through the sandbox proxy, so these lab numbers are pessimistic. **Open: Sara runs PageSpeed Insights (mobile) on /, /events/, /events/deoss-atmo-2027/** |
| M2 | Major | Posters set as the **featured image** had no srcset or crops: the URL was the "large" copy (`…-811x1024.webp`), which can't be matched to its attachment (lab: `width=1600` fallback, full file). | `PL_Images::id()` maps resized URLs back to the original. | ✅ 6/6 posters resolve; srcset served |
| M3 | Major | **Tap targets under 44 px** on phones: office phone/email links 23 px, footer Privacy 16 px, social links 21 px ("X" 9×21), WhatsApp 40 px, "Add to calendar" 40 px. | 44 px min-height/width on all of them. | ✅ Crawl at 375: only the skip link (hidden until focused, by design) |
| M4 | Major | **404 page had no H1** (Hello Elementor's 404 hides it because page titles are off). | `template-parts/404.php`: designed 404 with an H1, the "since June 2024" line, and See all events / Home / Contact. | ✅ HTTP 404 + one H1 |
| M5 | Major | The `.ics` was cached by LiteSpeed. | `litespeed_control_set_nocache` in the `.ics` response. | ✅ `X-Litespeed-Cache-Control: no-cache` |
| m1 | Minor | Motion scripts (gsap/ScrollTrigger + page module) download in Light/Reduced too, then stop. The visitor's preference can't be known on the server. | None. They load only on pages that use them (iris = Home, story = About, viewfinder = Home + Events). | Accepted |
| m2 | Minor | External links (society sites, registration links, social profiles) **can't be checked from this sandbox**: its network blocks every outside host. plevents.org was excluded from all checks on purpose. | Check them at launch: 6.3 runs a link check from a normal network. | Open → 6.3 |
| m3 | Minor | Google Calendar / Outlook buttons were checked by URL format only (same network block). | — | **Open: Sara, one click on each** |

## What passed (evidence)
- **Truth:**
  - no placeholder text, no "0 +", no `undefined`/`NaN`;
  - no past event shown as upcoming;
  - every date has its year;
  - one H1 per page (24/24, plus the 404);
  - 83 internal links, 0 broken.
- **Editor test (sandbox, PL Editor role, wp-admin form only):**
  - added "QA Test Congress 2026", Cairo, 10–12 Dec 2026, Congress, Ophthalmology, typing the dates by hand;
  - published, then appeared at once:
    - Events calendar card (date box "10–12 DEC / 2026", "Cairo, Egypt · venue");
    - first card in the Home row;
    - stats "14 congresses. 8 cities.";
    - "Next congress … in 34 days";
    - Cairo on the About map;
    - event page 200.
  - **Edit test:** Registration "Not open yet" → "Open" → "Register now" appeared with `aria-label="Register now for QA Test Congress 2026"`.
  - Test event and user deleted afterwards.
  - Note: the WordPress "Editor" role gets 403 on events. That's by design: PL staff use the **PL Editor** role (2.2 §6).
- **Accessibility:**
  - axe-core 4 (WCAG 2.0/2.1 A + AA): **0 violations** on 10 pages at 1280 (Full) and 375 (Reduced). Sanity check: an injected `<img>` without alt was caught;
  - Lighthouse accessibility 100 on 5 pages;
  - **Keyboard:** Tab through Home/Events/event page/Contact (29–71 stops each). The first stop is "Skip to main content", every stop has a visible focus ring, no trap, and focus never ends up off-screen (the Home row follows the focus).
- **Performance:**
  - CLS 0–0.015 everywhere;
  - TBT 0–170 ms (INP proxy, < 200 ms);
  - Lighthouse performance 87–96;
  - Home weight ~1.0 MB, list ~0.7 MB.
- **Motion:** Full (1280), Light (768 + 375, touch), Reduced (1280) on 5 pages: all content visible (0 elements stuck invisible); each page loads only its own module.
- **Responsive:** 375 / 768 / 1280, no horizontal overflow on any of the 24 URLs.
- **Copy:** dates as "29–30 Jan 2027" / "30 Oct – 1 Nov 2026" everywhere; naming consistent (PL Events, Register now, Website, Add to calendar, Share on WhatsApp).

## Files changed
- `tools/build-elementor.py` → `elementor/*.json`
- Theme: `template-parts/404.php` (new), `inc/perf.php`, `assets/css/src/wp.css` → `pl.css`
- Plugin: `includes/class-pl-images.php`, `includes/class-pl-event-page.php`, `templates/calendar.php`, `templates/card-event.php`
- QA scripts (not in the repo, scratchpad): crawl, axe, keyboard, motion, editor test.

## For Sara (5 minutes)
1. PageSpeed Insights → Mobile, for `https://saraamrousy.net/pl/`, `/pl/events/`, `/pl/events/deoss-atmo-2027/`. Send me the LCP numbers. The target is < 2.5 s; if they're over, I'll take the next step (hero/poster preload, a smaller first poster).
2. On a DEOSS 2027 card: Add to calendar → Google Calendar, then Outlook. Each should open a filled-in event.
