# Step 3.4a — Hi-fi: Step Summary (4 Nov 2026)

**Status: DONE — Sara approved the hi-fi on 4 Nov 2026.** Canvas: https://claude.ai/artifact/4c4cCfC1k1Wced3QKYW13T (Home, Events, About × Desktop 1280 / Mobile 390).

## Data
- **15 publishable events** (Jun 2024 – Mar 2027), 5 countries (Egypt, UAE, Saudi Arabia, Tunisia, Iraq), 7 cities. Status, date text, badges, Register button, tabs and counts, countries/cities, map pins and arcs, story chapters and explorer are computed.
- Offices: Alexandria (head office, +20 102 555 9003, events@plevents.org) and Dubai (regional office, +971 55 158 2689, **dina@plevents.org**).
- Societies (10 text tiles, logos at build). ISCRS left out until PL confirms. Sponsors: 8 names.
- Media: real photos and logo from PL-Portfolio.pdf (logo is a raster crop — vectors still needed); showreel = Sample; event posters are typographic.

## Decisions (binding)
| # | Decision |
|---|---|
| H1 | **Events tabs = All · Live now · Upcoming · Past; default All.** "Live now" only while an event is live. Order: Live → Upcoming (soonest first) → Past (newest first). Default is a shortcode attribute (`[pl_calendar default="all"]`). |
| H2 | **One continuous grid, no month or year headings.** Date tile + badge carry the time. |
| H3 | **Home section = "Our congresses."** with the same tabs (default All), 6 cards, "See {n} more in Events →". |
| H4 | **No favourites / ☆ Save / ★ My list anywhere.** No saved-list module in 4.1. |
| H5 | **Header CTA = Portfolio (PDF)** on desktop and mobile, next to "Request a proposal"; hero button = "Browse events". |
| H6 | **"Home" link in the main menu** (logo also links home). |
| H7 | **Button text colour:** `.pl .bp` etc. keep white text on blue (a global `a{color:inherit}` overrode it). Same class names in `hello-pl-child`. |
| H8 | **Open-now hidden** until PL sends opening hours. |

## Critique
1. Only 2 upcoming events today; default All hides that honestly; no fake upcoming events.
2. Registration "Open" for DEOSS 2027 and MOCAT 6 is an assumption — confirm on reveal day.
3. Portfolio as the header CTA trades doctor speed for society trust.
4. Flags were CSS approximations — use flag-icons SVG at build.
5. Long official names wrap to 3–4 lines; equal-height cards hold, nothing is cut.
