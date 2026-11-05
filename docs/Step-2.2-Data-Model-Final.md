# Step 2.2 — Data Model (Final, v4: simple, flexible where needed, built to grow): Step Summary (4 Nov 2026)

**Status: DONE (Sara, 4 Nov 2026).** The model is final. The column-by-column check and the **owner pack regeneration are deferred to before reveal day**. Skill: `pl-events-data-wp`.

## The three rules of this model (Sara, 4 Nov 2026)
1. **Easy.** The editor is not technical. If the site can work something out, the editor never types it: no coordinates, no timezones, no phone formats, no statuses.
2. **Free where it's needed, firm on the basics.** Everyday content never needs a code change: the editor can add as many phones, emails, links, photos, company figures and social links as needed, and add new specialties and series. Lists that change how the site counts or is laid out (event types, service groups, job types) are managed by Sara as admin in wp-admin, also without code. **Code changes are only for big changes** (a new feature, a new kind of content, a layout change).
3. **Built to grow.** The code is written so a new field or feature is *added*, not rebuilt (section 8).

---

## 0. Decisions

| # | Decision | Replaces | Why |
|---|---|---|---|
| D1 | **Live, status and countdown use the local time of the event's country.** The timezone comes from the country (PHP's built-in list); the editor never sees it. Online events use Cairo time. Agenda times, if ever shown, are local too. | Skill default "site timezone". Sara. | A Dubai congress goes Live at Dubai midnight. |
| D2 | **Country = dropdown with flag + name** (searchable, ready dataset). **City = plain text** with suggestions from cities already used. | v1 Locations list with lat/lng. | Easy. |
| D3 | **Coordinates are never asked for.** City matched to a bundled cities dataset; no match → pin on the country centre + an admin-only "Unmatched cities" list for Sara. | Locked decision 4 ("unknown city → manual lat/lng"). | No broken pins, no technical question. |
| D4 | **Field plugin = Secure Custom Fields (SCF)**, free on WordPress.org, maintained by the WordPress.org team. It includes **Repeater, Group, Flexible Content, Gallery and Options Pages** at no cost (Pro-only in ACF). | Locked decision 2 ("ACF Free"). | ACF Free has no Repeater, which forced fixed slots (e.g. exactly two phones). SCF removes those ceilings. Same API as ACF, so nothing else changes. SCF and ACF can't run together. Confirm the field set on the installed version in 4.1. |
| D5 | **All lists live in wp-admin, none in code, with two permission levels.** **Editor adds:** Specialties, Series (new ones appear with every new congress family). **Admin (Sara) only:** Event types, Service groups, Job types — the editor picks from them but can't create new ones. Each Event type carries two switches set by the admin: "Online (not on the map)" and "Counts as a congress". | v2 fixed dropdowns in code. Sara: "freedom without mess". | Types and groups change the counts and the layout, so they stay tidy (no "Congres" next to "Congress"); adding one is still a wp-admin task, never code. |
| D6 | **Company settings = one SCF Options Page** (PL Website › Company). | v2 plugin settings page. | Same editing experience as the rest, with repeaters. |
| D7 | **`event` CPT: no archive, rewrite slug `events`.** `/events/` = the Elementor page with `[pl_calendar]`; singles = `/events/{slug}/` (2.1 P1). | Skill "has_archive events". | An archive would hide the Elementor page. |
| D8 | **Field groups registered in PHP** by `pl-events-core`. **List values** (types, groups, etc.) are data, edited in wp-admin. | — | Editors can't break the form; list values stay editable in wp-admin (D5). |
| D9 | **Hourly status check:** if any status changed, clear caches and purge the page cache. JS re-checks status on screen. | 2.1 "daily purge". | Simple; never wrong on screen. |
| D10 | **Memberships & awards** type with a **required proof link**. Empty = proof strip hidden. | — | "Never faked" stays a rule. |
| D11 | **Phones = a repeater: add as many as needed.** Each row: label (optional, e.g. "Egypt", "Local organiser"), country code dropdown (flag + code, default 🇪🇬 +20), number, "On WhatsApp" switch. The site strips spaces and a leading 0 and builds the display ("+20 102 555 9003"), `tel:` and `wa.me` links. Same on events and offices. | v2 "two fixed slots". Sara. | International events list Egyptian + local numbers, with no ceiling. Fixes 1.1 M11. |
| D12 | **Soft limits instead of hard ones.** Text length = a counter and a yellow note ("long texts may wrap to more lines"), never a block. Only truth/safety rules block Publish (D13). | v2 hard 160/300-character limits. | Freedom; the design must handle any length without cutting with "…". |
| D13 | **What still blocks Publish (only these):** end date before start · Registration "Open" with no link · a link that is `#` or not a web address · missing country/city on a non-online event · award without a proof link. | — | These are the cases that made the old site misleading. |

### Ready data the plugin bundles (no work for the editor)
| Need | Source | Licence |
|---|---|---|
| Flags | **flag-icons** (SVG) | MIT |
| Country names, ISO codes, calling codes, centre points | **mledoze/countries** (JSON) | ODbL — attribution in a credits line |
| Timezone per country | PHP `DateTimeZone::listIdentifiers(PER_COUNTRY)` | built in |
| City coordinates | **GeoNames** cities (Europe, Africa, Middle East; ≥ 15,000 people) | CC BY 4.0 — attribution in a credits line |

---

## 1. Content types

| Type | Menu (PL Website › …) | Feeds |
|---|---|---|
| Event | Events | calendar, maps, story, stats, next event + countdown, upcoming cards, event page, add to calendar |
| Society | Societies | "Societies that trust us" band |
| Sponsor | Sponsors | sponsor strip |
| Office | Offices | Contact, footer, About, map origin |
| Service | Services | Services page, Home summary |
| Job | Careers | Careers page |
| Membership / award | Memberships & awards | proof strip (hidden if empty) |
| Company | Company (one screen) | company figures, footer, portfolio PDF, social links |
| Lists (wp-admin) | Specialties · Series (editor) · Event types · Service groups · Job types (admin) | filters, labels, counts, grouping |

Every item: **Show on site** (on by default; off = hidden everywhere, kept in the archive) and **Order** (drag). **Sample** = Sara-only switch, hidden from the PL Editor role.

---

## 2. Event — the form

| # | Field | Type | Required | Help text in wp-admin |
|---|---|---|---|---|
| 1 | **Event name** | title | ✓ | Full official name, e.g. "38th ESOIRS Annual Congress". |
| 2 | **Type** | pick one from the Event types list (admin adds new types) | ✓ | e.g. Congress, Symposium, Wetlab, Webinar. Missing a type? Ask the site admin. |
| 3 | **Specialty** | pick one or more (can add new) | ✓ | — |
| 4 | **Series** | pick one (can add new) | | The congress family, e.g. ESOIRS, so all its years are grouped. |
| 5 | **Start date** | date picker | ✓ | — |
| 6 | **End date** | date picker | ✓ | Same as start for one-day events. |
| 7 | **Country** | dropdown with flags | ✓ unless the Type is online | — |
| 8 | **City** | text with suggestions | ✓ unless the Type is online | In English, e.g. Alexandria. |
| 9 | **Venue** | text | | e.g. "Hilton Jeddah". |
| 10 | **Congress website** | link | | Paste the full link. |
| 11 | **Registration link** | link | | Where delegates register. |
| 12 | **Registration** | Not open yet · Open · Closed (default Not open yet) | ✓ | Choose "Open" only when the link works. |
| 13 | **More links** | repeater: label + link | | Anything else: programme, abstracts, hotel booking, online room. Shown on the event page. |
| 14 | **Phones** | repeater (D11) | | Add the Egyptian number and the local one for international events. |
| 15 | **Email** | email | | Event contact, if different from the office. |
| 16 | **Logo** | image | | Transparent PNG or SVG. |
| 17 | **Poster / photo** | featured image | | Used on the event page and when the link is shared. |
| 18 | **Photos** | gallery | | Optional, for past events. |
| 19 | **Short description** | text area (counter, soft) | | One or two sentences. |
| 20 | **Delegates** | number | | Only a real number; empty = nothing shown. |
| 21 | **Pin to Home** | switch (off) | | Turn on to show this event in the Home hero instead of the nearest one. |
| 22 | **Show on site** | switch (on) | | Off = hidden, not deleted. |

**Required: 7** (name, type, specialty, two dates, country, city). Everything else is optional; empty = hidden on the site.

**Gentle reminders (yellow, never block):** upcoming event with no website and no registration link · city not found (pin on country centre) · long text · Pin to Home on more than one event (the nearest pinned one wins).

### Worked out by the site
| Value | Rule |
|---|---|
| Status | Country local time: after end → **Past**; start…end → **Live**; before → **Upcoming**. |
| Register button | Upcoming/Live **and** Registration = Open **and** a link. Past = never. |
| Button text | "Register for {Series} {Year}"; no series → "Register". |
| Date text | "29–30 Jan 2027", "5 Feb 2026", "30 Oct – 1 Nov 2026". Always the year. |
| Countdown | Days to start at local midnight; hours in the last 48 h. |
| Map pin | City match → city point; else country centre. Types marked "Online" never pin. |
| Editions with PL | Published events in the same Series, by year. |
| Add to calendar | `.ics` + Google Calendar link (all-day). |
| Page URL | From series + year or the name; set at first publish, then fixed. |
| Home hero event | Nearest pinned upcoming/live event; else Live; else nearest upcoming. |

---

## 3. Other types

| Type | Fields (✓ required) | Notes |
|---|---|---|
| **Society** | Name ✓ · Logo ✓ · Website · Show on site | — |
| **Sponsor** | Name ✓ · Logo ✓ · Website · "Link the logo" switch · Show on site | Equal tiles. |
| **Office** | Name ✓ · Head office (switch) · Country ✓ · City ✓ · Address ✓ · **Phones** (repeater) ✓ at least one · Emails (repeater: label + email) ✓ at least one · Google Maps link · Opening hours | Head office = map arc origin. |
| **Service** | Name ✓ · Group ✓ (Service groups list, admin-managed) · Description ✓ (counter, soft) · Icon (optional) · Show on site | Full text always shown, never "…". |
| **Job** | Job title ✓ · Office (or Remote) · Job type (admin-managed list) · Description ✓ · Closing date · Show on site | Hides itself after the closing date. Empty state + CV form when none. |
| **Membership / award** | Name ✓ · Year · Logo · **Proof link ✓** · Show on site | None → strip not rendered. |

### Company (one screen)
| Field | Note |
|---|---|
| Founding year | Empty until PL confirms (2011 vs 2013). When filled, "{N}+ years" is computed every year. |
| **Company figures** | repeater: label + value (e.g. "Conferences" · "500+"). Add, edit, reorder or delete freely. Always shown static and labelled "Company figures" (locked decision 14). |
| Hero headline | text + emphasis part (Step 3.4b P4). |
| Portfolio PDF | Upload; size shown next to the link; warning over 5 MB. |
| **Social links** | repeater: paste the link; the icon is detected from the address (LinkedIn, Instagram, Facebook, YouTube, X, TikTok…), generic icon otherwise. |
| Show "Sample" tags | Sara-only. |
| Sample items left | Read-only count; launch blocked while > 0. |

---

## 4. Site-wide numbers (`[pl_stats]`, maps)
Events with Show on site = on: **countries** (distinct, non-online) · **cities** (distinct city + country) · **congresses** (Types with "Counts as a congress" on) · **events** (all) · **specialties** · **since** (earliest year). Today: 15 events · 5 countries · 7 cities.

## 5. Shortcodes (final — updated by 3.4b)
`[pl_upcoming]` · `[pl_calendar]` · `[pl_story]` · `[pl_stats]` · `[pl_company]` · `[pl_company_figures]` · `[pl_next_event]` · `[pl_logos type="society|sponsor|award"]` · `[pl_services]` · `[pl_offices]` · `[pl_careers]` · `[pl_live_ribbon]` + the event page template (with mini map) and `.ics`, from the plugin. Attributes per Step 3.4b §5. **No `[pl_help]`, no `[pl_hero_map]`, no saved-list module.** Output escaped and cached; caches cleared on any save and by the hourly check.

## 6. PL Editor role
Sees **PL Website** (all types and Company) and Media. Can add Specialties and Series; picks from Event types, Service groups and Job types (admin-managed). No pages, Elementor, menus, plugins, themes, users or settings. Events list: Start date · Country (flag) · City · Status badge · Registration · Show on site; newest first; filters by year and status.

---

## 7. Pack v2 — target columns (for regenerating the owner pack before reveal)
- **Events:** Event name · Type · Specialty (`;`-separated) · Series · Start date · End date · Country · City · Venue · Congress website · Registration link · Registration (Not open yet / Open / Closed) · More links (`label | link` per line) · Phones (`+code number` per line) · Email · Short description · Delegates · Pin to Home (Yes/No) · Show on website (Yes/No) · Logo file name · Poster file name · *source*
- **Company & Contacts:** Founding year · Company figures (label | value rows) · Social links · Portfolio PDF · per office: Name · Head office · Country · City · Address · Phones · Emails · Google Maps link · Opening hours
- **Memberships & awards:** Name · Kind · Year · Logo · **Proof link (required)**
- **Societies / Sponsors / Services / Open roles** as section 3.

## 8. Built to grow — how `pl-events-core` is structured (binding for 4.1)
Goal: adding a field or a feature means **adding a piece**, never tearing down and rebuilding.

| Principle | What it means in the code | Example of a future change |
|---|---|---|
| **One module per content type** | `modules/events/`, `modules/offices/`, `modules/jobs/`… each with its own CPT, fields and admin columns; a new type = a new folder, nothing else touched. | "Add a Speakers type" → new `modules/speakers/`. |
| **One field registry per type** | Fields declared once in a config array (`modules/events/fields.php`). From that one entry the plugin builds the admin form, validation, the data output and the JSON. | "Add a Hotel link field" → one entry in the array; it appears in wp-admin and in the data. |
| **One data layer** | All shortcodes and the JS read from `PL_Data` (e.g. `PL_Data::events([...])`), which returns the same shape as `data/pl-data.json`. No shortcode queries the database itself. | A new section reuses `PL_Data::events()`; the prototype and the site stay in sync. |
| **Shared services** | Status & dates, phones, countries/cities, cache — each in one class, used by every module. | Change the date format once, it changes everywhere. |
| **Templates overridable from the child theme** | Every piece of HTML (card, row, office card…) is a small template the theme can override (`hello-pl-child/pl-events/card-event.php`). | A redesign touches templates only; logic is untouched. |
| **Shortcode attributes instead of new shortcodes** | `limit`, `type`, `specialty`, `country`, `layout`, `show`, `default` on existing shortcodes. | "Only Iraq congresses on a landing section" → `[pl_upcoming country="IQ"]`, no code. |
| **Hooks for extension** | Filters/actions at key points (`pl_event_actions`, `pl_stats_items`, `pl_event_data`). | Add a "Watch live" button without editing core files. |
| **Stable field keys + versioned upgrades** | Field keys are never renamed (deprecated instead). A `db_version` with small upgrade routines moves old data when the model changes. | Splitting a field later doesn't break past events. |
| **Data survives the plugin** | Content is stored in standard WordPress posts, terms and meta; deactivating the plugin never deletes data. | Safe updates and rollbacks. |

## 9. Critique
1. **Editor-added Specialties and Series can still get duplicates** ("Retina" vs "retina"). Mitigation: existing values show first while typing, and new terms are matched case-insensitively before being created.
2. **Admin-only types mean the editor sometimes has to ask Sara.** That's the price of tidy counts.
3. **SCF instead of ACF:** SCF and ACF can't be active together. If PL ever wants ACF Pro instead, the code is compatible.
4. **Soft limits mean the design must survive long text** (3.2 wrapping rules).
5. **City typos can still split the count;** suggestions + "Unmatched cities" catch most.
6. **Hourly check needs WP-Cron;** use a real server cron (cPanel Cron Jobs on staging).
7. **Dataset attribution** (GeoNames, countries) goes in a credits line on the Privacy page.
8. **"Built to grow" costs a little more time in 4.1** than a quick plugin. It pays back the first time PL asks for a new field.
