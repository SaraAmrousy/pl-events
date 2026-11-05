# 301 sources — extracted from Step 1.1 (Current Site Audit) and Step 2.1 (Sitemap & User Flows)

Copied from the claude.ai Project (5 Nov 2026) so the 301 map in `deploy/launch/redirects.csv` can be filled. The full summaries stay in the Project (`claude/Step-1.1-Current-Site-Audit.md`, `claude/Step-2.1-Sitemap-User-Flows.md`). Old URLs are on **plevents.org** (never touch it — read only, from these lists).

## Step 1.1 — "301 map for Step 5.3 must cover"
`/about-us/`, `/our-events/`, `/career/`, `/contact/`, `/our-services/`, `/event/*`, `/author/*`, all orphan event pages, `/test/`, `/upload/`, `/login/`, `/register/`, `/account/`, `/password-reset/`, `/reset-password/`, `/oral-presentation/`, `/el-safwa/`, and the Portfolio PDF URL.

Context from 1.1:
- **B2:** `/author/<slug>/` exposes a staff member's personal Gmail address (title + H1, and in `wp-sitemap-users-1.xml`). Author archives are **never** shipped on the new site → 301 to Home.
- **B3:** `/upload/` asks for an identity document. Registration pages `/delta-meeting/`, `/conference-of-ophthalmology-departments/`, `/pan-alex-2027/` also collect gender and birth date. Not migrated → 301.
- **M3 orphan event pages** (exist on the old site, not listed): `/pan-alex-2027/` (PAN ALEX OPHTHA 2027), Delta Meeting (`/delta-meeting/`), 8th Repro International Congress 2025, Alex Eye Hospital, 4th Annual Conference of Ophthalmology Departments (`/conference-of-ophthalmology-departments/`), Egyptian Lymphedema Conference, El Safwa (`/el-safwa/`). Exact slugs for Repro / Alex Eye Hospital / Lymphedema: **TBC** (from `plevents.org/wp-sitemap.xml` at launch).
- **M9 test/leftover pages:** `/test/`, `/login/`, `/register/`, `/account/`, `/password-reset/`, `/reset-password/`, `/oral-presentation/`, `/our-services/` (empty duplicate).
- **m3:** The Events Calendar event `/event/deoss-congress-2027/`.
- **B5:** Portfolio PDF `PL-Portfolio.pdf` (34.9 MB) in the old uploads folder — exact path TBC.
- Registration page `/registration/` (every old card's "Register Now" pointed there) — add.

## Step 2.1 — "Old → new URLs"
- `/about-us/` → `/about/`
- `/our-events/` → `/events/`
- `/career/` → `/careers/`
- `/our-services/` → `/services/`
- `/contact/` stays
- `/event/deoss-congress-2027/` and orphan event pages → their `/events/{slug}/` (P1 approved) when the event exists in the data, else `/events/?tab=past`
- everything else from the 1.1 list → Home, or **410** (gone) for test/login/account/upload pages

## Rules for the CSV
- Author archives, `/upload/`, `/login/`, `/register/`, `/account/`, `/password-reset/`, `/reset-password/`, `/test/`, `/oral-presentation/` → **410** (or 301 to Home if Rank Math Free can't do 410 — check).
- Orphan event pages whose event is not yet in the data → `/events/?tab=past` until PL adds them (5.2), then update to `/events/{slug}/`.
- Before launch: pull the live `plevents.org/wp-sitemap.xml` (read only) to catch any URL not listed here.
