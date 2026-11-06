# Step 4.4 — UI polish + "Counts as a congress" decision (Thread 8, 6 Nov 2026)

**Status: ✅ live on staging.** Seven items from Sara's review on staging (screenshots, 5 Nov). **4.4 stays open** only for Sara's iPhone/Android check.

## Decisions recorded
| # | Decision | Before → after |
|---|---|---|
| D-T8-1 | **"Counts as a congress" = A**: only *Congress* and *Conference* count. *Symposium* and *Forum* are switched off on staging (PL Website › Event types). *Course, Wetlab, Workshop, Webinar* were already off. New types now start **off**. The seed defaults are changed too, so a reseed can't undo this. | **15 → 13 congresses.** Home stats line: "13 congresses". About yearly tallies: 2 → 6 → 11 → **13**. No longer counted: 4th Saudi Retina Symposium (SRS 2026) and 11th El Safwa Annual Forum. About H1 ("…to 5 countries") is unchanged, because it counts countries. This closes the 5.1 critique #1. |
| D-T8-2 | **Home "Our congresses." is one horizontal row, scrolled by hand only. It never autoplays** (Sara's decision). This **replaces 3.4a H3** (6-card grid). | 6 cards in a grid → all cards of the tab in one row. |

## What changed
1. **Selects (dropdowns)** — every `<select>` on the site (Events filters, Contact/Careers forms):
   - `appearance:none` plus our own chevron SVG: 1.5px stroke, the same as the field border, ink colour, 14px from the edge, `padding-right:44px`;
   - the chevron turns blue on focus; light colour on dark sections;
   - a Forminator rule was more specific and the sand-background rule used the `background` shorthand (which would wipe the chevron) — both fixed.
2. **List view** — a 16:9 thumbnail before the date:
   - it's the real poster, using the same crops/srcset as the cards; if there's no poster, the series' typographic poster in miniature;
   - size: 112px on desktop, 96px on tablet, 72px on phones;
   - the same thumbnails are on the event page's "editions with PL" rows.
   - Layout at each width:
     - 1280: thumb · date · name · place · badge · buttons.
     - 1024: the place column hides; the place shows under the name.
     - Phones: thumb on the left; date, name and place stacked; buttons under the name.
     - Longest name now wraps to at most 3 lines (4 on tablet). It was 6.
3. **Tabs** — one component everywhere (Home, Events, Grid/List): `inline-flex` + `align-items:center` + `height:40px` + `line-height:1` + `margin:0`. Measured: the text centre sits **0.0px** from the tab centre, Home and Events.
4. **Cards:**
   - an event with a real poster no longer shows the big series word (MEOM/DEOSS) or the edition/city text over the photo; only the badge and the date box stay (the event page poster too);
   - **date box:** "30 OCT – 1 NOV" over "2026", same size (13px/800) and case for both lines. Examples: "29–30 JAN / 2027", "26 JUN / 2025", "30 DEC – 2 JAN / 2026–27".
5. **Home row:**
   - `[pl_upcoming layout="row" limit="0"]`;
   - scroll-snap: 3 cards + a peek on desktop, 2 + a peek on tablet, 1 + a peek on phones;
   - ← → buttons (`aria-label` "Previous"/"Next", `aria-controls`): disabled at either end, each click moves one card;
   - swipe and trackpad work natively;
   - Tab through the cards and the row follows the focus;
   - the All/Upcoming/Past tabs filter the row and jump back to the first card (instantly, no glide);
   - viewfinder brackets hold the card nearest the centre (Full only);
   - Reduced motion: the buttons jump, no smooth scroll;
   - no JS: a plain horizontally scrollable row; the buttons' space is reserved, so nothing jumps.
6. **CLS fix found on the way:** Home on desktop (Full) had **CLS 0.536**. Cause: my hero fix removed the iris's inline geometry at the end of the opening, so the box jumped. The circle now keeps the size that covers the hero and is re-measured on resize. **CLS 0.536 → 0.029** (staging 0.032). Hero re-checked: the photo and the circle cover the hero after opening and after resizing to 1920×1080, 1190×575, 1440×700, 768×1024 and 375×667.

## Tests (evidence)
- **Row** (sandbox + staging, Chromium; 1280 Full, 1280 Reduced, 375):
  - visible cards on load: "1.00 1.00 1.00 0.38" on desktop, "1.00 0.27" on phones;
  - Previous is disabled at the start, Next is disabled at the end;
  - Past tab: 13 cards, back to the start;
  - keyboard focus on the 6th card is fully visible;
  - viewfinder on in Full, off in Reduced and on phones;
  - CLS: 0.029–0.032 in Full desktop, 0.001–0.003 in Reduced, 0.000 on phones.
- **Selects:** Events and Contact both show `appearance:none`, `padding-right:44px` and the chevron.
- **Image fit** still holds: 222/222 checks in the sandbox, 84/84 on staging, no sideways scroll at 375/768/1280.
- **Data-layer tests:** all passed. Debug log clean.
- **Browsers:** only Chromium runs in this sandbox (installing Firefox/WebKit here isn't allowed). The chevron uses standard CSS (`appearance:none` with `-webkit-`/`-moz-` prefixes plus a background SVG), which Safari and Firefox support. **Please glance at Events filters in Safari** during the phone check.
- Screenshots (before = old code in the sandbox · after = sandbox · after = staging with PL's real posters): `docs/img/thread8-polish-375.jpg`, `docs/img/thread8-polish-1280.jpg`.

## Files
- Plugin:
  - `templates/upcoming.php` (row layout)
  - `templates/card-event.php` (no overlay on real posters, date box)
  - `templates/event-page.php` (poster overlay, editions thumbnails)
  - `templates/row-event.php` (thumbnail + column classes)
  - `includes/class-pl-template.php` (`date_box()`, `thumb()`)
  - `includes/services/class-pl-dates.php` (`tile()` format)
  - `includes/class-pl-taxonomies.php` (decision A defaults)
  - `assets/js/pl-events.js` (row buttons, ends, tab reset, focus)
- Theme:
  - `assets/css/src/wp.css` → `pl.css` (selects, tabs, date box, thumbnails, row)
  - `assets/js/viewfinder.js` (row viewfinder)
  - `assets/js/iris.js` (CLS fix)
  - `inc/motion.php` (viewfinder on Home)
- `tools/build-elementor.py` → `elementor/pl-home.json` (`layout="row"`).
- On staging:
  - plugin + theme deployed (safe swap);
  - on Home, only the `[pl_upcoming]` shortcode was replaced inside the page;
  - Symposium/Forum flags switched off;
  - PL cache and LiteSpeed purged.

## Critique
1. The row hides its scrollbar. On desktop with a mouse (no trackpad), the buttons are the only cue. They sit next to the tabs, which is clear but small. If PL's editor finds them hard to spot, I can add a thin scrollbar.
2. Long event names (MOCAT 6, PHLEBOEGYPT) still take 3 lines in the list. A short name per event (Series "Short name") would help; that's PL's call in 5.2.

---

# Round 3 — Event card clean-up + logo (6 Nov 2026)

## What changed (card on Home row + Events grid; same actions in List view and on the event page)
| Item | Now |
|---|---|
| a. Register button | Text **"Register now"**. `aria-label` "Register now for {Series} {Year}" (e.g. "Register now for DEOSS 2027"), so screen readers can tell the cards apart. The "Website" link also gets "Website: DEOSS 2027". |
| b. Buttons | "Register now" (primary blue) and "Website" (outline) side by side. If they don't fit, each takes the full card width. They fit on one line at 375 after tightening the padding; checked. Past events: Website only. |
| c. WhatsApp | The brand mark: a #25D366 circle with the white glyph (Simple Icons, CC0), 40px, `aria-label="Share on WhatsApp"`. On the event page: "Share on WhatsApp" button with the green mark. |
| d. Calendar | The old icon is gone. A text button **"Add to calendar ▾"** opens Google Calendar (`render?action=TEMPLATE`, new tab) · Apple Calendar (.ics) · Outlook (`outlook.live.com/calendar/0/action/compose…`, new tab). Upcoming/live only. It's a `<details>` element (works without JS); JS adds Esc (focus returns to the button), click-outside, one menu open at a time, and arrow keys. On cards it opens **upward**, because the Home row clips anything below. On the event page it opens downward. |
| .ics | `Content-Type: text/calendar`, `Content-Disposition: inline; filename="deoss-2027.ics"` (series + year). `inline` lets Safari on iOS/macOS hand the file straight to Calendar; other browsers save it under a clear name. LiteSpeed no longer caches it. |
| e. Meta line | Flag · "City, Country · Venue". The date is only in the date box. |
| f. Bottom | ~~Superseded by round 4 (equal heights).~~ Actions are always the last block of the card, 8px under the text, the same in every card. **Trade-off (decided):** names run 1 to 5 lines and venues 2 to 4, so a row can't have both equal-height cards and a constant gap. Cards now take their own height, with no empty space inside. Before, the gap above the buttons ranged from 8px to 150px. |
| g. Elsewhere | The event page and List view use the same renderer (`PL_Template::actions()`). The List row puts its actions on two lines in a 232px column, so the name column stays ≥ 265px at 1280. |

## Logo
- The wordmark is the monogram + **EVENTS** (800, letter-spacing .06em), optically centred on the mark, in the header, mobile menu and the hero intro logo.
- The footer already uses PL's official logo image (monogram over EVENTS), so it's unchanged.
- The accessible name "PL Events — Home" and the schema logo are unchanged.

## Tests
- Sandbox + staging, Chromium 1280 / 375 (Firefox/WebKit can't run in this sandbox):
  - "Register now" + aria-label ✓;
  - the two buttons on one line ✓;
  - WhatsApp `rgb(37, 211, 102)` ✓;
  - no year in the meta line (0/15) ✓;
  - no calendar on past events (0) ✓;
  - buttons 21px from the bottom on every card ✓;
  - the menu opens inside the card ✓, Esc closes it and focus goes back to the summary ✓, a click outside closes it ✓, Enter goes to "Google Calendar" and ↓ to "Apple Calendar" ✓;
  - Google link: `https://calendar.google.com/calendar/render?action=TEMPLATE…`, `target=_blank` ✓.
- `.ics` on staging: 200, `text/calendar`, `deoss-2027.ics`, `X-Litespeed-Cache-Control: no-cache` ✓.
- **Not verifiable from the sandbox:** the network here blocks google.com and outlook.live.com, so the links are checked by format, not by a live click. **Sara: one click on each, from staging, on a phone and a laptop.** If either misbehaves, I'll remove the calendar from the card and keep it on the event page only (your rule).
- List view: name column 265/289/219/251px at 1280/1024/768/375, no sideways scroll. Event page: no sideways scroll at 375.
- Image fit 222/222. Data-layer tests passed.
- Screenshots: `docs/img/card-cleanup-375.jpg`, `docs/img/card-cleanup-1280.jpg`.

## Files
- Plugin:
  - `includes/class-pl-template.php` (`actions()`, `wa_icon()`)
  - `includes/class-pl-data.php` (`outlook_url`, `ics_name`, `register_aria`, `short_label`)
  - `includes/class-pl-event-page.php` (.ics headers + no-cache)
  - `templates/card-event.php`, `templates/row-event.php`, `templates/event-page.php`, `templates/hero.php` (wordmark)
  - `assets/js/pl-events.js` (calendar menus)
- Theme: `inc/chrome.php` (wordmark), `assets/css/src/wp.css` → `pl.css`.
- Staging: plugin + theme deployed. PL cache, Elementor CSS cache and LiteSpeed purged. Checked that staging serves the new markup (your card screenshot was taken before the previous deploy).


---

# Round 4 — Equal-height cards (6 Nov 2026, Sara's decision — DS7 restored)
**Replaces round 3 (f).**
- All cards in a row are the same height: Home row and Events grid (`stretch`, `height:100%`, flex column).
- Actions are pinned to the bottom (`margin-top:auto`) and sit on the same y in neighbouring cards.

**No big gaps from one long name:**
- **Card titles max 3 lines.** If longer, the card shows the series short label + year ("MOCAT 2027", "PHLEBOEGYPT 2026", "El Safwa 2026").
- The full name stays on the event page, in the List view, and in the link's aria-label, which starts with the visible text (WCAG 2.5.3). No "…".
- "Short name" = the series' Short name, or the series name when empty (same as the Register buttons). No series → full name.
- **Venue max 2 lines.** If longer, it's dropped from the card (kept on the event page).
- Done by `pl-events.js` after layout and fonts, and on resize/tab change. Without JS the full name shows.

**Measured** (1280/1024/768/375 × Home/Events × All/Upcoming/Past, sandbox + staging):
- Δheight 0 px and Δbutton-y 0 px in every row; titles ≤ 3, meta ≤ 2.
- At 1280 every full name fits; the short labels appear at 1024/768/375.

Details and the rest of the review: `docs/Step-6.1b-Code-Review-Responsive.md`.
