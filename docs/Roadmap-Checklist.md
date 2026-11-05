# PL Events Website — Roadmap Checklist (قايمة الخطوات)

> **Rule for Claude:** at the end of every step, (1) tick it here, (2) write the Step Summary to the Project, (3) end the reply with **"الخطوة الجاية: Step X.Y — Name"**. At the end of the last step in a thread, also give Sara the starter message for the next thread.
> **Threads:** one thread per group below (not per step). Open a new thread early if the current one gets long; the Step Summaries in the Project carry the context.
> Last updated: 6 Nov 2026 (Thread 8)

**Now:** end of Thread 8 (6 Nov 2026). ✅ 4.5 Forms · ✅ 5.1 Final copy · ✅ 5.3 SEO (301 map ready for launch) · ✅ hero fix + image fit (every image fits its frame) · ⏳ 4.4: only Sara's phone-check result is missing · open decision: "Counts as a congress" for symposium + forum (A/B) → **Next: Thread 9 — Step 6.1 Independent QA** (5.2 data entry is PL's, after the reveal).

---

## Thread plan (خطة الثريدات)
| Thread | Steps | Why grouped this way |
|---|---|---|
| 1 | 0.1–0.3 ✅ | Setup |
| 2 | 1.1–1.3 Discovery ✅ | Research and writing, light |
| 3 | 2.1–2.3 Structure ✅ | Each step feeds the next |
| 4 | 3.1–3.3 Wireframes, design system, motion spec ✅ | Design decisions together |
| 5 | 3.4a Hi-fi ✅ + 3.4b Prototype v1 ✅ | Visual sign-off on the canvas first (cheap edits), then the full HTML prototype |
| 6 | 4.0 Setup + 4.1 Plugin — **Claude Code** | **Claude Code on the web (browser only)** on the private repo `SaraAmrousy/pl-events`; cPanel driven by an API token; deploy over HTTPS; PHP/JS built and tested in the cloud sandbox |
| 7 | 4.2–4.5 Theme, header/footer, pages, forms — **Claude Code** | Child theme, Elementor templates as JSON imports, forms; deploy to the staging folder |
| 8 | 5.1–5.3 Content & SEO | |
| 9 | 6.1–6.3 QA & launch | |

### Starter message for the next thread
Open **claude.ai/code** → repo `SaraAmrousy/pl-events` (branch `main`) → environment `pl-events`.
```
Thread 9 — Phase 6 QA & launch (6.1–6.3) — Claude Code.
اقرا CLAUDE.md وبعدين docs/PL-Events-Project-Instructions.md و docs/Roadmap-Checklist.md و docs/Step-4.4-Pages.md و docs/Step-4.4-Image-Fit.md و docs/Step-5.1-Final-Copy.md و docs/Step-5.3-SEO.md.
الأول قفّل اللي فاضل:
1. 4.4 — نتيجة فحص الموبايل: [اكتبي النتيجة هنا].
2. قرار "Counts as a congress" للـ symposium والـ forum: [A يتقفل / B يفضل].
بعدها 6.1 — Independent QA (skill pl-content-qa): a11y + performance (375 / 768 / 1280، Full / Light / Reduced) + editor test على saraamrousy.net/pl (منها: رفع صورة صغيرة → الملاحظة الصفرا، وSVG).
ثم 6.2 — Editor handover (دليل للموظف بالإنجليزي، خطوة بخطوة من wp-admin، فيه مقاسات الصور).
6.3 — Launch checklist: اكتبه بس (فيه deploy/launch/redirects.csv + check-redirects.py، والـ 3 slugs الـ TBC من wp-sitemap.xml وقت الإطلاق)، ومتلمسش plevents.org قبل ما أوافق على كل خطوة.
الـ staging يفضل noindex، والإيميلات كلها بتروحلي أنا. متكتبش أي باسورد في الشات.
```

---

## Phase 0 — Setup (التجهيز) ✅
- [x] **0.1 Project Setup** (تجهيز الـ Project) — Sara
- [x] **0.2 Public Data Harvest** (جمع الداتا العامة) — `pl-content-qa` · Output: `PL-Data-Sheet-Step-0.2.xlsx` · Summary: `claude/Step-0.2-Data-Harvest-Summary.md`
- [x] **0.3 Owner Request Pack** (الطلبات لأصحاب الشركة) — `pl-content-qa` · Output: `PL-Website-Data-Pack.xlsx` + 2 messages · Summary: `claude/Step-0.3-Owner-Request-Pack.md`

## Phase 1 — Discovery (الاستكشاف) ✅
- [x] **1.1 Current Site Audit** (مراجعة الموقع الحالي) — `pl-content-qa` · Output + Summary: `claude/Step-1.1-Current-Site-Audit.md` (29 issues: 5 Blocker, 16 Major, 8 Minor)
- [x] **1.2 Competitor Teardown** (تحليل المنافسين) — `pl-design-motion` · Output + Summary: `claude/Step-1.2-Competitor-Teardown.md` (steal / avoid / beat; 4 proposals approved in principle)
- [x] **1.3 Positioning & Messaging** (التموضع والرسائل) — `pl-content-qa` · Output + Summary: `claude/Step-1.3-Positioning-Messaging.md` (approved in principle; tagline "Science in focus.")

## Phase 2 — Structure (الهيكل) ✅
- [x] **2.1 Sitemap & User Flows** (خريطة الموقع ورحلات المستخدم) — `pl-design-motion` · Output + Summary: `claude/Step-2.1-Sitemap-User-Flows.md` (6 pages + event detail view; 5 flows, all ≤ 3 steps; proposals P1–P7)
- [x] **2.2 Data Model — Final** (نموذج الداتا النهائي) — `pl-events-data-wp` · Output + Summary: `claude/Step-2.2-Data-Model-Final.md` (8 content types on SCF; country-local time; no coordinates; phones/links as repeaters; built-to-grow plugin). Pack regeneration deferred → Reveal point.
- [x] **2.3 Page Content Outlines** (محتوى كل صفحة) — `pl-content-qa` · Output + Summary: `claude/Step-2.3-Page-Content-Outlines.md` (35 sections across 7 templates, each with purpose, source and empty state; H1 + meta per page)

## Phase 3 — Design (التصميم)
- [x] **3.1 Wireframes** (الـ wireframes) — `pl-design-motion` · Output: Design canvas https://claude.ai/artifact/1Pp9xGiZ8TvyXsHTWWq8Wh (page "✅ Final — D × B") · Summary: `claude/Step-3.1-Wireframes.md` (Final approved by Sara 4 Nov 2026; rule: familiar controls only; video hero about PL; Events Grid/List + labelled filters)
- [x] **3.2 Design System** (الـ design system) — `pl-design-motion` · Output: Design System "PL Events" https://claude.ai/artifact/QfopWoDHG2RSWUSk2VWayR · Summary: `claude/Step-3.2-Design-System.md` (Montserrat; portfolio palette ink / navy / blue #2C4894 on warm paper; 14 components; wrapping rules; Elementor Globals mapping)
- [x] **3.3 Motion Spec** (مواصفات الحركة) — `pl-design-motion` · Output + Summary: `claude/Step-3.3-Motion-Spec.md` (3 signatures: iris hero · viewfinder on the card in focus · story map → explorer; Full / Light / Reduced contexts; transform-only iris; no motion on reading text; Lenis on Home + About only)
- [x] **3.4a Hi-fi** (التصميم عالي الدقة) — `pl-design-motion` · Output: Design canvas https://claude.ai/artifact/4c4cCfC1k1Wced3QKYW13T · Summary: `claude/Step-3.4a-Hi-fi.md` (approved by Sara 4 Nov 2026; tabs All · Live now · Upcoming · Past, default All, one continuous grid; no favourites / My list; header CTA Portfolio + Home link; real portfolio photos; typographic event posters)
- [x] **3.4b HTML Prototype v1** (البروتوتايب الكامل) — `pl-design-motion` · Output: https://claude.ai/artifact/1CYF4Vfn6i1etX4UELAa8Z (v1.7, public) + data `claude/pl-data.json` · Summary: `claude/Step-3.4b-Prototype-v1.md` (approved by Sara 5 Nov 2026; decisions P1–P12: closing band instead of [pl_help], Lenis Home only, About sticky map + chapters (no pin), headline "Your science. Our stage.", stats/About H1 in blue pills + viewfinder, cinematic pass, Services zigzag, next-congress strip)

## Phase 4 — Build (البناء)
> Built in **Claude Code on the web** (Sara hands-off, browser only): private repo `SaraAmrousy/pl-events` (`pl-events-core`, `hello-pl-child`, data JSON, Elementor templates). Deploy to Sara's staging `saraamrousy.net/pl` on her cPanel host driven by Claude Code through the cPanel API token + WordPress REST API over HTTPS; content seeded by the plugin importer, never a DB dump. Plan: `docs/Step-4.0-Setup-Plan.md`.
- [x] **4.0 Setup** (التجهيز) — ✅ 5 Nov 2026 · Summary: `docs/Step-4.0-Setup.md` (one-command deploy proven with `pl-hello`; noindex; password removed by Sara; cron */15) · Sara + Claude Code · Sara (browser only): GitHub repo + Claude Code web environment + 1 cPanel API token · Claude Code: sandbox test WP; free plugins on staging (Elementor, SCF, UAE HFB, Forminator, Rank Math); HTTPS deploy; staging `saraamrousy.net/pl` noindex + password · Plan: `docs/Step-4.0-Setup-Plan.md` (S1–S8)
- [x] **4.1 pl-events-core Plugin** (البلجن) — ✅ 5 Nov 2026 · on staging + seeded (15 / 7 / 5 checked live); editor test passed in the sandbox · Summary: `docs/Step-4.1-Plugin.md` · `pl-events-data-wp` · SCF; follow "Built to grow" (2.2 §8); test page + CPT sharing the `events` slug; new pieces from 3.1/3.4a/3.4b (`[pl_live_ribbon]`, `[pl_next_event layout="strip"]`, `[pl_company field=…]`, `[pl_stats layout="line|sponsor_line"]`, calendar search/Type/Grid–List, tabs All · Live now · Upcoming · Past with `default` attribute, `[pl_story layout="sticky explore"]`, `[pl_services layout="zigzag"]`, event-page mini map). Seed from `claude/pl-data.json`. **No `[pl_help]` (3.4b P1), no saved-list module (3.4a H4).**
- [x] **4.2 Child Theme & Global Styles** (الثيم والستايل العام) — ✅ 5 Nov 2026 · Summary: `docs/Step-4.2-Child-Theme.md` (prototype CSS built into the theme, self-hosted Montserrat, 5 motion modules, Elementor Globals, `[pl_hero]`) · `pl-events-data-wp` · Tokens + classes from the 3.2 Design System (+ 3.4a H7 button fix) + motion modules from 3.4b §5 (no ScrollTrigger pin; Lenis Home only; `@view-transition`); confirm Elementor Free custom Global Colors
- [x] **4.3 Header & Footer** (الهيدر والفوتر) — ✅ 5 Nov 2026 · Summary: `docs/Step-4.3-Header-Footer.md` (UAE HFB templates with `[pl_site_header]` / `[pl_site_footer]`; sticky; keyboard-tested) · `pl-events-data-wp` · Header: Home · Events · Services · About · Careers · Contact + Request a proposal + Portfolio (3.4a H5–H6)
- [ ] **4.4 Pages in Elementor** (بناء الصفحات) — ⏳ built + live; LiteSpeed Cache on (purge from pl-events-core, tested); hero fix ✅ 6 Nov (media fills the hero at every size, tested at 6 sizes × 3 motion contexts) · image fit ✅ 6 Nov (every image in a fixed frame: photos cover, logos contain, crops + srcset, soft size note in wp-admin, safe SVG — `docs/Step-4.4-Image-Fit.md`) · **open: Sara's iPhone/Android check result** · Summary: `docs/Step-4.4-Pages.md` · `pl-events-data-wp` · Pages exported as Elementor template JSON by Claude Code and imported (Elementor Free Templates › Import — confirm the JSON format on the installed version); tested locally, then on the staging folder (noindex); confirm Elementor Free background video (self-hosted MP4 + poster)
- [x] **4.5 Forms** (الفورمز) — ✅ 6 Nov 2026 · authenticated SMTP (WP Mail SMTP + mailbox pl-staging@), both forms delivered to Sara's inbox, CV attached; CV direct URLs blocked · Summary: `docs/Step-4.5-Forms.md` · Summary: `docs/Step-4.5-Forms.md` (Forminator Contact + Careers/CV, topic+office pre-fill confirmed in Free, routing by office, staging mail → Sara only with a hard safety net) · `pl-events-data-wp` · Confirm Forminator Free URL pre-fill for topic/office (2.1 P4)

## Phase 5 — Content & SEO (المحتوى والـ SEO)
- [x] **5.1 Final Copy** (الكتابة النهائية) — ✅ 6 Nov 2026 · 22 URLs read; 3 misleading/internal sentences + 4 minor copy fixes live; remaining TBC/Sample listed for PL · Summary: `docs/Step-5.1-Final-Copy.md` · `pl-content-qa`
- [ ] **5.2 Event Data Entry** (إدخال الداتا) — PL editor, Claude supports
- [x] **5.3 SEO Setup** (الـ SEO) — ✅ 6 Nov 2026 · live on staging: 7 page + 15 event titles/descriptions (unique, ≤ 155), Organization (2 offices) + Event schema, site name "PL Events", sitemap ready (not submitted, IndexNow off + blocked), noindex kept · 301 map `deploy/launch/redirects.csv` (27 rows: 15 × 301, 10 × 410, 2 keep; 3 old slugs from the live sitemap at launch) + checker, **applied only at launch** · Summary: `docs/Step-5.3-SEO.md` · `pl-content-qa`

## Phase 6 — QA & Launch (المراجعة والإطلاق)
- [ ] **6.1 Independent QA** (المراجعة المستقلة) — `pl-content-qa`
- [ ] **6.2 Editor Handover** (تسليم الموظف) — `pl-content-qa`
- [ ] **6.3 Launch Checklist** (الإطلاق) — `pl-events-data-wp` · Must include: move from the staging folder to plevents.org, **remove noindex** ("Discourage search engines" off), apply the 301 map, real server cron for WP-Cron + check the hourly status check + cache purge runs (2.2 D9) · 301: import `deploy/launch/redirects.csv` into Rank Math › Redirections, fill the 3 TBC slugs from the live `wp-sitemap.xml` (read only), then `python3 deploy/launch/check-redirects.py https://plevents.org` = 0 failed

## Phase 7 — After Launch (بعد الإطلاق, optional)
- [ ] **7.1 Monthly Content Check** (مراجعة شهرية) · Also review the home meta description's region wording yearly
- [ ] **7.2 Calls for Submissions Page** (صفحة التقديمات, if needed)
- [ ] **7.3 Congress Case Studies** (صفحات الكيس ستادي)

---

### Reveal point (لحظة المفاجأة)
After **3.4** is approved: **make a clean reveal copy of the prototype (no Prototype controls)**, **regenerate the owner pack** to the Step 2.2 §7 "Pack v2" columns (attach `PL-Data-Sheet-Step-0.2.xlsx` + `PL-Website-Data-Pack.xlsx`; update the counts in both messages), then show the prototype to PL's owners, then send the **0.3** pack (`claude/Step-0.3-Owner-Request-Pack.md` has both messages). Their data goes in through wp-admin from **5.2**. Raise the two privacy issues from Step 1.1 (B2, B3) first thing. Also ask: official logo vectors + any brand guide (3.2), 30–60 s of real congress video for the hero showreel (3.1), office opening hours (3.1), memberships/awards (with proof links), server cron on the host, attendance per congress, any existing tagline, hidden event pages (see 1.1), privacy text + CV retention period (2.1), **registration state of upcoming events (3.4a)**, **society/sponsor logo files + permission (3.4a)**.
