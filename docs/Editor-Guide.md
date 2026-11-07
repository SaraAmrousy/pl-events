# PL Events website — Editor guide

> **New (Step 6.2b, 8 Nov 2026): the full guide now lives inside the website.** Sign in → **PL Website › Help**: 11 chapters, 50 tasks in simple Egyptian Arabic, each with a short video, the steps and a screenshot with numbered callouts. On every screen that has a task, the blue **?** button (bottom right) opens that task in a side panel. The one-page **cheat sheet** prints from there (**Print / Save as PDF**). This English page stays as the short reference for Sara; the employee uses PL Help.

For the PL staff member who keeps the website up to date. You don't need any technical skills: everything happens in **wp-admin** under **PL Website**, using forms. The site updates itself: counts, maps, "Next congress", past/upcoming status and the cache.

*Step 6.2 — written 6 Nov 2026. Sign-in address: **`https://plevents.org/plevents/`** (staging: `https://saraamrousy.net/pl/plevents/`). Bookmark it: `/wp-admin` and `/wp-login.php` show "Page not found" until you're signed in, by design. Once signed in, wp-admin works as usual. After 5 wrong passwords, sign-in from your connection pauses for 15 minutes; use "Lost your password?" on the same page if needed. Your account has the **PL Editor** role.*

---

## 1. What you see
After logging in you see only what you need:
- **PL Website** — everything on the site: events, offices, societies, sponsors, memberships, careers, services, company details.
- **Media** — photos, posters, logos, PDFs.
- **Profile** — your name and password.

**PL Website › Overview** shows the live numbers (events · congresses · countries · cities · since …, plus Live / Upcoming / Past), so you can check a change at a glance.

> You can't break the design: pages, menus and layout aren't in your menu. If something on a page needs a new *section* (not new data), ask Sara.

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
| More links · Phones · Email | Optional extras on the event page (programme, abstracts, local contact). | |
| **Delegates** | A real number only, if PL has it. | |
| **Short description** | One or two plain sentences. | |
| **Logo** | The congress logo: transparent PNG or SVG (see §5). | |
| **Poster / photo** | The poster or a photo (see §5). Without one, the site draws a clean typographic poster by itself. | |
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
Only types with **"Counts as a congress"** switched on are counted in the "{n} congresses" line. Today that's *Congress* and *Conference* (decision 6 Nov 2026). Event types are managed by the administrator (Sara).

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
Every image sits in a fixed frame, so any size *fits*. Small images look soft, though. Under each image field the site says what it needs, and shows a **yellow note** if your image is smaller. The note never blocks publishing.

| Image | Frame | Upload at least |
|---|---|---|
| Event poster / photo | 16:9 on cards, 16:10 on the event page (cropped from the centre) | **1280 × 800 px** |
| Event photos (gallery) | 4:3 | 960 × 720 px |
| Service photo | 4:3 | 960 × 720 px |
| Wide photo (About) | 21:9 (4:3 on phones) | 2048 × 878 px |
| Showreel stills / poster | Full-screen hero | 1920 × 1080 px |
| Logos (event, society, sponsor, membership) | Shown whole, never cropped | **SVG**, or PNG ≥ 400 × 160 px with a transparent background |

- Keep the important part of a poster **in the middle**: the edges may be cropped.
- **Alt text:** in Media, fill **Alternative text** with what the photo shows ("Delegates at the ESOIRS 2026 opening session"). Leave it empty for logos and posters; the site names them already.
- SVGs with scripts or outside links are refused at upload. Export a plain SVG, or use a PNG.
- File size: under ~2 MB per image. The site makes the small copies by itself.

---

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
- **Fixed for you:** `www.site.org` → `https://www.site.org` · spaces, dashes and a leading 0 in phone numbers · a code typed into the number (`+971 55…`) moves to **Country code** · making an office the head office switches the old one off · an office with no name takes its city.
- wp-admin can't edit code files (`DISALLOW_FILE_EDIT`).

## 7. If something looks wrong
| You see | Do this |
|---|---|
| A change doesn't show | Wait a minute and reload. The site refreshes its cache by itself on every save. Still wrong → tell Sara. |
| An event shows the wrong status | Check its **dates** (start/end) and **Country** (the status uses the country's local time). |
| "Register now" is missing | Registration must be **Open** *and* the **Registration link** must be filled in. |
| A yellow image note | Upload a bigger image if you have one (see §5). It's fine to publish meanwhile. |
| Anything else | Screenshot + the page address → Sara. |
