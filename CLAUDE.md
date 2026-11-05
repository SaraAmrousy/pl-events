# PL Events website — build repo

Read this first, then `docs/PL-Events-Project-Instructions.md` (the rules) and `docs/Roadmap-Checklist.md` (where we are). Those two files win over anything here.

## Who and how
- **Sara Amrousy** owns every decision. You build, test and critique.
- Reply in **Egyptian Arabic**; give every technical term in **English + Arabic** every time. Code, comments and site copy in English.
- Be blunt: name the problem, show the evidence, give the fix.
- Never invent event data, numbers or memberships. Unknown = **TBC**.
- **Never touch PL's live server (plevents.org).** You work in the cloud sandbox and deploy only to Sara's staging folder `https://saraamrousy.net/pl`. Never touch anything else on saraamrousy.net (root site, other folders, other databases).
- End of every step: tick it in `docs/Roadmap-Checklist.md`, write `docs/Step-X.Y-Name.md` (decisions, files, open questions), and end the reply with **"الخطوة الجاية: Step X.Y — Name"**. Remind Sara to upload the new/changed docs to the claude.ai Project.

## What we are building
WordPress + **Hello Elementor child theme `hello-pl-child`** + **Elementor Free** (no Pro features) + free plugins (SCF, UAE Header Footer Builder, Forminator, Rank Math) + our plugin **`pl-events-core`** that holds all logic and data.
- Plugin structure is binding: `docs/Step-2.2-Data-Model-Final.md` §8 (one module per type, one field registry, one data layer `PL_Data` returning the shape of `data/pl-data.json`, shared services, overridable templates, shortcode attributes, hooks, stable keys + `db_version`).
- Shortcodes, attributes and page mapping: `docs/Step-3.4b-Prototype-v1.md` §5. **No `[pl_help]`, no saved-list / favourites.**
- Status (Past / Live / Upcoming) is computed in the **event country's local time**, never typed.
- Server-rendered shortcodes; JS only enhances. No-JS = finished page.

## Visual reference
- `reference/prototype/site/index.html` = approved prototype v1.7 (serve it: `python3 -m http.server` inside that folder, open `/#home`). The yellow "Prototype controls" are not part of the site.
- `reference/prototype/src/` = its sources: `base.css` + `proto.css` (tokens + components — reuse class names), `views.js` (markup per section → becomes PHP templates), `data.js` (computed logic → becomes PHP services), `motion.js` (→ theme motion modules), `app.js`.
- `data/pl-data.json` = seed data (15 real events + offices, societies, sponsors, services, geo). Items with `"sample": true` are Sample.
- Design + motion rules: `docs/Step-3.2-3.3-Design-and-Motion.md`, `docs/Step-3.4a-Hi-fi.md`.

## Repo layout
```
CLAUDE.md
docs/                     project rules, checklist, step summaries (you write new ones here)
data/pl-data.json         seed data
reference/prototype/      approved prototype (site + src) — read only
wp-content/plugins/pl-events-core/    our plugin (you build it)
wp-content/themes/hello-pl-child/     our child theme (you build it)
elementor/                Elementor template JSON exports (Thread 7)
deploy/                   deploy scripts + .env (secrets, git-ignored)
```
The two `wp-content/` folders are linked (symlinked) into the sandbox WordPress. Never edit WordPress core or third-party plugins.

## Environments
- **Sara does not work by hand** and works **only from the browser** (Claude Code on the web, claude.ai/code; no desktop app, no local installs). You do all setup, build and deploy; she approves prompts and reviews on staging. Explain each command in one line before you run it.
- **Sandbox WordPress:** for tests only (PHP + SQLite integration + WP-CLI, set up by script each session). The plugin and theme folders are linked in.
- **Staging:** `saraamrousy.net/pl` on Sara's cPanel shared host. Drive cPanel with its HTTPS API using `$CPANEL_TOKEN` (environment variable — never print, log or commit it). Deploy over HTTPS (zip → cPanel upload + extract → WordPress REST API activate); SSH + WP-CLI only if it works from the sandbox. **Content is never pushed as a database dump** — the plugin importer seeds it from `data/pl-data.json`. Before any destructive action on staging, show Sara what it does and wait. Plan: `docs/Step-4.0-Setup-Plan.md`.

## Tests before saying "done"
PHP lint (`php -l`), WP debug log clean, the plugin activates/deactivates without data loss, the shortcode output matches the prototype at 375 / 768 / 1280, and reduced motion works. Show evidence (command output or screenshots), not claims.
