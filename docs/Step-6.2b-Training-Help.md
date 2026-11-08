# Step 6.2b — Training & Help (Thread 9b, 8 Nov 2026)

**Goal:** a PL staff member (Administrator) runs the whole site alone, from a guide in simple Egyptian Arabic that lives inside wp-admin. Three layers from **one source** (`tools/guide/guide.json`) + guardrails.

| Layer | What | Where |
|---|---|---|
| 1. In the screen | Guardrails: block / warn / fix with a one-line "how to fix it" | `pl-events-core` (`PL_Guardrails`, `pl-guard.js`) |
| 2. Next to the screen | Blue **?** button on every screen that has a task → that task in a side panel | `pl-events-core` (`PL_Help::panel`) |
| 3. The full guide | **PL Website › Help**: 11 chapters, 50 tasks, videos, screenshots, cheat sheet (A4) | `pl-events-core` (`PL_Help::page`) |
| Deck | PNGs, pairing shots, GIFs, guide.json | `docs/guide/` |

**Status: ✅ done, on staging, tested (8 Nov 2026).**
- Guardrails: sandbox **16/16** (`tools/guard-test.js --write`, data restored) · staging **11/11** (block cases only, nothing saved; DEOSS/MOCAT last modified 5 Nov, unchanged).
- Guide: **51 tasks** in 11 chapters (50 + the cheat sheet) · **59 screenshots** (incl. 1 mobile, 6 drawings) · **7 pairing shots** · **38 videos** (26–62 s, 0.14–0.7 MB each, 16 MB total, H.264 1280×800) · 4 GIFs · cheat sheet PDF **1 × A4**.
- Recorder: whole run ≈ 45 min, **data restored after every task**, proof at the end: DB content fingerprint before = after (`c95e3721…`, `749710ab…` on later runs).
- Staging: admin-check **31/31** (17 old + 14 new: Help, search, 52/52 pictures, video 206 Range, no autoplay, print button, media hidden signed out ×2, "?" on 5 screens, Esc/focus, file editor off) · click sweep **396/396** · debug log: 0 lines from our code.

## 1. Guardrails (pl-events-core)
| Rule | Type | Message (English, wp-admin) |
|---|---|---|
| Registration **Open** without a link | **Blocks** Publish/Update | "Registration is "Open", so the site needs the registration link. Paste it here, or set Registration to "Not open yet"." |
| End date before Start date | **Blocks** | "The end date is before the start date. Pick an end date on or after the start (the same day for a one-day event)." |
| A link that isn't a web address (e.g. "coming soon") | **Blocks** | "This doesn't look like a web address. Paste the full link from the browser's address bar, e.g. https://www.example.org" |
| Link without https (`www.site.org`) | **Fixed** (browser on blur + server on save) | "Added https:// at the start, so the link opens." |
| `http://` link | Warns | "…not secure. If the site opens with https://, use that instead." (Decision: warn, don't block, because some society sites are still http only.) |
| Event ended + Registration still Open | Warns (edit screen + Events list names them) | "This event has ended, but Registration is still Open. To fix it: set Registration to Closed and click Update." |
| Phone: spaces, dashes, brackets, leading 0 | **Fixed** on save | Preview under the field: "On the site: +20 102 555 9003 · WhatsApp: wa.me/…" |
| Phone typed with a code (`+971 55…`, `00966…`) while Country code says +20 | **Fixed**: the code moves to Country code | "Moved +971 to "Country code"." |
| Phone too short / long for its code (EG 8–10, AE/SA 8–9, TN 8, IQ 8–10, …) | Warns | "Looks short for +20 (5 digits after the code). Check the number, or choose the right country code." |
| Office: switching off the only Head office | **Blocks** | "The site needs one head office. To move it, open the other office and switch Head office on there; this one switches off by itself." |
| Office: a second Head office | **Fixed**: the old one switches off | "Dubai is now the head office. Alexandria was switched off, because the site has one head office." |
| Office: bin the head office while others exist | **Blocks** | "…is the head office, so it can't go to the bin yet. To fix it: open the office that should be the head office, switch Head office on…" |
| Offices list: 0 or 2+ head offices | Warns | how to fix |
| Image smaller than its frame | Warns (existing 4.4 yellow note) | unchanged |
| `DISALLOW_FILE_EDIT` | already **true** on staging (4.0); now also in the sandbox; added to 6.3 for live | — |

**Bugs found and fixed on the way**
- **Offices had no title** on staging and in the sandbox (the seed has no `name` for offices), so wp-admin listed them as "(no title)". Importer now uses the city; an office saved without a name takes its city.
- **Form recipients didn't follow the data.** Contact routing was refreshed only when the provisioning script ran, so an office email changed in wp-admin kept sending to the old address. Now saving an office (or binning/restoring one) or the Company page rewrites both forms' email settings at once.
- **Careers recipient had no editable field.** New **PL Website › Company › CVs go to** (empty = head office's first email). This also answers L4 without code.

## 2. Guide kit — `tools/guide/` (one source)
| File | Role |
|---|---|
| `guide.json` | **The source.** Chapters + tasks: id, chapter, title, why, steps[], tip, warning, screenshots[] (callouts: number + selector + short text), pair, video (actions), role, danger, screens (where the ? shows), links, placeholders, table, cheat (rank for the cheat sheet). |
| `run.sh` | **One command:** `bash tools/guide/run.sh` (options `--only=id,id`, `--no-video`). Saves the DB + uploads list → temporary QA admin + test data → records → restores → proves it (content fingerprint before = after) → builds. Sandbox only. |
| `record.js` | Playwright on the sandbox. Screenshots 1440×900 (mobile 390×844 @2x), callouts drawn **in the page** (circles #2C4894, white numbers, soft stroke). Pairing shots. Videos 1280×800 → MP4 H.264 `-preset veryfast`, 20 fps, CRF 30 (35 if > 2.9 MB), no sound: visible cursor, blue circle on every click, title card, Arabic captions as an HTML overlay (IBM Plex Sans Arabic, RTL), reading time ≈ 62 ms/character (min 2.3 s), WebVTT track + poster. Restores the DB and uploads after **every** task. |
| `overlay.js` | The in-page layer (cursor, click circle, captions, callouts, blur of every email / password / key / token). |
| `demo-data.php`, `demo-forms.js` | Test data for the screenshots (example.com sign-ups, one Contact message + one CV through the real forms, a small test image). Removed by the restore. |
| `diagrams/*.html` | Our own drawings (how the site works, data vs pages, where messages go, Mailchimp, Zoho, reading results). Not copies of their UI. |
| `build.js` | → `pl-events-core/help/guide.json` (what PL Help reads), `docs/guide/` (deck export + GIFs), sandbox media. |

**Re-generate after any change on the site:** start the sandbox (`bash deploy/sandbox/setup.sh`, then the server), then `bash tools/guide/run.sh`, then `deploy/deploy.sh plugin wp-content/plugins/pl-events-core` + `deploy/deploy-help.sh`.

## 3. Tasks (50 + cheat sheet)
| # | Chapter | Tasks (✱ = in the cheat sheet, ▶ = video, ⇄ = pairing shot, ⚠ = danger) |
|---|---|---|
| 0 | Welcome (أهلاً) | الموقع بيشتغل إزاي |
| 1 | Sign in (الدخول) | الدخول للوحة التحكم ✱ ▶ · نسيت الباسورد ▶ · الدخول وقف بعد 5 محاولات · تغيير الباسورد ▶ |
| 2 | Tour (جولة سريعة) | قايمة PL Website ▶ · صفحة Overview والأرقام ▶ ⇄ · الشرح ده جوه الموقع (PL Help) ▶ |
| 3 | Events (الإيفنتات) | إضافة إيفنت جديد ✱ ▶ · تعديل إيفنت ✱ ▶ · فتح وقفل التسجيل ✱ ▶ ⇄ · إخفاء إيفنت (من غير ما تمسحوه) ✱ ▶ · تثبيت إيفنت في الهوم ▶ ⇄ · صور الإيفنت ومقاساتها ▶ · إضافة Specialty أو Series جديدة ▶ · "يتحسب congress" إزاي ▶ |
| 4 | Offices & contact (المكاتب والتواصل) | تعديل رقم تليفون أو واتساب لمكتب ✱ ▶ ⇄ · رقم تليفون أو واتساب لإيفنت ▶ · تعديل إيميل مكتب ▶ · تعديل عنوان مكتب ولينك الخريطة ▶ · مواعيد العمل ▶ ⇄ · المكتب الرئيسي (Head office) |
| 5 | More content (باقي المحتوى) | الجمعيات العلمية (Societies) ▶ · الرعاة (Sponsors) ▶ · العضويات والجوايز ▶ · إضافة وظيفة جديدة ✱ ▶ · إقفال وظيفة ✱ ▶ · الخدمات (Services) ▶ · عنوان الهوم وأرقام الشركة ▶ ⇄ · الـ Portfolio PDF وفيديو الهوم ▶ |
| 6 | Pages (الصفحات) | إيه بيتعدّل منين · تعديل جملة في صفحة (بأمان) ▶ · القوايم (Menus) ▶ · المنطقة الممنوعة ⚠ |
| 7 | Forms (الفورمز والرسايل) | رسايل الفورمز بتروح فين · قراية الرسايل (Submissions) ✱ ▶ · فتح الـ CV ▶ · تصدير الرسايل (Export CSV) ▶ · عمل فورم جديد وحطه في صفحة ▶ · تغيير الإيميل اللي بتوصله الرسايل ▶ |
| 8 | Newsletter (النشرة والحملات) | المشتركين بييجوا منين ▶ ⇄ · قايمة Newsletter sign-ups ▶ · شيل حد من النشرة ✱ ▶ · Export CSV للمشتركين ▶ · حملة في Mailchimp بالـ tags · حملة في Zoho Campaigns بالـ tags · قراية النتايج |
| 9 | If something breaks (لو حاجة باظت) | شايفين كذا؟ اعملوا كذا · الممنوعات ⚠ · تكلموا مين |
| 10 | Cheat sheet (ورقة الغش) | ورقة الغش: أهم 10 مهام |

**Added beyond the minimum:** sign-in lockout explained, the head-office rule, event phone (as well as office phone), Pin to Home, the "where is it edited" map, "Validation failed" and phone warnings in the trouble table, PL Help itself (chapter 2).

**Mailchimp / Zoho (no accounts):** steps + our own drawing + official links + a "حطوا هنا screenshot من حسابكم" box for each step. Links checked by web search on 8 Nov 2026 (the sandbox can't open mailchimp.com or zoho.com; both domains were blocked for the fetch tool too, so they were confirmed as indexed official pages, not opened): Mailchimp *Getting Started with Tags*, *Send to Tags*, *Create and Send to a Segment*, *Create a Regular Email*, *About Email Reports*; Zoho *Contact Management FAQ* (tags + segments), *Create your first email campaign*, *Send and schedule email campaigns*, *An introduction to Reports*. **Sara: open each once from PL Help.**

## 4. PL Help in wp-admin
- **PL Website › Help:** hero (viewfinder corners) + search (Arabic-aware: أ/ا, ة/ه, ى/ي) + 11 chapter cards in order + each task as an accessible disclosure: why, video, steps, tip/warning, screenshot + numbered legend, pairing shot, official links, placeholders. Danger tasks marked red. Deep link `?page=pl-help&task=event-add`.
- **? button** (bottom right, 44 px, keyboard: also Shift+?) on 18 screen types (Add/Edit event, Events list, Offices, Company, Newsletter sign-ups, Forminator Submissions/Forms, Specialties/Series/Event types, Menus, Plugins/Themes/Settings, Pages, Profile, Careers, Media…). Side panel, non-modal, focus moves in, **Esc** closes and returns focus, videos pause on close.
- **Look:** Design System (paper / ink / #2C4894, borders before shadows, radius 10/18), Montserrat for Latin + **IBM Plex Sans Arabic** (OFL, self-hosted in the plugin, 3 × ~46 KB), `dir="rtl" lang="ar"` inside the English admin, English button names isolated (`<bdi>`) so they never flip. No emoji in the UI (WordPress swaps emoji for images from an outside CDN): icons are CSS.
- **Accessible:** keyboard, visible focus, captions (burned in + WebVTT track), **no autoplay** (`preload="none"`; videos start only on ▶), reduced motion = no panel slide / card lift.
- **Cheat sheet:** **Print / Save as PDF** → print stylesheet that prints only the cheat sheet, A4, one page (10 tasks × up to 4 steps).

### Where the videos live — decision
**`wp-content/uploads/pl-help/`, served through a capability-checked gate** (`admin-post.php?action=pl_help_media`, Range requests for seeking, `X-Robots-Tag: noindex`, `Cache-Control: private`), with **`.htaccess` "Require all denied"** on the folder.
- Not plugin assets: public URLs, and every plugin deploy would carry ~16 MB of video (30 MB with pictures).
- Not Media: the employee would see (and could delete) them, attachment pages are public, Rank Math could list them.
- Deployed separately with `deploy/deploy-help.sh` (only when the videos change).

## 5. Staging
| Item | Result |
|---|---|
| Plugin + theme | deployed with the safe swap; LiteSpeed + Elementor CSS + PL cache purged |
| Office titles | the 2 offices had no title → now "Alexandria" and "Dubai" (no visible change on the site; links keep their stable id) |
| Form routing | refreshed: contact → events@ (Alexandria) / dina@ (Dubai), careers → events@; staging still sends every email to Sara |
| Help media | `deploy/deploy-help.sh` → `uploads/pl-help/` (59 + 7 + 38). **Found:** LiteSpeed ignores `Require all denied` in that sub-folder (direct URL answered 200). **Fixed** with a rewrite rule in `/pl/.htaccess` (`# BEGIN PL help media`, same method as the CV block) → direct URL **403**, gate signed out **400**; the script refuses to finish if the folder isn't blocked. Backup of the old `/pl/.htaccess` kept in the session scratchpad; the block is 6 lines at the top. |
| `DISALLOW_FILE_EDIT` | already true on staging (4.0); checked by admin-check |
| Temporary QA user | `pl-qa-62b` created for the tests, **deleted**; users = claude-deploy, PL@Admin, sara |

## 6. Files
- **Plugin:** `includes/class-pl-guardrails.php` (new), `includes/class-pl-help.php` (new), `assets/js/pl-guard.js` (new), `assets/js/pl-help.js` (new), `assets/css/pl-help.css` (new), `assets/fonts/` (new: Plex Arabic 500/700, Montserrat, OFL), `help/guide.json` (built), `modules/events/module.php` (messages + https), `modules/company/fields.php` (CVs go to), `includes/class-pl-importer.php` (office title), `includes/class-pl-plugin.php`.
- **Theme:** `inc/forms.php` (CVs go to + live routing refresh).
- **Tools:** `tools/guide/*` (new), `tools/guard-test.js` (new), `tools/admin-check.js` + `tools/click-sweep.js` (Help, ?, media gate, file editor).
- **Deploy:** `deploy/deploy-help.sh` (new), `deploy/sandbox/setup.sh` (same plugins as staging + `DISALLOW_FILE_EDIT`).
- **Docs:** this file, `docs/guide/`, `Editor-Guide.md` (points to PL Help, guardrails, recipients), `Step-6.3-Launch-Checklist.md` (DISALLOW_FILE_EDIT, Help media, L4), `Roadmap-Checklist.md`.

## 7. Open questions for Sara
1. **Page wording lives inside Shortcode widgets as HTML** (e.g. `<h1 class="h1">Work behind the <span class="em">lens.</span></h1>`). The guide teaches a safe way (change only the words, Revisions to undo), but it's the most fragile thing the employee can touch. **Recommendation:** for the 5–6 sentences PL is likely to change (page intros), move them to fields (Company page or a small "Page texts" screen) in a later step. Your call.
2. **Mailchimp / Zoho links:** confirmed by search, not opened (both sites are blocked from here). Please click each once in PL Help › النشرة والحملات.
3. **Who to call (chapter 9):** written as "سارة"; the channel (WhatsApp/email) is **TBC**: tell me what to write.
4. **Videos are of the sandbox** (same plugins and data as staging, test data marked "training"/example.com). If PL's live site changes (menus, plugins), re-record with one command before the handover.
5. Videos run **26–62 s** (8 between 26 and 29 s, `event-add` 62 s): the target was 30–60. I didn't pad them.
