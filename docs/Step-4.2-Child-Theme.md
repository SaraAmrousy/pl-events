# Step 4.2 — Child Theme & Global Styles: Step Summary (Thread 7, 5 Nov 2026)

**Status: ✅ DONE.** DoD "the theme matches the prototype's typography, colours and motion" — checked in the sandbox and live on `saraamrousy.net/pl` (evidence below). Theme: `wp-content/themes/hello-pl-child` (README there).

## What was built
- **CSS = the approved prototype, not a re-make.** `tools/build-theme-css.py` builds `assets/css/pl.css` from `reference/prototype/src/base.css` + `proto.css` (Prototype-controls rules stripped, asserted) + a small WordPress layer `assets/css/src/wp.css`. Class names are identical to the prototype and to the plugin templates. 34 KB.
- **Montserrat self-hosted** (variable 400–800 + 500 italic, latin, 52 KB, `font-display: swap`, main file preloaded). Elementor's Google Fonts and icon font switched off on the front end. Live check: **0 requests to Google**, `document.fonts.check('800 40px Montserrat')` = true.
- **Hello defaults off:** its reset/theme CSS, page title and meta description (Rank Math writes it). Body class `pl` carries the tokens.
- **Elementor Free Globals** = 3.2 tokens: Primary `#2C4894`, Secondary `#0A0D18`, Text `#0A0D18`, Accent `#2C4894` + 8 custom colours (paper, sand, navy, ink-raised, blue-on-ink, muted, live, open); fonts Montserrat 800 / 700 / 400 17/1.65 / 700 12 px uppercase; container 1200. Written into the Elementor Kit by `plt_sync_elementor_kit()` (creates the kit if Elementor hasn't yet) — done on staging (kit #61).
- **Motion modules (3.4b §5)** — `core.js`, `patterns.js`, `iris.js`, `viewfinder.js`, `story.js`; GSAP 3.15.1 + ScrollTrigger (**triggers only, no pin anywhere**) on every page; **Lenis only on Home** (Full context); iris / viewfinder / story load **only on pages that render `[pl_hero]` / `[pl_calendar]` / `[pl_story]`**. All self-hosted, deferred, in the footer.
- **Contexts** decided before first paint by a 1-line head script (`data-motion` = full / light / reduced, Save-Data counts as reduced). Start states never flash; if scripts never arrive the page is revealed after 2.5 s. Iris runs once per session.
- **Page crossfade:** `@view-transition { navigation: auto }`, off for reduced motion.
- **Fixes:** `.story2 .steps{min-width:0}` (from 4.1) + `.pl-story > .wrap{overflow-x:clip}` (viewfinder brackets widened the page by 10 px at 768–1023). Body overflow fixed for real pages (the prototype hid overflow on `.pl`, which would stop scrolling and sticky).

## Decisions
| # | Decision | Replaces / why |
|---|---|---|
| 4.2-1 | **`[pl_hero]`** (plugin) renders the whole Home hero: showreel (MP4 on desktop when uploaded, else stills) inside the iris, headline from Company, eyebrow and intro **computed** from offices + countries ("from Alexandria and Dubai to 5 countries"), figures + Next congress. | 3.4b §5 "hero container (video) + 3 shortcodes": the iris needs one nested DOM (`#hero > .media > #iris > #irisIn`) that Elementor Free cannot build, and the showreel is Company data. Elementor still places it. |
| 4.2-2 | **"Watch the film · 0:45" is not built** until PL sends a real film. | The prototype's film modal was a Sample placeholder; a button that opens "coming soon" is misleading (R10). Add a Company "Film" field when footage exists. |
| 4.2-3 | Plugin JS now fires a cancelable **`pl:story`** event (mode, chapter, cumulative cities, new cities, country, viewBox) and **`pl:swap`** with the changed element. The theme animates; without the theme the plugin still jumps to the right framing. | Lets motion stay in the theme while the plugin stays usable alone. |
| 4.2-4 | Viewfinder brackets are created by `viewfinder.js` (not in the plugin markup). | No stray brackets without motion / in Reduced. |

## Tests (evidence)
- `php -l` all theme PHP ✅ · `node --check` all modules ✅
- Sandbox, prototype side by side, reduced motion: Home hero, Events, About, event page match at **1280 / 768 / 375**; **horizontal overflow 0 on every page and width** (after the fixes above).
- Full motion (1280), Light (375), Reduced: contexts detected correctly; **Lenis on Home only**; viewfinder on Events (Full + Light, not Reduced); after scrolling every page, **0 elements left invisible** (cards, tiles, panels, titles, pills, photos, hero words); **0 JS errors**. Iris captured mid-open (aperture + PL mark) and at the end (full-bleed, headline sharp). About: map stays sticky (top 40 px) while the camera moves per chapter.
- **Bug found and fixed:** deferred scripts run while `readyState` is `interactive`, so `core.js` booted before the module files had registered — motion silently didn't run. It now waits for `DOMContentLoaded`. (The first "0 stuck" result was false for that reason; re-tested after the fix.)
- Staging (`/pl/events/`): theme active, tab font 700/14 px Montserrat = prototype, viewfinder on the first card, 0 Google requests, 0 JS errors, overflow 0 at 1280 and 375.

## Not in 4.2 (next steps)
- Header/footer (the staging pages still show Hello's plain header) → **4.3**.
- Page layouts in Elementor (About intro + photo, Services, Careers, Contact, closing bands) → **4.4**. The temporary `/pl-check/` page goes then.
- Real Safari / iOS / Android and 4G Lighthouse → 4.4 DoD.

## Critique
1. Ken Burns runs on stills only; with a real MP4 the video carries the motion.
2. Lenis + GSAP ≈ 95 KB on Home (deferred). Within budget, but measure in 4.4 Lighthouse.
3. The iris needs ≥ 1024 px + fine pointer; tablets in landscape get Light (fade) — by design (3.3).
4. `pl.css` is one file for all pages (34 KB, ~8 KB gzipped): simpler cache, slight waste on small pages.
