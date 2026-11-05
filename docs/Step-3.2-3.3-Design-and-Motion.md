# Steps 3.2 + 3.3 — Design System and Motion Spec (condensed for the build)

Full versions live in the claude.ai Project (`claude/Step-3.2-Design-System.md`, `claude/Step-3.3-Motion-Spec.md`). **Where these differ from Step 3.4b, 3.4b wins** (no pin anywhere; Lenis Home only; page crossfade allowed). The working CSS for every token and component is in `reference/prototype/src/base.css` + `proto.css`.

## Design system (3.2)
- Design System artifact: https://claude.ai/artifact/QfopWoDHG2RSWUSk2VWayR
- **Font:** Montserrat only (400/500/700/800 + 500 italic). One emphasis word per heading in 500 italic.
- **Colours:** paper `#FAFAF7` · sand `#EFECE5` · surface `#FFF` · ink `#0A0D18` · ink-raised `#161C30` · navy `#142040` · **blue `#2C4894` (accent ~10%)** · blue-on-ink `#9DB2F2` · text-muted `#57534C` · live `#C62828` · open `#1B7A4A` · soon `#8A5A00`. All text pairs ≥ 4.5:1.
- **Buttons:** primary blue on light / white on ink; secondary outlined pill; ghost underlined link. Pills for everything pressable; radius 10 cards/fields, 6 date + logo tiles, 18 sheet/search/modal. Borders before shadows.
- **Type scale (desktop → mobile):** display-xl 120→64, display 88→52, h2 48→32, h3 28→22, h4 20→17, body-lg 20→17, body 17→16, label 12 uppercase +0.08em; tabular numerals.
- **Spacing 4/8:** 4 8 12 16 24 32 48 64 96; container 1200, margins 40/20, 12-col grid, gutter 24; touch targets ≥ 44.
- **Wrapping:** no ellipsis or line-clamp anywhere; equal-height cards with actions pinned to the bottom; `overflow-wrap:anywhere` for URLs/emails; `text-wrap: balance` headings, `pretty` body, max 68ch.
- **Elementor Globals:** Primary = blue, Secondary = ink, Text = ink, Accent = blue; fonts Primary Montserrat 800, Secondary 700, Text 400 17/1.65, Accent 700 12px uppercase.
- **Signature classes:** `pl-vf` / `pl-vf-on-ink` (viewfinder), `pl-cut-top` / `pl-cut-bottom`, giant "PL" (`aria-hidden`). Max one viewfinder per view; never two dark sections in a row.
- **14 components:** Button · Status badge · Date tile · Event card · Event row · Filter bar · Tabs & chips · Section header · Figure (computed vs company) · Office card · Form field (Forminator restyled) · Viewfinder frame · Live ribbon · Empty state.

## Motion (3.3, as amended by 3.4b)
- **Principles:** motion explains focus, never decorates · reading text never moves · one signature per page (Home iris, Events viewfinder, About story map → explorer) · three contexts via `gsap.matchMedia()`: **Full** (≥1024px, pointer fine, no reduced motion), **Light** (mobile/touch: opacity only, no blur, no Lenis, no autoplay), **Reduced** (`prefers-reduced-motion` or Save-Data: final states) · no-JS = finished page (`html.pl-motion` set before start states) · transform/opacity only; blur only for focus pull; no animated clip-path/size/position · entrances once; iris once per session · Elementor Entrance Animations off.
- **Tokens:** `--dur-instant .15s` · `--dur-fast .3s` · `--dur-base .6s` · `--dur-slow .9s` · `--dur-signature 1.2s` · `--ease-out cubic-bezier(.33,1,.68,1)` · `--ease-out-strong cubic-bezier(.22,1,.36,1)` · `--ease-in-out cubic-bezier(.65,0,.35,1)` · stagger .06 cards / .08 panels (max 6) · rise 16/24px · blur 8px H1 / 6px H2.
- **Iris hero:** transform-only wrapper scale 0.25→1 with inverse counter-scale; any input skips to end; Light/Reduced skip. Video ≤ 2.5 MB, muted, poster = LCP, Pause button, pauses off-screen.
- **Viewfinder (Events):** one bracket element glides to the card nearest the viewport centre (IO rootMargin -45% 0 -45% 0), 0.4s; follows keyboard focus; Light = jump; Reduced = none.
- **About story:** CSS-sticky map + IO chapters (no pin) — arcs draw, new cities ripple, camera move per chapter, tallies cut (never count-up); explorer step with country chips.
- **Patterns:** H1/H2 focus pull once · cards/tiles rise + fade · panels · "PL" outline draw · tab/filter crossfade 0.2s (grid keeps min-height) · bottom sheet slide · Live dot 3 pulses then still · header transparent → solid over the hero · film modal fade+scale.
- **Libraries:** GSAP 3.15.1 + ScrollTrigger (triggers only) + Lenis 1.3.27 (Home only), self-hosted in `hello-pl-child/assets/js/vendor/` (copies in `reference/prototype/site/vendor/`), enqueued with `defer` only on pages with a motion hook. Hooks are CSS classes set in Elementor Free (`pl-m-title`, `pl-m-cards`, `pl-m-panels`, `pl-m-outline`, `pl-hero-iris`).
- **Budget (mobile 4G):** no motion JS on the critical path; CLS 0; passive handlers.
