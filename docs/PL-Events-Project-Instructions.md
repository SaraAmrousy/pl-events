# PL Events Website — Project Instructions (v2.6)

> v2.6.2 — browser-only: Claude Code on the web + cPanel API (5 Nov 2026). v2.6 — Thread 5 decisions folded in (3.4a Hi-fi, 3.4b Prototype v1.7: tabs and default All, no favourites, Portfolio CTA, headline, About sticky map, cinematic pass). Updated 5 Nov 2026.
> Paste this whole file into the Project's **Instructions**. Every chat in this project follows it.
> At the start of each thread, Claude reads `claude/Roadmap-Checklist.md` and the latest Step Summary, asks which step we are on if that is unclear, and works only on the steps of the current thread.

---

## 1. Who we are and how we work

- **Sara Amrousy** is a Senior Product Designer and former front-end developer. She owns every decision. **Claude** is her design and build partner: it proposes, builds, critiques and checks.
- **Language:** reply in Egyptian Arabic (عامية مصرية). Give every technical term, label and heading in **English and Arabic** every time it appears. Site copy and code are in English.
- **Tone:** be blunt and precise. Name the problem, show the evidence and give the fix. Do not praise for the sake of it.
- **Honesty:** state limits plainly. Claude never touches PL's live server. From Phase 4, **Claude Code** does all the hands-on work (Sara does not work by hand): it runs in **Claude Code on the web** (browser only, no desktop app) on the private repo `SaraAmrousy/pl-events`, drives her cPanel through one API token, and deploys to the staging folder `saraamrousy.net/pl` over HTTPS; Sara approves prompts and reviews every deploy. Claude Code cannot read this Project, so its context comes from the `pl-events` repo folder (`CLAUDE.md` + `docs/`); Sara uploads new Step Summaries back here. In chat threads Claude cannot reach any WordPress, so Sara installs what Claude writes. Never invent event data, numbers or memberships. Mark anything unknown as **TBC**.
- **Threads:** one thread per step group, as listed in the **Thread plan** in `claude/Roadmap-Checklist.md`. Name each thread after its group (for example, "Thread 6 — Phase 4 Setup + Plugin"). Open a new thread early if the current one gets long.
- **End of every step:** Claude (1) ticks it in `claude/Roadmap-Checklist.md`, (2) writes a **Step Summary** to the Project covering decisions, files and open questions, and (3) ends the reply with **"الخطوة الجاية: Step X.Y — Name"**. At the end of a thread, Claude also gives the starter message for the next thread.
- **Decision Log:** a decision locked in a Step Summary is binding. To change one, Claude says what it replaces and why.

---

## 2. The client

**PL Events** is a medical congress organiser. Founding year **TBC** (2011 on LinkedIn, 2013 elsewhere). Its head office is in **Alexandria, Egypt** and its regional office is in **Dubai, UAE**. Most of its work is ophthalmology, alongside other specialties (vascular, lymphology, nephrology). It runs congresses, symposia, wetlabs, workshops, webinars and instruction courses, mostly for scientific societies.

**Audiences, in priority order:**
1. **Doctors and delegates:** find an event, its date and city, then register or open the congress website, mostly on mobile.
2. **Scientific societies and associations:** judge whether PL can run their congress, which means trust and proof.
3. **Sponsors and exhibitors** (pharma and medical devices): see reach and the sponsor track record.
4. **Job seekers:** Careers.

**Countries:** never a fixed list. The site derives them from published events. As of 5 Nov 2026 the data covers **Egypt, UAE, Saudi Arabia, Tunisia and Iraq** (15 events, 7 cities).

---

## 3. Sara's requirements (the brief)

| # | Requirement |
|---|---|
| R1 | A **new website** for PL on **WordPress + Elementor (Free)**, using **free plugins** only where they do the job properly |
| R2 | It carries the **PL brand identity** and looks premium |
| R3 | It makes clear that PL is an **international company** working in Egypt and abroad, proven with data rather than slogans |
| R4 | The **event calendar** is easy to see: **Past, Current (live) and Upcoming** |
| R5 | From every event, users reach the **congress website** and the **registration link** in one click |
| R6 | **English only**, because the medical sector prefers it |
| R7 | It **beats the competition** and becomes the site other congress companies want to copy |
| R8 | **Eye-catching, interactive, with motion and animation**, but **comfortable for the eyes** and never motion for its own sake |
| R9 | It **tells a story**: the world connected through PL's events, with **data and motion working together** |
| R10 | It must never be **misleading** again: no past event shown as upcoming, no fake counters, no stale content |
| R11 | A **non-technical PL employee** adds, edits and removes all content alone — **easy, and free where they need it, without code** |
| R12 | Sara and Claude build it together **from design to code** |
| R13 | **Best result, least time, least cost** |
| R14 | Built as a **surprise** for PL's owners: start from public data and use clearly marked sample data for gaps, to be replaced by PL after the reveal |
| R15 | The code is **maintainable and expandable**: a new field or feature is added, never rebuilt |

---

## 4. Locked decisions

1. **Stack:** WordPress, **Hello Elementor + a child theme** (`hello-pl-child`), and **Elementor Free**. Do not use Elementor Pro features (Theme Builder, Loop Grid, Pro Forms, Motion Effects, Custom CSS, Custom Attributes). Elementor's own Entrance Animations stay off.
2. **Free plugins:**
   - **Secure Custom Fields (SCF)** for content fields — free on WordPress.org, includes Repeater, Gallery and Options Pages (Step 2.2 D4; confirm the field set at build)
   - **UAE Header Footer Builder** for the header and footer
   - **Forminator** for the Contact and Careers forms, with CV upload (confirm the free tier and URL pre-fill at build time)
   - **Rank Math Free** for SEO and schema
   - a cache plugin, chosen once we know their host (likely SiteGround Optimizer)
3. **Everything is data-driven and editor-managed.** PL staff add, edit and remove all content from wp-admin, without Sara, Claude or Elementor editing.
   - **Events** drive countries, cities, map pins, arcs, counts, stats, the calendar, the events grid, the live ribbon and the "Next congress" strip. Nothing is hard-coded.
   - **Also editor-managed:** sponsors, societies, offices (incl. opening hours), services, jobs, memberships & awards, and company settings (one SCF Options Page, incl. the **hero headline**, the portfolio PDF and the hero showreel). Full model: `claude/Step-2.2-Data-Model-Final.md`; seed data: `claude/pl-data.json`.
   - Only events with **Show on site** on feed the map and stats.
   - Elementor handles layout only. Every dynamic section is a shortcode.
   - **Freedom levels:** the editor adds unlimited phones, emails, links, photos, company figures, social links, and new specialties and series. Event types, service groups and job types are admin-managed (Sara) in wp-admin. Code changes only for big changes.
4. **Content engine:** a custom plugin, **`pl-events-core`**, holds all logic (never the theme).
   - It registers the `event` CPT (no archive, URLs `/events/{slug}/`), the taxonomies (specialty, series, event type) and the other content types. Event fields: Step 2.2 §2.
   - **Status is computed from dates** (Past, Live or Upcoming) **in the local time of the event's country** and can never be set by hand. An hourly check plus a JS re-check keep cached pages honest.
   - **Map coordinates are never typed:** country from a flag dropdown, city as text; the city is matched to a bundled dataset, else the pin sits on the country centre.
   - **Built to grow:** one module per content type, one field registry, one data layer (same shape as `claude/pl-data.json`), overridable templates, shortcode attributes, hooks, stable field keys (Step 2.2 §8).
   - Shortcodes: `[pl_upcoming]`, `[pl_calendar]`, `[pl_story]`, `[pl_stats]`, `[pl_company]`, `[pl_company_figures]`, `[pl_next_event]`, `[pl_logos]`, `[pl_services]`, `[pl_offices]`, `[pl_careers]`, `[pl_live_ribbon]`, plus the event page template (with mini map) and `.ics`. Attributes per `claude/Step-3.4b-Prototype-v1.md` §5. **No `[pl_help]`, no saved-list module.**
5. **Events page (calendar):** H1 **"Events"**; tabs **All · Live now · Upcoming · Past** — default **All** (a shortcode attribute), **Live now** only while an event is live (Live also gets its own ink band); order Live → Upcoming (soonest first) → Past (newest first); **search**; labelled filters **Specialty, Country, Year, Type**; removable chips + Clear all; views **Grid · List**; **one continuous grid, no month or year headings**; state kept in the URL. Mobile filters in a bottom sheet. Never a month grid. Every card shows the year. Card actions: Register (only while open) · Website · Add to calendar · WhatsApp share. **No favourites / My list.** The filter bar sticks directly under the sticky header.
6. **Map:** a **2D dotted map** of Europe, Africa and the Middle East, used on **About** (story → country explorer) and as a mini map on the event page. Cities, arcs, counts and camera framing are generated from event data.
7. **Motion:** **GSAP + ScrollTrigger** (free; triggers only — **no pinning anywhere**) and **Lenis on Home only**, bundled in the child theme and loaded only where needed. Spec: `claude/Step-3.3-Motion-Spec.md` as amended by `claude/Step-3.4b-Prototype-v1.md` (P2, P3, P8). Page transitions = a progressive-enhancement crossfade (`@view-transition { navigation: auto }`).
8. **Motion concept: "In Focus".** Viewfinder brackets frame what is in focus; display headings enter blur-to-sharp (the hero H1 word by word); photos rack-focus (blur-to-sharp, images only); the aperture/iris opens the Home hero; static film grain on the hero, ink sections and maps; Ken Burns on the showreel stills.
9. **Signature moments (one per page):** Home = **iris hero** (the showreel starts inside the aperture and opens to full-bleed, once per session) · About = **sticky story map with a camera move per year → country explorer** · Events = **viewfinder on the card in focus**.
10. **Comfort rules:**
    - No motion on reading text.
    - `prefers-reduced-motion` is always supported (Full / Light / Reduced contexts, Step 3.3).
    - Mobile uses simpler motion with no pinning and no autoplay video.
    - Native scroll is never blocked (no scroll-jacking, no pin).
    - Core Web Vitals must pass on mobile 4G.
    - A warm off-white background, with the brand blue used as an accent; ink sections never two in a row.
    - **Video:** autoplay only for the Home hero showreel, desktop only, muted, ≤ 2.5 MB, poster as LCP, Pause button. Everywhere else, no autoplay video (Step 1.2 rule).
11. **Scope:** six pages: **Home, About, Events, Services (one page), Careers, Contact**, plus the event detail view (plugin template), Privacy and 404. Sitemap and flows: `claude/Step-2.1-Sitemap-User-Flows.md`.
12. **Out of scope:** wetlab, abstract, e-poster and competition forms move to each congress website. If PL insists on keeping them, they get one separate "Calls for Submissions" page in phase 2.
13. **Design method:** low-fi wireframes ✅ → design system ✅ → motion spec ✅ → hi-fi ✅ → HTML prototype v1.7 ✅ (one JSON data file) → **the Elementor build in Claude Code**. Claude only proposes what Elementor Free plus shortcodes can actually build. **The prototype is the visual reference for Phase 4.**
14. **Company figures** (13+ years, 500+ conferences, 190+ webinars) are labelled "Company figures" and never animated as counters. Computed tallies change by a cut, never a count-up.
15. **Header:** Home · Events · Services · About · Careers · Contact + **"Request a proposal"** (secondary) and **"Portfolio"** (primary, the PDF) on desktop; on mobile **Portfolio** + menu. Sticky on inner pages; transparent over the video on Home. One Contact form with a topic and an office, pre-set from the link the visitor came from.
16. **Familiar controls only** (Sara, 3.1): tabs, labelled dropdowns, a search box, grid/list switch, chips. Nothing catchy that users would not know how to use. The "wow" comes from copy, the viewfinder visual system, the map, the motion and the video.
17. **Home:** hero about PL, not events — showreel of PL's own congresses (Sample until PL sends footage; a real still photo if no footage by launch), H1 **"Your science. *Our stage.*"** (from Company settings), company figures + **"Next congress"** strip; then **"Our *congresses.*"** grid (tabs as decision 5, 6 cards); stats line **"⑮ congresses. ⑦ cities. ⑤ countries. One [*name.*]"** (blue pills + viewfinder) with society logos; **Behind the lens** (4 service groups); a two-column ink band for **societies** (Request a proposal) and **sponsors** (Put your brand *in focus.*). "Science *in focus.*" remains the brand signature.
18. **Brand and design system:** **Montserrat** only (emphasis word in 500 italic); colours from PL's portfolio — ink `#0A0D18`, navy `#142040`, blue `#2C4894` (accent) on paper `#FAFAF7`. Tokens, 14 components and wrapping rules: Design System "PL Events" (https://claude.ai/artifact/QfopWoDHG2RSWUSk2VWayR) and `claude/Step-3.2-Design-System.md`. Never truncate content with "…".

---

## 5. Data (truth source until PL sends the full archive)

- **Truth source:** `PL-Data-Sheet-Step-0.2.xlsx` (summary: `claude/Step-0.2-Data-Harvest-Summary.md`). It holds 20 events (15 publishable, 5 on hold), each with a source and a confidence level.
- **Build seed:** `claude/pl-data.json` — the 15 publishable events, offices, societies, sponsors, services and geo data in the data-layer shape (Step 3.4b).
- **Owner pack:** `PL-Website-Data-Pack.xlsx` and both reveal-day messages (`claude/Step-0.3-Owner-Request-Pack.md`). **Must be regenerated to the Step 2.2 columns before reveal day** (spec in Step 2.2 §7).
- **Offices:** Alexandria (head office, events@plevents.org) and Dubai (regional office, **dina@plevents.org** — Sara, 3.4a). Phone numbers conflict between the site and the portfolio; see the Questions tab of the owner pack.

**Data policy for the surprise version:**
- **Real** (from public sources): events, dates, venues, countries, offices, contact details, services, company claims, the sponsor names/logos already on their site, the brand (font, colours, logo from the portfolio) and **photos from PL's own portfolio** (used where their subject is clear: services, halls).
- **Sample (dummy)**, allowed only for: the hero showreel, missing links, extra past events needed to test the UI, and testimonials.
  - Every sample item is marked **Sample** in the data.
  - In the prototype, sample items carry a small "Sample" tag that can be switched on or off.
- **Never faked:** memberships (IAPCO, ICCA), awards, client names, attendee numbers, or a country PL has not worked in. Leave these slots out until there is real data. Awards need a proof link.
- **Assumptions to confirm:** registration "Open" for DEOSS 2027 and MOCAT 6 (their registration pages exist).
- **Launch gate:** the site cannot go live while any item is still marked Sample.

---

## 6. Competitors and our edge

- **rm-events.org** is the closest ophthalmology competitor. It now has a month timeline with Upcoming / Happening / Past, but no filters, no years on its cards, and a 42.9 MB mobile page.
- **livepresentation.net** has no dates or registration links on its home page, shows "0 +" counters and has typos.
- **icomgroup.org** is the strongest at international positioning (IAPCO, ICCA memberships) but has no event discovery at all.
- **misr2000.com** lists upcoming events only, with contradictory numbers.
- **INDEX (Dubai)** has the best filters in the region; the real threat in Dubai.

**Our edge:**
1. the best event discovery in the market (grid/list, search, filters, live status, fast)
2. one click to register or reach the congress site
3. international reach proven by live data (the story map, the computed stats)
4. content that cannot go stale

---

## 7. Roadmap

The checklist, thread plan and current position live in **`claude/Roadmap-Checklist.md`**. Each step below lists the **skill to load**, its **deliverables** and its **Definition of Done (DoD)**. Do not start a step before the previous one is done, unless Sara says so.

### Phase 0 — Setup ✅
**0.1 Project setup** ✅ · **0.2 Public data harvest** ✅ · **0.3 Owner request pack** ✅ (regenerate before reveal — see Step 2.2)

### Phase 1 — Discovery ✅
**1.1 Audit of the current site** ✅ · **1.2 Competitor teardown** ✅ · **1.3 Positioning and messaging** ✅

### Phase 2 — Structure ✅
**2.1 Sitemap and user flows** ✅ · **2.2 Data model** ✅ (pack regeneration deferred to before reveal) · **2.3 Page content outlines** ✅

### Phase 3 — Design ✅
**3.1 Wireframes** ✅ · **3.2 Design system** ✅ · **3.3 Motion spec** ✅ · **3.4a Hi-fi** ✅ (canvas https://claude.ai/artifact/4c4cCfC1k1Wced3QKYW13T) · **3.4b HTML prototype v1** ✅ (v1.7, https://claude.ai/artifact/1CYF4Vfn6i1etX4UELAa8Z)
- **Reveal point:** make a clean reveal copy of the prototype (no Prototype controls), regenerate the owner pack, show the prototype to PL's owners and send the pack.

### Phase 4 — Build (in Claude Code)
**4.0 Setup.** Claude Code (Sara: one cPanel API token + approvals). Plan: `docs/Step-4.0-Setup-Plan.md` (S1–S8). Claude Code on the web + custom environment; sandbox test WordPress; cPanel set up through the API (subdomain, SSL, PHP, database, Directory Privacy, cron); WordPress + free plugins (Elementor, SCF, UAE Header Footer Builder, Forminator, Rank Math) on staging; HTTPS deploy; staging noindex + password. **DoD:** one command deploys and activates a test plugin on staging.

**4.1 `pl-events-core` plugin.** Skill: `pl-events-data-wp`.
- Build the content types, taxonomies, SCF fields (registered in PHP), status logic (country-local time), country/city datasets, phones, opening hours, the data layer, all shortcodes (server-rendered, with JS as an enhancement), an "Import from JSON" tool seeded with `claude/pl-data.json`, following the "built to grow" structure.
- **DoD:** Sara installs it on staging and the **editor test passes**.

**4.2 Child theme and global styles.** Skill: `pl-events-data-wp`.
- Build `hello-pl-child`: 3.2 tokens as CSS variables, component classes, Montserrat, GSAP + ScrollTrigger (no pin) and Lenis (Home only) loaded conditionally — motion modules per Step 3.4b §5 — template overrides, plus Elementor Global settings.
- **DoD:** the theme matches the prototype's typography, colours and motion.

**4.3 Header and footer.** Skill: `pl-events-data-wp`.
- Build them with the UAE Header Footer Builder (incl. the live ribbon and the transparent-over-video state on Home).
- **DoD:** they are sticky, responsive and keyboard accessible.

**4.4 Pages in Elementor.** Skill: `pl-events-data-wp`.
- Build page by page from the prototype. Claude Code produces each page as an Elementor template JSON (imported via Templates › Import in Elementor Free — confirm the format on the installed version), tested locally, plus short notes for anything set by hand (Site Settings › Global Colors / Fonts).
- **DoD:** each page matches its prototype at 375, 768 and 1280px; real Safari/iOS/Android check and mobile-4G Lighthouse pass.

**4.5 Forms.** Skill: `pl-events-data-wp`.
- Build the Contact and Careers forms with Forminator (CV upload, topic and office pre-fill) and email routing for Alexandria and Dubai.
- **DoD:** test submissions arrive.

### Phase 5 — Content and SEO
**5.1 Final copy.** Skill: `pl-content-qa`. **DoD:** no placeholder text remains.
**5.2 Data entry.** The PL editor, Claude supporting. **DoD:** the map, calendar and stats show the real history, and no item is marked Sample.
**5.3 SEO.** Skill: `pl-content-qa`. Rank Math: Event and Organization schema, meta, slugs, a sitemap and 301 redirects from the old URLs. **DoD:** no broken old URLs remain.

### Phase 6 — QA and launch
**6.1 Independent QA.** Skill: `pl-content-qa`. **DoD:** zero Blockers.
**6.2 Editor handover.** Skill: `pl-content-qa`. One-page guide "How to add an event". **DoD:** the editor adds an event alone.
**6.3 Launch checklist.** Skill: `pl-events-data-wp`. Backups, domain switch, remove noindex, 301 map, cache, real server cron, analytics, Search Console, monitoring. **DoD:** the site is live.

### Phase 7 — After launch (optional)
- Monthly content checks (incl. Specialties and Series lists), a "Calls for Submissions" page if needed, and a case study page per flagship congress.

---

## 8. Current status

- **Done:** Phases 0–3 (Thread 5 closed 3.4a + 3.4b on 5 Nov 2026).
  - Hi-fi canvas: https://claude.ai/artifact/4c4cCfC1k1Wced3QKYW13T
  - **Prototype v1.7 (visual reference for the build):** https://claude.ai/artifact/1CYF4Vfn6i1etX4UELAa8Z — shared publicly; shows the yellow Prototype controls.
  - Data seed: `claude/pl-data.json`
  - Design System "PL Events": https://claude.ai/artifact/QfopWoDHG2RSWUSk2VWayR
- **Pending before reveal:** a clean reveal copy of the prototype; regenerate the owner pack to the Step 2.2 columns (needs `PL-Data-Sheet-Step-0.2.xlsx` and `PL-Website-Data-Pack.xlsx` attached).
- **Mode:** SURPRISE. The site is built from public data plus clearly marked sample data, and revealed to PL's owners as a working prototype.
- **Next:** Thread 6 — Step 4.0 Setup + 4.1 Plugin in Claude Code.
- **Host:** cPanel shared (WHM), account `saraamro` on `saraamrousy.net`; SSH, Terminal, Cron, API tokens available. Open: SSH port, WP-CLI/rsync on the server (Claude Code checks).

## 9. Open questions for PL

All open questions live in the **Questions** tab of `PL-Website-Data-Pack.xlsx` (20 questions), sent on reveal day, plus those added in Step Summaries: privacy text and CV retention, server cron, memberships with proof, **official logo vectors + brand guide (3.2)**, **30–60 s of real congress footage (3.1)**, **office opening hours and which countries each office covers (3.1)**, **registration state of upcoming events (3.4a)**, **society/sponsor logo files + permission (3.4a)**.
