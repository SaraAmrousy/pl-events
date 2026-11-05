# Elementor page templates (Step 4.4)

Built by `tools/build-elementor.py` — edit the script, not the JSON. One file per page:
`pl-home`, `pl-events`, `pl-about`, `pl-services`, `pl-careers`, `pl-contact`, `pl-privacy`.

- **Format:** Elementor template export (`version 0.4`, `type page`) — tested with Elementor 4.3.6 (`templates_manager->import_template`). Manual import: Templates › Saved Templates › Import Templates.
- **Structure:** each section = one full-width container (class `pl-raw`) with one **Shortcode** widget: `[pl_section]` + the prototype copy + `[pl_*]` shortcodes. Layout only; data comes from `pl-events-core`.
- **On staging** the theme writes them into the pages: `plt_provision()` (run by the deploy scripts). Existing pages are left alone unless forced.
- The extra `"pl"` key (slug, title, front page, privacy page) is read by the provisioning and ignored by Elementor.
