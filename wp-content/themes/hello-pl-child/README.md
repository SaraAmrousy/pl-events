# hello-pl-child — PL Events look and motion (Step 4.2)

Child of **Hello Elementor**. Look + motion only; all logic and data are in `pl-events-core`.

| Path | What |
|---|---|
| `assets/css/pl.css` | **built** by `tools/build-theme-css.py` = self-hosted Montserrat + prototype `base.css` + `proto.css` (minus Prototype controls) + `assets/css/src/wp.css` (WordPress layer). Never edit `pl.css` by hand. |
| `assets/fonts/` | Montserrat variable 400–800 + 500 italic, latin, woff2 (SIL OFL) |
| `assets/js/core.js` | contexts Full / Light / Reduced, header over the hero, Lenis (Home, Full), module runner |
| `assets/js/patterns.js` | focus pull, cards/panels, focus sets, rack focus, arcs, outline, swap crossfades |
| `assets/js/iris.js` | Home iris hero, stills + Ken Burns, MP4 showreel (desktop), Pause — loads with `[pl_hero]` |
| `assets/js/viewfinder.js` | Events viewfinder — loads with `[pl_calendar]` |
| `assets/js/story.js` | About camera / arcs / ripples — loads with `[pl_story]` |
| `assets/js/vendor/` | GSAP 3.15.1, ScrollTrigger (triggers only, never pin), Lenis 1.3.27 |
| `inc/setup.php` | styles, font preload, body class `pl`, Hello CSS + page title off |
| `inc/motion.php` | conditional script loading + the pre-paint context script |
| `inc/elementor.php` | Elementor Free Global Colors/Fonts = design tokens (`plt_sync_elementor_kit()`) |

Override a plugin template: copy it to `pl-events/{name}.php` in this theme.
