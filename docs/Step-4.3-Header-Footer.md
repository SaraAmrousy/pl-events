# Step 4.3 — Header & Footer: Step Summary (Thread 7, 5 Nov 2026)

**Status: ✅ DONE.** DoD "sticky, responsive and keyboard accessible" — tested in the sandbox and live on `saraamrousy.net/pl` (evidence below).

## How it is built
- **UAE Header Footer Builder** holds two templates, **"PL header"** and **"PL footer"**, display rule **Entire Website**, all users. Each contains one Elementor **Shortcode** widget: `[pl_site_header]` / `[pl_site_footer]` (theme, `inc/chrome.php`). They print the prototype markup exactly (same classes), so the transparent-over-hero, sticky and mobile-menu behaviour from the prototype carries over.
- **Links** = WordPress menus (Appearance › Menus): **"Main menu (header)"** = Home · Events · Services · About · Careers · Contact; **"Footer menu"** = Events · Services · About · Careers · Contact. Created by `plt_provision()`; editable in wp-admin. With no menu assigned, the six pages are linked by slug.
- **Data from the plugin:** Portfolio button (PDF + size in its accessible name), Request a proposal (`/contact/?topic=proposal`), **live ribbon** (`[pl_live_ribbon]`, only while an event is live), footer offices (`[pl_offices layout="compact" hide_on="contact"]` — hidden on Contact, 3.4b P11), tagline "Science *in focus.*" from Company, social links, © year, Privacy link (when the Privacy page is published), dataset credits.
- **Provisioning** is code: `plt_provision()` (theme `inc/provision.php`) creates the HFB templates and menus once and never overwrites edits made in wp-admin. Run on staging through the deploy runner.

## Decisions
| # | Decision | Why |
|---|---|---|
| 4.3-1 | HFB places the header/footer; **the markup is a theme shortcode inside an Elementor Shortcode widget**, not HFB's Navigation/Logo/Button widgets. | HFB widgets print their own markup and classes; rebuilding the prototype's transparent→solid header, sticky filter offset and dialog menu on them would mean fighting their CSS. Links stay editable (WP menus); placement stays in HFB. |
| 4.3-2 | **`#masthead` is the sticky element** (HFB prints it straight in `<body>`); HFB's own `.ehf-header #masthead{position:relative}` is overridden. On Home the inner `.topbar` goes `fixed` over the hero. | Sticky needs a tall parent; the prototype's `.topbar` sits in a parent its own height inside HFB. |
| 4.3-3 | **One skip link** — HFB's "Skip to main content" (→ `#content`). Ours removed. | Two skip links = a duplicate tab stop. |

## Tests (evidence)
- Desktop 1280 vs prototype: header and footer identical (screenshots); Home header transparent over the hero, solid after it (`over` class + paper background); inner pages: header **sticky at top 0**, Events filter bar sticks **directly under it (77 px)**.
- Keyboard: first Tab = skip link → `#content`; then the page in order. Mobile menu (375): Enter opens, focus moves into the dialog, **10 × Tab stays inside**, Esc closes and returns focus to the menu button, `aria-expanded` follows, `aria-current="page"` on the current link (event pages mark "Events"). Desktop nav hidden < 1024 px, Portfolio + menu button shown (3.4a H5).
- Live ribbon: shown on a simulated 31 Oct 2026 ("Live now · 8th MEOM Conference · Dubai · day 2 of 3"), absent today.
- Staging: header + footer on `/events/` at 1280 and 375, sticky, overflow 0, 0 JS errors. **Bug found and fixed:** current-page detection doubled `/pl/` in the sub-folder.
- Debug log: no new warnings (the old Elementor activation warnings remain from 4.0).

## Open
- The menus list only the pages that exist; they fill in when 4.4 creates the pages (menus re-provisioned then).

## Critique
1. Header/footer markup lives in the theme, so changing the button labels means a code change (labels are fixed by 3.4a; links and order are editable).
2. HFB still loads its own small CSS/JS on every page (~10 KB). Acceptable for "free plugin does the placement".
