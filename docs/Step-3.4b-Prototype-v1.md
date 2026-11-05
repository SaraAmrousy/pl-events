# Step 3.4b — HTML Prototype v1: Step Summary (4–5 Nov 2026)

**Status: DONE — DoD met: Sara approved v1.7 on 5 Nov 2026 ("يلا بينا"); works with reduced motion; adding/removing an event in the JSON updates map, calendar, grid and stats with no other change.** Skill: `pl-design-motion`. Next: **Phase 4 in Claude Code (Thread 6: 4.0 Setup + 4.1 Plugin).**

**Prototype:** https://claude.ai/artifact/1CYF4Vfn6i1etX4UELAa8Z — multi-file artifact (`index.html` with all CSS/JS, `data/pl-data.json`, `assets/`, `vendor/`). Local copy in this repo: `reference/prototype/site/` (built) and `reference/prototype/src/` (sources). **Shared publicly by Sara** — the yellow Prototype controls are visible to anyone with the link; make a clean reveal copy without them before showing PL's owners.
**Data file:** `data/pl-data.json` (same content as the prototype's `data/pl-data.json`) — seeds WordPress in 4.1.

## 1. What it is
- **Pages:** Home, Events, About, Services (+ #sponsorship), Careers, Contact, Privacy (text TBC), and the **event page** (`#event-{id}`).
- **One JSON** (schema `pl-events-core/1`): company (figures, `hero_headline`, tagline, portfolio, showreel, social), offices (phones/emails as lists, hours, `covers`), 15 events (Step 2.2 fields), societies, sponsors, services (4 groups), jobs (empty), memberships (empty), geo (countries with time zone + centre, cities with lon/lat, credits). Keys starting `_` are build notes.
- **Computed, never typed:** status per event in the **country's local time**, date text with year, badges, Register button (only Upcoming/Live + Open + a real link), tabs + counts, "Next congress" strip, countries, cities, map pins/arcs/camera framing, story chapters (max 6), explorer panel, stats line, About H1 number, sponsor line, office suggestion.
- **Prototype controls** (not part of the site): date simulation, motion context override, Home headline options, Sample tags on/off, replay the iris, data test.

## 2. Checks run
- Headless Chrome, WebKit (Safari engine) and Firefox; desktop 1280/1440 + mobile 390; reduced motion; inside an iframe. No console errors; no horizontal overflow (except the intended chip scroller); About scrolls to the end everywhere.
- Live: on 31 Oct 2026 → ribbon, "Live now · 1" tab, Live band, "Happening now" strip, "day 2 of 3".
- **DoD data test:** +1 sample row (Jordan) → 16 events, 6 countries, About H1 = 6, Amman pin, Jordan in the Country filter; removing both Dubai events → 14 / 7 cities / 5 countries.
- Not done: real Safari / iOS / Android devices and throttled 4G Lighthouse → repeat on the staging build (4.4 / 6.1).

## 3. Review-round decisions (binding)
| # | Decision | Replaces / why |
|---|---|---|
| P1 | **Home: "How can we help?" + the closing section → one ink band, two columns** (societies: "Planning a *congress?*" → Request a proposal · sponsors: "Put your brand *in focus.*" + computed line → Sponsor a congress + See sponsorship). Home = 5 blocks + footer. | 3.1 Home §5–6. **`[pl_help]` removed from 4.1.** |
| P2 | **Lenis on Home only.** | Locked 7 (Home + About). |
| P3 | **About story = CSS-sticky map + natively scrolling year chapters + an explorer step**; an IntersectionObserver picks the step in focus. **No ScrollTrigger pin anywhere.** Desktop shows chapters in every motion context; mobile = explorer only. | 3.3 M3 pinned scroll — scrolling stopped at the map (twice). |
| P4 | **Home hero headline = "Your science. *Our stage.*"** from Company settings (`hero_headline`, editable in wp-admin). "Science *in focus.*" stays the brand signature (footer, motion concept). | 1.3/3.1 hero H1. |
| P5 | **Stats line: "⑮ congresses. ⑦ cities. ⑤ countries. One [*name.*]"** — numbers in blue pills, "name." in blue viewfinder brackets; pills pop in one after another, then the brackets close (once; Light = fade; Reduced = static). Never a count-up. | 3.1 "One name" statement. |
| P6 | **About H1 = "From [Alexandria] to ⑤ countries."** — same device and motion as P5; number computed. | — |
| P7 | **Header is sticky on inner pages; the Events filter bar sticks directly under it.** | Bug fix (header was scrolling away). |
| P8 | **Cinematic pass (v1.6):** static film grain on hero, ink sections and maps · Ken Burns on showreel stills (Full) · hero H1 focus pull word by word · rack focus on photos (images only) · card hover light sweep on the poster diagonal · route crossfade (build: `@view-transition { navigation: auto }`) · About map camera move per chapter/country with constant on-screen label size · event page: bigger poster + mini map with arc from Alexandria · **"Next congress" strip** in the hero figures bar (computed; "Happening now" when live) · showreel Pause + Sample tag moved to the hero corner. | 3.3 P14 "no page transitions" (now a progressive-enhancement crossfade). |
| P9 | **Services: each photo beside its own service, alternating sides (zigzag)**, rack focus + grain. | v1.6 sticky photo stack (photo and text felt out of sync). |
| P10 | **About map aspect 1.3:1** (taller, balanced with the chapter column). | 2.1:1. |
| P11 | **Mobile hero ≤ 844 px:** eyebrow hidden, H1 50 px, two buttons side by side, figures + next congress in the first screen. **Contact page footer omits the offices** (already listed above). | 3.4b critique 4. |
| P12 | **Giant "PL" outline:** fully inside its section; fill paints over the variable font's overlapping contours (`paint-order: stroke fill`). | Visual bug. |

## 4. Prototype-only differences (artifact host rules)
1. Routes are `#name` only — filter state lives in the page. **The build keeps state in the URL query** (locked 5).
2. `.ics` downloads blocked → "Add to Google Calendar"; the plugin serves `.ics`.
3. Forms validate but do not send (honest notice).
4. JS-rendered; **the build is server-rendered** (shortcodes) — no-JS = finished page there.
5. Showreel = crossfading real portfolio stills (Sample); the build uses MP4 + poster.

## 5. Build mapping (for 4.1–4.4)
Header/footer → UAE HFB + `[pl_live_ribbon]`, `[pl_offices layout="compact"]` (hidden on Contact) · **Home** → hero container (video) + `[pl_company field="hero_headline"]` + `[pl_company_figures layout="inline"]` + `[pl_next_event layout="strip"]`, `[pl_upcoming layout="grid" tabs="all,live,upcoming,past" default="all" limit="6"]`, `[pl_stats layout="line"]` + `[pl_logos type="society"]`, `[pl_services layout="numbered"]`, closing band (Elementor Heading/Text/Buttons, class `ink`) + `[pl_stats layout="sponsor_line"]` · **Events** → `[pl_calendar default="all" search="1" filters="specialty,country,year,type" views="grid,list"]` · **About** → H1 with `[pl_stats field="countries"]`, `[pl_story layout="sticky explore" aspect="1.3"]`, `[pl_company_figures layout="big"]`, `[pl_logos type="society"]`, `[pl_offices layout="split"]` · **Services** → `[pl_services layout="zigzag"]` + `[pl_logos type="sponsor"]` · **Careers** → `[pl_careers]` + Forminator (CV) · **Contact** → Forminator (topic + office pre-fill) + `[pl_offices]` · **Event page** → plugin template (poster, mini map, editions, contact, more upcoming).
**Motion modules for 4.2:** `core.js` (contexts), `iris.js` (+ Ken Burns, word focus pull), `viewfinder.js`, `story.js` (sticky + observer + camera), `patterns.js` (focus pull, cards/panels, rack focus, focus sets, outline, swaps, sheet, modal). GSAP 3.15.1 + ScrollTrigger (triggers only, no pin) + Lenis 1.3.27 (Home only), self-hosted.

## 6. Critique (as if I didn't make it)
1. **Only 2 upcoming events** — default All carries the page; switch `default` to "upcoming" by attribute when PL adds data.
2. **Grain at 9 %** may look "dirty" on some screens — drop to 5 % if so.
3. **Explorer viewfinder** frames the whole map, not the chosen country (camera move covers most of the intent).
4. **Registration "Open" for DEOSS 2027 / MOCAT 6** is still an assumption (TBC in data).
5. **Poster words** are typographic stand-ins; real posters/logos replace them per event.
6. **Public link shows the Prototype controls** — reveal copy needed.

## Open questions
None new for Sara. Reveal-day list unchanged (+ registration states, society/sponsor logo files).
