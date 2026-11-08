# PL Events website — Editor guide

> **New (Step 6.2b, 8 Nov 2026): the full guide now lives inside the website.** Sign in → **PL Website › Help**: 11 chapters, 50 tasks in simple Egyptian Arabic, each with a short video, the steps and a screenshot with numbered callouts. On every screen that has a task, the blue **?** button (bottom right) opens that task in a side panel. The one-page **cheat sheet** prints from there (**Print / Save as PDF**). This English page stays as the short reference for Sara; the employee uses PL Help.

For the PL staff member who keeps the website up to date. You don't need any technical skills: everything happens in **wp-admin** under **PL Website**, using forms. The site updates itself: counts, maps, "Next congress", past/upcoming status and the cache.

*Step 6.2 — written 6 Nov 2026. Sign-in address: **`https://plevents.org/plevents/`** (staging: `https://saraamrousy.net/pl/plevents/`). Bookmark it: `/wp-admin` and `/wp-login.php` show "Page not found" until you're signed in, by design. Once signed in, wp-admin works as usual. After 5 wrong passwords, sign-in from your connection pauses for 15 minutes; use "Lost your password?" on the same page if needed. Your account is an **Administrator** (you see the whole of wp-admin, so read the danger zone in PL Help first).*

---

## 1. What you see
After logging in you see the whole of wp-admin (you're an Administrator). What you need is here:
- **PL Website** — everything on the site: events, offices, societies, sponsors, memberships, careers, services, company details, **Page texts**, and **Help**.
- **Media** — photos, posters, logos, PDFs.
- **Profile** — your name and password.

**PL Website › Overview** shows the live numbers (events · congresses · countries · cities · since …, plus Live / Upcoming / Past), so you can check a change at a glance.

> **Read the danger zone in PL Help before any change to the pages or the Plugins.** Page sentences are edited in **PL Website › Page texts**, never inside Elementor. A new *section* on a page (not new data) is a job for PL Team.

---

## 2. Add an event (the most common task)
**PL Website › ＋ Add event**

| Field | What to enter | Required |
|---|---|---|
| **Title** (top box) | The official name, e.g. *4th DEOSS Congress & 17th ATMO*. Write it as the society writes it. | ✅ |
| **Type** | Click, start typing, choose: Congress, Conference, Symposium, Forum, Course, Wetlab, Workshop, Webinar. | ✅ |
| **Specialty** | One or more (Ophthalmology, Vascular, …). To add a new specialty: PL Website › ↳ Specialties. | ✅ |
| **Series** | The recurring congress it belongs to (DEOSS, ASAIC, MEOM…). New series: PL Website › ↳ Series. Fill in the series' **Short name**: it's used on buttons ("Register now for DEOSS 2027") and on the poster. | |
| **Edition** | e.g. *4th*, *4th / 17th*. Leave it empty if you don't know. Never guess. | |
| **Start date / End date** | Click and pick from the calendar, or type it, e.g. *29 Jan 2027*. One-day event: the same date in both. | ✅ |
| **Country / City / Venue** | Country from the list; city and venue as written locally. A new city appears on the map and in the counts by itself. | |
| **Congress website** | The society's page for this congress. | |
| **Registration link** + **Registration** | The link, and its state: *Not open yet* / *Open* / *Closed*. **"Register now" appears only when the state is Open *and* there is a link.** When registration closes, switch to *Closed*. | ✅ (state) |
| **Pin to Home** | Only to force an event into the first spot on Home. Normally leave it off: Home already shows Live → Upcoming → Past. | |
| **WhatsApp for inquiries** | One number (Country code + Number). Doctors tap the green WhatsApp button on the card and message it with a ready text ("Hello, I'd like to ask about {event} ({dates}, {city}).", editable in PL Website › Company › **WhatsApp inquiry message**). **Empty = no button at all.** | |
| More links · Phones · Email | Optional extras on the event page (programme, abstracts, local contact). | |
| **Delegates** | A real number only, if PL has it. | |
| **Short description** | One or two plain sentences. | |
| **Logo** | The congress logo: transparent PNG or SVG (see §5). | |
| **Poster / photo** | The poster or a photo (see §5). Without one, the site draws a clean typographic poster by itself. | |
| **Image type** | **Auto** (default): an image far from 16:9 is a *Poster*. **Poster** = shown whole on a blurred copy of itself (no text is ever cut). **Photo** = fills the frame (edges may be cropped). The note under the field says what Auto picks. | |
| **Photos** | Gallery for past events (optional). | |
| **Show on site** | On by default. Off hides the event everywhere but keeps it. | |

Then click **Publish** (blue button, top right). The event appears at once in:
- Events (calendar + list);
- Home ("Our congresses." row, and "Next congress" if it's the next one);
- the counts ("13 congresses · 7 cities · 5 countries");
- the About map;
- its own page `/events/{name-year}/`, with "Add to calendar" and WhatsApp share.

**You never set "Past / Live / Upcoming".** The site works it out each day from the dates, in the event country's local time.

### Edit or remove an event
- **PL Website › Events** → click the name → change → **Update**.
- To take it off the site, switch **Show on site** off (better than deleting: it stays in the archive).

### Which events count as "congresses"
Only types with **"Counts as a congress"** switched on are counted in the "{n} congresses" line. Today that's *Congress* and *Conference* (decision 6 Nov 2026). Event types are managed in **PL Website › ↳ Event types** (a decision that changes a public number: agree it with PL Team first).

---

## 3. Other content
| What | Where | Notes |
|---|---|---|
| Offices | PL Website › Offices | Address, phones (with WhatsApp), emails, Google Maps link, **Opening hours** (still TBC — please fill), countries the office covers. Exactly one office is the **Head office**. |
| Societies | PL Website › Societies | Name, **Logo**, Website. Without a logo the name shows as a text tile. |
| Sponsors | PL Website › Sponsors | Name, **Logo**, Website, "Link the logo" (on = the logo links to their site). |
| Memberships & awards | PL Website › Memberships & awards | Name, Kind, Year, Logo, **Proof link (required)**: no proof link, no badge. |
| Careers | PL Website › Careers | Job title, Office, Job type, Closing date, Description. After the closing date the job disappears by itself. |
| Services | PL Website › Services | You can edit the text, photo and the list of services in a group. Adding or deleting a whole group is admin-only. |
| Company | PL Website › Company | Hero headline, founding year, **company figures** (label + value, PL's own claims), portfolio PDF, showreel video/poster/stills, social links. |

Order: every list has an **Order** number. Lower numbers come first.

---

## 4. Messages from the website
- **Contact form:** goes to the **first email** of the office the visitor chose (PL Website › Offices). Change that email and the form follows at once.
- **Careers form (with CV):** goes to **PL Website › Company › CVs go to** (empty = the head office's first email). Changes take effect at once.
- Don't change recipients inside Forminator's own settings: the site rewrites them from Offices and Company.
- CVs can't be opened from a public link. They're only in the email and in wp-admin (administrator).
- *(On the staging site every email goes to Sara only. That switch is removed at launch.)*
- **Newsletter sign-ups** (footer, event pages, the Contact box): the website sends the person **one** confirmation email. Only after they click **Confirm my subscription** are they added to both Zoho and Mailchimp, with their tags. Each sign-up is kept in **PL Website › Newsletter sign-ups** (administrators only) with its sign-up time, confirmation time, page and consent text, so you can answer "when did this person agree?".
  - **Someone asks to be removed** by phone or email: hover their row there → **Unsubscribe from all lists** (it removes them from both Zoho and Mailchimp).
  - **Unsubscribes made in Zoho or Mailchimp** are copied to the other account automatically.
  - **Backup:** Export CSV, on the same screen.
  - Campaigns are sent from Zoho/Mailchimp using the tags (`specialty-…`, `country-…`, `series-…`); see `Step-4.6-Newsletter.md` §4.

---

## 5. Images — sizes that look sharp
The numbers below come from one place in the plugin (`PL_Image_Spec`): the same numbers are under every image field, in the yellow "image is small" note, and in **PL Help › مقاسات كل الصور** (with safe-zone drawings). Measured on the real pages, 8 Nov 2026.

| Image | Upload | At least | Shape | Format | Max file |
|---|---|---|---|---|---|
| **Home hero photo / stills** (Company › Showreel poster / stills) | 2400 × 1350 | 1920 × 1080 | 16:9, cropped from the centre | JPG or WebP | 1 MB |
| **Home hero video** (Company › Hero showreel) | 1920 × 1080 | 1280 × 720 | 16:9, 10–20 s loop, **no sound** | MP4 (H.264) | 2.5 MB |
| **Event image — Photo** (Image type = Photo) | 1600 × 1000 | 1280 × 800 | 16:10; the card shows 16:9 (5% off top and bottom) | JPG or WebP | 2 MB |
| **Event image — Poster** (Image type = Poster/Auto) | the original design, e.g. 1080 × 1350 | 800 × 1000 | any; shown whole on a blurred copy | JPG, WebP or PNG | 2 MB |
| Logos (event, society, sponsor, membership) | 800 × 320 | 400 × 160 | shown whole in a tile | SVG, or PNG with a transparent background | 512 KB |
| Event photos (gallery) / Service photo | 1600 × 1200 | 960 × 720 | 4:3, cropped from the centre | JPG or WebP | 2 MB |
| Wide photo (About) | 2560 × 1097 | 2048 × 878 | 21:9 (phones: the middle 57%) | JPG or WebP | 2 MB |

- **Hero safe zone:** phones show only the **middle column (≈ 29% of the width)** of a 16:9 photo; the headline sits over the middle of the height. Faces and the key detail go in that middle column; no text in the photo.
- **Poster on the card:** the status badge covers the top ~16% and the date the bottom-left ~30%: put the congress name in the middle band. On the event page the whole poster shows.
- **Photo vs Poster:** a real photo → **Photo** (fills the frame). A designed image with text → **Poster** (nothing is cut). **Auto** decides from the shape (far from 16:9 = Poster).
- **Alt text:** in Media, fill **Alternative text** with what the photo shows. Leave it empty for logos and posters; the site names them already.
- SVGs with scripts or outside links are refused at upload. Export a plain SVG, or use a PNG.
- Resized copies are made by the site (PNG copies as WebP): upload the big file once.

## 5b. Page texts (since 6.1b round 7)
**PL Website › Page texts** holds every fixed sentence on the pages (titles, intros, section headings, buttons), page by page, each with where it appears on the site.
- The **Highlighted word** (italic, blue, e.g. *lens.*) has its own field: you never type HTML. HTML and [codes] are removed on save.
- A counter shows the length and roughly how many lines it makes on a phone; past the design's limit you get a yellow note.
- Empty field = the original text. **Reset to original** under each field.
- Saving refreshes the pages at once. SEO titles/descriptions stay in **Rank Math**.

## 5c. Website support (since 6.1b round 7)
**PL Website › Company › Website support — email / WhatsApp**: who the staff contact when they need help. Empty = the head office's first email and WhatsApp number. PL Help (chapter "تكلموا مين") turns them into **WhatsApp PL Team** / **Email PL Team** buttons with a ready message. Every email the site sends is signed "— PL Events Team", from "PL Events Team".

## 6. Rules that keep the site trustworthy
1. **Never invent data.** Unknown means leave it empty (or "TBC" where a field asks for text).
2. Dates always come from the date pickers, never typed into text.
3. Registration state: keep it honest: *Open* only when the registration page really takes registrations.
4. Names: the official congress name in the title; the short name in the Series.
5. No "Sample" items at launch. The administrator removes them (the counter on PL Website › Company shows how many are left).

---

## 6b. Guardrails (since 6.2b)
The forms stop the mistakes that would make the site wrong, and explain the fix in one line:
- **Blocked:** Registration *Open* without a link · End date before Start date · a link that isn't a web address · switching off the only **Head office** · binning the head office while other offices exist.
- **Yellow warning (still saves):** the event has ended but Registration is still *Open* · a phone number that looks too short/long for its country code · a small image · an `http://` link.
- The same phone guardrail applies to **WhatsApp for inquiries**.
- **Fixed for you:** `www.site.org` → `https://www.site.org` · spaces, dashes and a leading 0 in phone numbers · a code typed into the number (`+971 55…`) moves to **Country code** · making an office the head office switches the old one off · an office with no name takes its city.
- wp-admin can't edit code files (`DISALLOW_FILE_EDIT`).

## 7. If something looks wrong
| You see | Do this |
|---|---|
| A change doesn't show | Wait a minute and reload. The site refreshes its cache by itself on every save. Still wrong → PL Team. |
| An event shows the wrong status | Check its **dates** (start/end) and **Country** (the status uses the country's local time). |
| "Register now" is missing | Registration must be **Open** *and* the **Registration link** must be filled in. |
| No WhatsApp button on a card | Fill **WhatsApp for inquiries** in the event. Empty = no button, by design. |
| A poster's text is cut | Set **Image type** to **Poster** (or Auto). |
| A yellow image note | Upload a bigger image if you have one (see §5). It's fine to publish meanwhile. |
| A sentence on a page is wrong | **PL Website › Page texts** (not Elementor). |
| Anything else | Screenshot + the page address → PL Team (PL Help › تكلموا مين). |
