# Step 4.4 — Pages in Elementor: Step Summary (Thread 7, 5 Nov 2026)

**Status: ✅ built and live on staging — DoD 2 of 3 met; 1 item needs Sara's phone.**
| DoD item | Result |
|---|---|
| Each page matches its prototype at 375 / 768 / 1280 | ✅ Checked on `saraamrousy.net/pl` page by page (full-page screenshots + page height + horizontal overflow). At **1280: Home, Events, About, Services, Contact = identical height to the prototype**; at **768: Home, Events, About, Services identical**; 375 within ±30 px (text wrap). **Overflow 0 on every page at every width.** Careers/Contact are shorter only because the forms come in 4.5. |
| Mobile-4G Lighthouse pass | ✅ Performance **91–96**, Accessibility **100**, Best practices **96**, CLS **≤ 0.002** on all 8 pages (SEO 58 = the staging noindex, on purpose). LCP ≤ 2.5 s on 6 of 8 pages; **Home 2.8–2.9 s and Contact 2.7 s** — the remainder is the host's ~1.0–1.2 s server response (no page cache yet, see Open 1). |
| Real Safari / iOS / Android check | ⏳ **Sara** — the sandbox has Chromium only (WebKit/Firefox can't be installed there). Checklist below. |

## What was built
- **7 pages** — Home, Events, About, Services, Careers, Contact, Privacy — as **Elementor Free template JSON** in `/elementor` (built by `tools/build-elementor.py`, format confirmed by importing with Elementor 4.3.6's own importer). Copy is the prototype's (`views.js`) word for word.
- Each section = one full-width container + one **Shortcode** widget: `[pl_section]` + the prototype markup + the plugin shortcodes. Sara can reorder, duplicate or delete sections and edit the copy in Elementor; all data stays in wp-admin.
- Theme helpers (`inc/blocks.php`): `[pl_section]`, `[pl_url to=… topic=… anchor=…]` (links that survive the `/pl/` folder and the move to plevents.org), `[pl_form name=…]` (Forminator form chosen in 4.5), `[pl_media src=…]` (seed photo from the Media Library), `[pl_sample]`, `[pl_credits]`.
- **Provisioning:** `plt_provision()` writes the pages (and the same shortcodes into `post_content`, so a page still works if Elementor is ever off), sets **Home = front page**, **Privacy = privacy page**, rebuilds the menus, and moved the temporary `/pl-check/` page to the bin.
- Page H1s: Home "Your science. *Our stage.*" · Events "Events" · About "From [Alexandria] to ⑤ countries." · Services "Behind the *lens.*" · Careers "Work behind the *lens.*" · Contact "Talk to our *team.*" · Privacy "Privacy policy" (text TBC, Sample tag).

## Fixes found while matching the prototype
1. **Grid collapse on tablet/mobile:** the prototype scoped its 12-column collapse to `main`; Elementor Full Width printed no `<main>`. Fixed twice over: the build script widens the selector, and the theme now wraps Elementor pages in `<main id="content">` (also the skip-link target + main landmark — Lighthouse "skip link" fail fixed).
2. **About story below 1024 px:** chapters only from 1024 px (the prototype decided at render); narrower = explorer only, with the map at **2.1:1** instead of 1.3:1 (3.4b P10). Camera framing per chapter now = **all cities on the map so far, min width 330** — viewBoxes identical to the prototype at 5 scroll positions.
3. **Footer logo** had no height → page jumped when it loaded (CLS). Intrinsic sizes on all logos.

## Performance + accessibility work (from Lighthouse, mobile 4G)
| Change | Effect |
|---|---|
| Home header becomes transparent/fixed **while the page parses** (tiny inline script), not after load | Home CLS 0.08 → 0.002 |
| jQuery to the footer, jquery-migrate off; UAE HFB widget CSS, Font Awesome, Hello header CSS not loaded on the front end (`inc/perf.php`, never in the editor) | ~1.7 s render-blocking removed; FCP 3.0 → 1.9 s |
| Page H1 never hidden on mobile (the focus pull runs on desktop only) | About LCP 4.1 → 2.5 s |
| Metric-matched fallback font ("Montserrat Fallback" = Arial with size-adjust) | Font swap no longer re-wraps lines (event page CLS 0.12 → 0.001) |
| `srcset` for hero stills and service photos; first service photo eager + `fetchpriority` | Services LCP 3.3 → 2.3 s |
| 1-year cache headers for static files in `pl/.htaccess` (`deploy/htaccess-cache.sh`, this folder only) | repeat visits |
| A11y: wordmark text = its accessible name; explorer links use `aria-current` (not `aria-pressed`); hidden "Results" h2 on Events; event-page labels contrast (2.5 → ≥ 4.5) | Accessibility 93–98 → **100** |

## Decisions
| # | Decision | Why |
|---|---|---|
| 4.4-1 | Sections are **Shortcode widgets with `[pl_section]` + HTML**, not Elementor Heading/Text widgets. | Elementor's widgets add their own wrappers/typography and would drift from the prototype; this keeps the approved markup exactly, and the copy is still edited inside Elementor. |
| 4.4-2 | Pages are provisioned from the JSON on staging (same files Sara can import by hand). | Sara doesn't work by hand; one source for both. |
| 4.4-3 | Page H1 focus pull: desktop (Full) only. | It was the mobile LCP element; "reading text never moves" — the H1 now simply appears on phones. |

## For Sara — phone check (5 minutes)
Open `https://saraamrousy.net/pl/` on **iPhone (Safari)** and an **Android phone (Chrome)**: Home (hero, tabs, menu button), Events (Filters button → bottom sheet, Grid/List, a card's WhatsApp + calendar icons), About (country chips), an event page (Add to calendar opens the calendar app). Tell me anything that looks off, with a screenshot.

## Open
1. **Page cache (decision for Sara):** the host is **LiteSpeed**, so **LiteSpeed Cache** (free, from WordPress.org; full-page cache + its purge API, which `pl-events-core` already calls on status changes) would take the ~1 s server time off every page and bring Home under 2.5 s. Free tier: everything we need is free (QUIC.cloud image/CDN extras are optional). For plevents.org the cache plugin is chosen once PL's host is known (locked decision 2).
2. Real-device check (above).
3. The About photo and the services/hero photos are from PL's portfolio (seed); the showreel stays Sample.

## Critique
1. Copy lives in HTML inside Shortcode widgets: fine for Sara, not for a non-technical editor — but page copy is not editor content by design (R11 covers data, not layout).
2. Home is the heaviest page (hero stills + GSAP + Lenis ≈ 95 KB JS deferred); a real MP4 showreel must stay ≤ 2.5 MB.
3. Lighthouse runs from the sandbox's network, not a phone on a real 4G line — the numbers are directional; repeat in 6.1 from PageSpeed Insights once the site is public.

## Thread 8 — page cache (Sara approved LiteSpeed Cache, 6 Nov 2026)
| Item | Result |
|---|---|
| Plugin | **LiteSpeed Cache 7.9.3** (free; the host runs LiteSpeed, so full-page caching is free and server-level). Page cache on, 7-day TTL. CSS/JS combine/minify, lazy-load and image optimisation **off** (they would fight the motion scripts and the srcset work). |
| Excluded | `/contact/`, `/careers/` — their forms carry a security nonce that expires; a cached copy would break submissions. |
| Speed | server response on cached pages **0.87 s → 0.22 s** (`X-LiteSpeed-Cache: hit`). |
| Purge | `pl-events-core` now purges the whole page cache **after any change to PL content** (save/trash/delete of any PL item, Company page, list terms) and on the hourly status check (an event going Live/Past). Tested: re-saving an event turned Home, Events and About from `hit` to `miss`, then `hit` again. |
| Mobile weight | Home: the 3 extra showreel stills (~400 KB) no longer load on phones (they only feed the desktop crossfade; `iris.js` loads them in the Full context). Home page weight **800 KB → 317 KB**; desktop crossfade still works. LCP image preloaded. |
| Lighthouse (mobile, from the sandbox) | Events 96 · About 96 · Contact 93 · Home 86–93 (LCP 2.6–2.9 s; most of it is the sandbox↔server route through the proxy — TTFB 0.5–0.9 s even on cache hits). **Check from a normal network with PageSpeed Insights** once the site is public (6.1). |

## Still open
- **Hero fix (6 Nov, Thread 8)** — the photo stopped at the buttons and grey showed below. Cause: Elementor's `.elementor img{height:auto}` beat `.still{height:100%}`. Now the media is `inset:0` + `object-fit:cover` + `height:100%`. The iris shape is measured from the hero's live `getBoundingClientRect()` (on resize + ResizeObserver) and goes back to full-bleed when it opens. The header turns solid before the viewfinder brackets reach the logo. Tested at 1190×575, 1280×600, 1440×700, 1920×1080, 375×667, 768×1024 × Full / Light / Reduced, plus resizing the window, also mid-opening. LCP image unchanged. Before/after screenshots sent to Sara.
- **Image fit (6 Nov, Thread 8)** — every image now sits in a fixed frame (photos cover, logos contain), with server crops + srcset, a soft size note in wp-admin and safe SVG. Details + screenshots: `docs/Step-4.4-Image-Fit.md`.
- **Sara's phone check (iPhone Safari + Android Chrome)** — result not received yet ("[هكتبها هنا]" left empty in the Thread 8 message). 4.4 closes when it arrives.
