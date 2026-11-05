# Image fit — every image fits its frame (Thread 8, 6 Nov 2026)

Follow-up to Step 4.4 (the hero bug had the same cause). **Status: ✅ live on staging.** Tested in the sandbox with odd images, then on staging with the real ones.

## The rule
| Kind | Frame | Fit |
|---|---|---|
| Hero (showreel stills) | the hero box | cover, centre |
| Event card poster | 16:9 | cover, centre |
| Event page poster | 16:10 | cover, centre |
| Services photos (Home numbered + Services zigzag) | 4:3 | cover, centre |
| About photo (`[pl_media frame="wide"]`) | 21:9 (4:3 on phones) | cover, centre |
| Event photos gallery (new, past events) | 4:3 grid (4 / 3 / 2 columns) | cover, centre; opens the full photo |
| Society / sponsor / membership logos | fixed tile (52 px logo height, 36 px for sponsors) | **contain**, never cropped, white tile with padding |
| Event logo (new) | event page: tile under the poster · card: white chip, bottom-right of the poster | contain |
| Elementor Image widget (anything an editor adds) | 3:2 | cover. Add the CSS class `pl-natural` to keep the image's own shape, or `pl-logo` for a logo |

- Specificity is (0,2,1) or higher, so it always beats Elementor's `.elementor img { height:auto }`, the cause of the hero bug.
- **Transparent PNG/WebP/GIF and SVG are always shown whole (contain)**, even in a photo frame. Transparency is read from the file header once, at upload (`_pl_alpha`). On a card, a transparent poster also hides the big poster word, so the two don't clash.

## Sizes + srcset (phones don't download the big file)
- New sizes, cropped on the server from the centre: `pl-poster-640/1280` (16:9), `pl-poster10-640/1024` (16:10), `pl-photo-480/960` (4:3), `pl-wide-1024/2048` (21:9), `pl-logo-480` (no crop). Each `<img>` gets the srcset of its own frame's ratio and a `sizes` that matches the layout.
- **WordPress bug avoided:** when an upload is smaller than a crop in one direction, WordPress cuts it to the wrong shape (a tall portrait became a "16:9" file of 600×720). `PL_Images::keep_ratio()` crops the largest centred area with the exact ratio instead.
- Checked on staging: a phone at 375 px (2×) loads `svc-booth-773x580.jpg` / `hall-wide-1024x439.jpg`, not the originals.

## wp-admin
- Every image field says what it needs, e.g. "Cropped from the centre to fit the event cards (16:9) and the event page (16:10). Best: at least 1280 × 800 px."
- If the chosen image is smaller, a **yellow note** appears under the field, with the real size and the suggested size. It updates as soon as another image is picked. It **never blocks** saving or publishing. SVGs are never flagged (they are vectors).
- **SVG upload** is now allowed (the logo field asked for "PNG or SVG", but WordPress blocked SVG). A file is **rejected, not "cleaned"**, if it has scripts, `on…=` handlers, `foreignObject`/iframe/embed, DOCTYPE/ENTITY, `javascript:`, or outside links. Tested: a file with `onload` + `<script>` was refused with a clear message.

## Tests (evidence)
- Sandbox, odd images: portrait 600×3000, panorama 5000×600, 300×200, 2400×1600, transparent PNG 900×300, tall transparent PNG 200×800, SVG; plus a malicious SVG (refused). Placed in every frame: cards, event page, gallery, services ×2, society + sponsor tiles, About wide frame, Elementor Image widgets.
- Automatic check at 375 / 768 / 1280, Reduced and Full:
  - every photo covers its frame exactly;
  - every logo stays inside its tile;
  - every image loaded;
  - no sideways scroll.
  - Results: **210/210** sandbox · **78/78** staging.
- Screenshots: `docs/img/image-fit-375.jpg`, `image-fit-768.jpg`, `image-fit-1280.jpg`, `image-fit-admin-note.png`.

## Fixed on the way
1. **Event page on phones scrolled sideways by 43 px** (older bug). The "editions with PL" rows had a fixed 200px date column. The date now gets its own line on phones.
2. **Elementor Image widget spill.** In a flex column the widget was sized from the image's own width, so the framed image ran onto the next widget. Fixed with `width:100%` (an editor's own width setting still wins).
3. `[pl_media]` with an empty `src` showed a random Media Library image. It now shows nothing.
4. Elementor adds a second `loading="lazy"` to images that already have one. The duplicate is removed.
5. **Deploy hardening.** During this deploy, cPanel's extract failed silently once. The old plugin folder was already deleted, so for about 5 minutes uncached pages on staging ran without the plugin. Fixed by hand, then `deploy.sh` was changed:
   - the old folder is renamed (not deleted) until the new one is verified;
   - the extract is retried up to 3 times;
   - on failure the old folder is restored.
   - Also, every plugin deploy now clears the PL render cache. Without that, Home kept serving the old HTML after the deploy.

## Files
- `pl-events-core/includes/class-pl-images.php` (new): frames, sizes, ratio-safe crop, `img()`, transparency, admin note + live JS, safe SVG
- `class-pl-plugin.php` (boot), `class-pl-fields.php` (`frame` → instructions + note), `class-pl-template.php` (`img()` → frames)
- Fields with a `frame`: events (poster, logo, photos), societies, sponsors, memberships (logo), services (photo), company (showreel poster/stills = hero)
- Templates: `card-event.php`, `event-page.php` (poster, logo tile, gallery, editions row), `logos.php`, `services-numbered.php`, `services-zigzag.php`
- Theme: `assets/css/src/wp.css` (Image fit block) → `pl.css`; `inc/blocks.php` (`[pl_media frame=…]`, empty-src guard); `tools/build-elementor.py` (About photo)
- Deploy: `deploy/deploy.sh` (safe swap + cache clear), `deploy/lib.sh` (`cp_rename`)

## On staging
- Plugin + theme deployed.
- Crop sizes regenerated for the 14 existing images (this only adds files).
- On About, only the photo shortcode was replaced inside the page.
- LiteSpeed purged.
- No content was changed; the odd test images exist only in the sandbox.

## Critique
1. Seed photos are smaller than the recommended sizes (e.g. `svc-booth` 886×580 against 960×720). Once PL's real photos arrive (5.2), the yellow note will say which ones are too small.
2. On phones the About photo switches from 21:9 to 4:3, so the sides of the hall are cropped. If PL wants exactly the same framing everywhere, that's a one-line change.
