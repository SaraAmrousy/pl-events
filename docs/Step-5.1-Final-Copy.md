# Step 5.1 — Final Copy: Step Summary (Thread 8, 6 Nov 2026)

**Status: ✅ DONE.** DoD "no placeholder text remains": every page and all 15 event pages on `saraamrousy.net/pl` were crawled and read (22 URLs: visible text, H1s, links, buttons, image alts). What remains are **deliberate, labelled unknowns** — "TBC" and "Sample" tags that only PL's data can remove (list below) — not placeholder copy. Skill: `pl-content-qa`.

## Checks
| Check | Result |
|---|---|
| One H1 per page | ✅ 22/22 |
| Placeholder text (lorem, TODO, "coming soon", "[…]", "0 +", undefined/NaN) | ✅ none |
| Empty / "#" links, images without alt | ✅ none |
| Clichés ("leading", "innovative", "premier", "world-class", …) | ✅ none |
| CTAs start with a verb ("Submit", "Read more", "Click here") | ✅ none of those; one fixed (below). Card "Website" button kept (approved 3.4a, familiar control). |
| Dates always with the year ("29–30 Jan 2027", "30 Oct – 1 Nov 2026") | ✅ every date, cards, rows, event pages, story |
| Past shown as upcoming | ✅ none (13 past "Held …", 2 upcoming "Registration open") |
| Numbers computed, not typed (15 / 7 / 5, "since June 2024") | ✅ |

## Fixed (live on staging)
| # | Where | Was | Now | Severity |
|---|---|---|---|---|
| 1 | About intro | "…every congress **we run** is on this site…" — the site lists 15; PL states 500+ | "…every congress **since June 2024** is on this site…" — the date is computed from the earliest event, so it stays true when PL adds its history | **Major** (R10, misleading) |
| 2 | Events intro | "**Every** PL Events congress, symposium, course and **webinar** — …" — no webinar is listed | "PL Events congresses, symposia and meetings **since June 2024** — live, upcoming and past, with dates, cities and registration." (computed date) | **Major** (R10) |
| 3 | Privacy | "The full text comes from PL Events **(reveal-day request)**…" — internal note, reveals the surprise | "Our full privacy policy is being prepared. It will cover the contact and careers forms, how long we keep CVs, and cookies." + "Text TBC" tag | **Major** |
| 4 | About story tallies | "1 cities", "1 countries" | "1 city", "1 country" | Minor |
| 5 | About explorer, "All countries" | "See events in All countries" | "See all events" | Minor |
| 6 | Event page | "Google Calendar" | "Add to Google Calendar" (as in the prototype) | Minor |
| 7 | Egyptian Lymphedema Conference | "Edition 2025" (the year, not an edition) | an "edition" equal to the event's year is not shown (display only; the stored data is unchanged) | Minor |

Sources changed: `tools/build-elementor.py` (About, Privacy copy → rebuilt `/elementor/*.json`; on staging only the two sentences were replaced inside the pages, nothing else touched), plugin templates/data (3–7). Data-layer tests: all passed.

## Deliberate unknowns still on the site (TBC / Sample) — for PL, Step 5.2
| Where | Text | Needs from PL |
|---|---|---|
| Home hero | "Sample showreel" tag | 30–60 s of real congress footage (or keep stills, untag) |
| Contact | "Opening hours: TBC — added by PL" | office opening hours |
| Privacy | "Text TBC" | privacy policy text + CV retention period |
| ESOIRS 2024 page | "Edition 36th (TBC)" | confirm the edition number |
| Company figures | "13+ years" | founding year (2011 vs 2013) — figures are labelled "Company figures", PL's own claim |
| Registration "Open" for DEOSS 2027, MOCAT 6 | assumption | confirm (3.4a) |

## Data to tidy (not copy — 5.2, in wp-admin)
1. Series **"EGYPTLYMPHEDEMA"** shows in capitals ("EGYPTLYMPHEDEMA SERIES"): rename the Series term (e.g. "Egyptian Lymphedema"); its Short name can stay short.
2. Head-office address "Kirosiez Mall building…" — spelling as on PL's current site; PL to confirm.

## Handed to 5.3 (SEO)
- **No meta description on any page**: Rank Math is active, so the plugin's own fallback steps aside, but Rank Math has no descriptions/templates set yet. 5.3: Rank Math titles + descriptions (≤ 155 chars), Event + Organization schema. Site title still "PL Events (staging)".

## Critique
1. "Congresses" in the stats line also counts conferences, a symposium and a forum (all event types are set to "Counts as a congress"). True to the 2.2 rule, but a strict reader could object — the admin can switch a type off in PL Website › Event types.
2. The page copy lives in the Elementor shortcode widgets; future copy edits there are manual for Sara (by design, 4.4-1).
