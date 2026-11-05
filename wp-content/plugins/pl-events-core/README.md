# pl-events-core — PL Events content engine (Step 4.1)

Binding structure: `docs/Step-2.2-Data-Model-Final.md` §8. Summary: `docs/Step-4.1-Plugin.md`.

| Folder | What |
|---|---|
| `pl-events-core.php` | header, autoloader, activation hooks |
| `includes/` | `PL_Plugin` (boot, roles, `db_version` upgrades) · `PL_Fields` (field registry → SCF; reader/writer) · `PL_Data` (the one data layer, shape of `data/pl-data.json`) · `PL_Shortcodes` · `PL_Template` · `PL_Map` · `PL_Event_Page` (+ `.ics`) · `PL_Importer` · `PL_Admin` · `PL_Taxonomies` · `cli.php` |
| `includes/services/` | `PL_Dates` (status in country-local time) · `PL_Geo` (countries, cities, flags) · `PL_Phones` · `PL_Cache` (+ hourly status check) |
| `modules/{type}/` | one folder per content type: `module.php` + `fields.php` |
| `templates/` | HTML partials — override in `{theme}/pl-events/{name}.php` |
| `data/` | bundled countries + cities (built by `tools/build-geo.py`) |
| `assets/` | `js/pl-events.js` (enhancement only), `js/pl-admin.js`, functional CSS, flags (flag-icons, MIT), dotted map |
| `seed/` | symlinks to `data/pl-data.json` + prototype photos (the importer's default source) |
| `tests/` | `wp eval-file tests/test-data-layer.php` (sandbox only, not deployed) |

**Add a field:** one entry in `modules/{type}/fields.php`. **Add a type:** a new `modules/{type}/` folder. **Change markup:** copy a template into the theme. **Extend:** filters `pl_event_data`, `pl_event_actions`, `pl_stats_items`, `pl_fields_{type}`, `pl_template_file`, `pl_shortcode_atts`, `pl_data_all`, `pl_map_labels`, `pl_now`; actions `pl_status_changed`, `pl_cache_flushed`, `pl_purge_page_cache`.

WP-CLI: `wp pl import [--file=] [--overwrite]` · `wp pl export` · `wp pl stats` · `wp pl status-check`. REST: `GET /pl/v1/data` (public, site data) · `POST /pl/v1/import` (admins).
