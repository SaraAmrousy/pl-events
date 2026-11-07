# Step 4.6 — Newsletter (Zoho Campaigns + Mailchimp)

**First built:** 7 Nov 2026 (round 3). **Redesigned:** 7 Nov 2026 (round 4), after the new information that PL runs campaigns from **both** Zoho and Mailchimp and will connect its current accounts.

**Status:**
- **Round 5 (7 Nov):** the decision on button pages is final; the rate limit reads `REMOTE_ADDR` only; the real-account test (§6) is **not done yet**: on staging there are no keys, no destination, 0 records and no webhook calls so far.
- Built, deployed to staging, and tested against mock providers: 44/44 flow checks, plus the click sweep.
- **Waiting:** Sara's free test accounts (Zoho + Mailchimp) for the real connection test.
- No PL account is connected, and none will be until the reveal.

## 1. Decisions
| Decision | Why |
|---|---|
| **Our own adapter, not Forminator's integration** | Forminator Free has Mailchimp but **no Zoho**, no queue, no consent record, and our footer and event forms aren't Forminator forms. The Contact form only uses Forminator for its UI; when the box is ticked, our hook does the rest. |
| **Destination "Both" is the normal setting** | PL sends campaigns from both accounts, so every sign-up goes to both lists. |
| **Double opt-in on the site, not at the providers** | One confirmation email from the site (through WP Mail SMTP). If Zoho and Mailchimp each did their own opt-in, people would get **two** confirmation emails and could confirm one list only. Only after the click is the person added to both lists as **subscribed**. |
| **The link opens a page with one button; the change happens on that click** — **decided by Sara, 7 Nov 2026: stays this way** for both confirm and unsubscribe (no one-click from the email) | Email security scanners (common at hospitals and universities) open every link in an email. If opening the link were enough, a scanner could confirm a subscription the person never agreed to. Same for unsubscribe links. |
| **Unsubscribes are kept in sync** | Mailchimp and Zoho webhooks tell the site, and the site removes the person from the other list too. The site's own unsubscribe link removes them from both. |

### Can Zoho take an already-confirmed contact? (checked in Zoho's docs)
Yes, with one condition.
- Zoho's developer docs ("Subscribe"): for a list with its **signup form enabled**, Zoho sends a confirmation email itself. For a list with the **signup form disabled**, the contact is added **without any confirmation**.
- So the website must add people to a Zoho list whose signup form is **off**, e.g. a dedicated "Website sign-ups" list. PL can send campaigns to several lists at once, so this costs PL nothing.
- If the website's list ever has its signup form on, Zoho answers "confirmation email sent". The site notices this and shows a warning in Settings › PL Newsletter.
- This was checked in the docs. It still needs to be seen working once with Sara's test account (§6).

**Mailchimp:** members are added with `status: subscribed`.
- If the audience has GDPR fields, the **Email** marketing permission is switched on too (the other permissions, such as ads, stay off).
- Someone who earlier unsubscribed in Mailchimp **can't** be re-subscribed through the API (Mailchimp's "Member In Compliance State" rule). For those rare people, the site falls back to Mailchimp's own re-confirmation email and notes it on the record.

## 2. The flow
1. **Form.** Footer, event page, or the Contact checkbox. The visitor clicks **Subscribe**.
   - Checks: same-origin, honeypot, valid email, at most 5 tries per hour per IP (the IP comes from `REMOTE_ADDR` only, same rule as the login limit: see 6.1b round 5).
2. **Saved on the site** (PL Website › Newsletter sign-ups, admins only), with:
   - the email, name and tags;
   - the consent record: sign-up time (UTC), page, form, and the exact consent text shown;
   - the status **awaiting confirmation**.
3. **One confirmation email** from the site.
   - Subject: "Confirm your subscription to PL Events updates".
   - A short plain English text and a **Confirm my subscription** button, plus the link written out for email apps that hide buttons.
   - The link is signed (HMAC-SHA256 with a key derived from the site's salts) and **expires after 7 days**.
   - The same address can't trigger a second email within 24 hours.
   - If the email can't be sent right now, it's retried by the hourly cron.
4. **The person opens the link** → a page in the site's own header and footer → they click **Confirm my subscription** →
   - the confirmation time is added to the consent record;
   - they're added to **every destination** as subscribed, **with their tags** (Mailchimp: tags + a note holding the consent record; Zoho: the contact + tags);
   - the page says "You're subscribed" and offers an **Unsubscribe** link.
5. **Provider down / no key / no destination yet:** the confirmed sign-up stays **queued**, and the hourly cron (`pl_nl_retry`) retries it up to 8 times. Zoho tags are retried for 7 days.
6. **Never confirmed:** deleted after 30 days. Without a confirmation there's no consent, so the address isn't kept.
7. **Bad or expired link:** a calm page that doesn't blame anyone.
   - Expired: "Confirmation links work for 7 days… sign up again and we'll send you a fresh link."
   - Tampered or cut by an email app: "This link isn't complete… sign up again" (or, for unsubscribe links: "write to us").
   - Nothing changes either way.

### Unsubscribe sync
| Where the person leaves | What the site does |
|---|---|
| Mailchimp (unsubscribe link in a campaign, or an admin) | The Mailchimp webhook (`unsubscribe`, `cleaned`) → local record **unsubscribed**/**bounced** → **Zoho**: unsubscribed. |
| Zoho (unsubscribe link, or a hard bounce) | The Zoho webhook → local record → **Mailchimp**: unsubscribed. Soft bounces are ignored. |
| The site's unsubscribe link (page + **Unsubscribe me** button) | Removed from **both**. |
| wp-admin › Newsletter sign-ups › **Unsubscribe from all lists** (when someone asks by phone or email) | Removed from **both**. |

- **No loops:** a second notice for someone already unsubscribed changes nothing. Mailchimp's webhook ignores changes made by API.
- **People PL added directly in Zoho or Mailchimp** (not through the website) are handled too: when they unsubscribe in one account, the site keeps a small record ("outside the website") and removes them from the other.
- **If the other provider is down,** the unsubscribe is retried hourly until it goes through.

### Webhook security
- Neither provider signs its webhooks, so each webhook URL carries a 32-character random secret, compared in constant time.
- A wrong or missing secret gets a plain 404.
- "Make a new webhook secret" in the settings page replaces it, if the URL ever leaks.

### Forms and tags
| Where | What | Tags |
|---|---|---|
| Footer (every page) | Email + **Subscribe**; "Choose topics (optional)" opens specialty checkboxes + a country select | `specialty-*`, `country-*`, `source-footer` |
| Event page | "Get updates on [series]" + email | `series-*`, `specialty-*`, `country-*` of that event, `source-event` |
| Contact | Checkbox "Send me updates about upcoming PL congresses", **unchecked by default** | `source-contact` |

- Every form has a visible label, a verb-first button, a privacy line, and loading, success and error states announced to screen readers.
- On staging every sign-up also gets the tag `pl-staging-test`.

## 3. Settings → PL Newsletter (wp-admin, admins only)
- **Destination:** Not connected yet · Zoho · Mailchimp · **Both** (normal for PL).
- **Warnings:**
  - With Both, a warning shows **only while a webhook isn't connected**: "Unsubscribes aren't synced yet…"
  - A second warning shows if Zoho ever sends its own confirmation (the list's signup form is on).
- **Provider settings:**
  - Zoho: data center, Client ID, Client secret, one-time grant code, List key.
  - Mailchimp: API key, Audience ID; the data center is read from the key.
  - **Test connection** per provider: it only reads the list name and adds nobody.
- **Unsubscribe sync (webhooks):** status per provider, **Connected / Not set**.
  - Mailchimp: **Connect Mailchimp webhook** adds it by API (checked against the audience).
  - Zoho: the URL to paste in Zoho. It shows Connected after Zoho's first call.
  - **Make a new webhook secret**.
- **Backup:** **Export all sign-ups (CSV)**, admins only, also on the Newsletter sign-ups list.
  - Columns: email, name, status, tags, form, page, consent text, sign-up / confirmation / left times, left via, Zoho and Mailchimp state.
  - Cells starting with `= + - @` are made safe so they can't run as Excel formulas.
- **Sign-ups list:** columns Email · Status (+ per-provider state) · Tags · Confirmed · Signed up; row action **Unsubscribe from all lists**.
- **Security:**
  - Keys are **encrypted** (AES-256-GCM) and not autoloaded.
  - Fields are **write-only** ("saved ••••"; an empty field keeps the key).
  - The Zoho grant code is exchanged once and never stored.
  - No key is ever printed or logged.

## 4. How PL segments and sends a campaign
**Tags (the same in both accounts):**
- `specialty-ophthalmology`, `specialty-dermatology`, …
- `country-eg`, `country-sa`, …
- `series-<short name>`
- `source-footer` / `source-event` / `source-contact`

**Mailchimp:**
1. Audience › All contacts › filter by tag, or Audience › **Segments** › Create segment (for example, tagged `specialty-ophthalmology` AND `country-eg`).
2. Campaigns › Create › Email.
3. Choose the audience, then **Send to: a segment or tag**.
4. Design the email, send a test, then send or schedule.

**Zoho Campaigns:**
1. Contacts › **Tags**, or Contacts › **Segments** › Create segment ("Tag is `specialty-ophthalmology`").
2. Campaigns › Create campaign › Email.
3. Recipients: the website list (+ PL's other lists if wanted) + the segment.
4. Content, test email, then send or schedule.

**Consent proof:**
- The site record: sign-up time, **confirmation time**, page and text.
- Mailchimp also has it as a note on the member.
- Answer "when did this person agree?" from PL Website › Newsletter sign-ups, or from the CSV.

## 5. Staging rules
- No PL account; only Sara's free accounts with **test lists**.
- Every sign-up is tagged `pl-staging-test`, and a staging banner shows on the settings page.
- The mail safety net stays on: **every** site email, including confirmation emails, goes to Sara, with "STAGING — on the live site this goes to: …" at the top.
- Destination on staging today: **None**. Confirmed sign-ups are queued and sent once a destination is set.

## 6. What Sara needs to do (wp-admin on staging only — never paste a key in the chat)
1. **Mailchimp (free):**
   - Create an account and an Audience (test list).
   - Profile › Extras › **API keys** › Create a key.
   - Audience › Settings › **Audience ID**.
2. **Zoho Campaigns (free):**
   - Create an account and a mailing list with its **signup form OFF**, then copy its **List key** (list › Setup).
   - At **api-console.zoho.com** › Add client › **Self Client**:
     - copy the Client ID + Client secret;
     - Generate Code, scope `ZohoCampaigns.contact.ALL,ZohoCampaigns.campaign.READ`, 10 minutes.
   - Note the data center (zoho.com = com, zoho.eu = eu, …).
3. Go to **saraamrousy.net/pl/plevents/** (the new sign-in address) › Settings › **PL Newsletter**:
   - paste the keys and Save (the Zoho grant code within its 10 minutes);
   - click **Test connection** for each;
   - choose Destination **Both**.
4. **Webhooks:**
   - Click **Connect Mailchimp webhook**; it should show Connected.
   - In Zoho: Settings › Developer Space › Webhooks › **Contacts** › Create, then:
     - paste the Zoho URL shown on the settings page;
     - method POST;
     - events **Unsubscribe** and **Bounce**;
     - choose the test list.
5. **Test:**
   - Subscribe from the footer with your own email.
   - Confirm from the email; you should be in **both** test lists with the tags.
   - Unsubscribe from a Mailchimp campaign/test email; you should disappear from Zoho too, and Zoho's webhook shows Connected after its first call.
   - Tell me when it's done, and I'll check the records. The first real Zoho webhook call also lets me confirm that Zoho's payload is read correctly; it's parsed defensively until then.
   - **Then the other direction:** unsubscribe from a Zoho test email (or Contacts › the contact › Unsubscribe) → you should disappear from Mailchimp too.

### What I check after Sara's test (prepared 7 Nov 2026)
- The record has a confirmed time and is `subscribed`, and both providers show `sent`.
- The tags are present in **both** accounts, read back by API.
- After the Mailchimp unsubscribe: the record is `unsubscribed via mailchimp` and the contact is unsubscribed in the Zoho list (read back).
- **The real webhook shapes:** the site keeps the last 3 payloads per provider with emails masked (`a•••@domain`, in the non-autoloaded option `pl_nl_hooks`). I compare the Zoho payload with the parser and then **tighten it to the real field names** (today it accepts any field holding an email + an "unsub"/"bounce" marker).
- The same check again for an unsubscribe made from the Zoho side.

## 7. Launch steps (also in 6.3 §4)
- [ ] L6 answered (below).
- [ ] Remove Sara's test keys.
- [ ] Connect **PL's** accounts in Settings › PL Newsletter (PL or Sara, in wp-admin):
  - Mailchimp: PL's audience.
  - Zoho: a list with the **signup form off** (a "Website sign-ups" list if their main list uses Zoho signup forms).
  - Click **Test connection**; Destination **Both**.
- [ ] **Connect both webhooks** on live: the URLs contain the live site's own secret. Check that both show **Connected**.
- [ ] The staging mu-plugin (`PL_NL_TEST_ONLY` → tag `pl-staging-test`, mail safety net) is **not** on live.
- [ ] **Mail for confirmation emails:** the live site sends them through PL's own mailbox, so set up SPF + DKIM + DMARC for that mailbox on plevents.org. Also authenticate plevents.org in **both** providers (Mailchimp: Domains › Authenticate; Zoho: Sender domains), or campaigns go to spam.
- [ ] Sender name and address in both providers ("PL Events", e.g. `news@plevents.org`), with PL's postal address in the campaign footer (required by anti-spam law).
- [ ] One real test: sign up → **one** confirmation email → confirm → in **both** lists with tags → unsubscribe in one → gone from the other. Then delete the test contact in both.
- [ ] Privacy page: name Zoho and Mailchimp as processors; say that sign-ups are confirmed by email, and that unconfirmed addresses are deleted after 30 days.
- [ ] Export a first CSV backup after launch week.

### L6 — questions for PL
1. Do **all** website sign-ups go to both accounts, or are some campaigns tied to a specific list (e.g. one list per specialty or per congress series)? If so, we map tags → lists.
2. Which Mailchimp audience, and which Zoho list? Does their Zoho list use Zoho signup forms (if yes, we need a separate "Website sign-ups" list with the signup form off)?
3. Who owns each account (login + billing), and who connects them on launch day?
4. Existing tags, groups or segments to map onto (instead of, or alongside, `specialty-*` / `country-*`)?
5. Is the Mailchimp audience GDPR-enabled (marketing permissions)? It works either way; this is just to know what PL will see.

## 8. Files
- **Plugin `pl-events-core` (`includes/`):**
  - `class-pl-newsletter.php` (records, subscribe → confirm → deliver, opt-out sync, cron, REST, shortcode, Contact hook)
  - `class-pl-nl-links.php` (new: confirmation email, signed links, confirm/unsubscribe pages)
  - `class-pl-nl-hooks.php` (new: webhooks)
  - `class-pl-nl-provider.php` (interface + `unsubscribe()`)
  - `class-pl-nl-mailchimp.php` (subscribed + marketing permissions + compliance fallback + webhook)
  - `class-pl-nl-zoho.php` (listsubscribe without a second confirmation, unsubscribe, own-confirmation warning)
  - `class-pl-newsletter-admin.php` (webhooks section, warnings, CSV, columns, row action)
  - `class-pl-secret.php`
- **Plugin templates / JS:** `templates/newsletter-form.php`, `templates/event-page.php`, `assets/js/pl-events.js`.
- **Theme:** `inc/chrome.php`, `inc/forms.php`, `assets/css/src/wp.css` → `pl.css` (link pages).
- **Tools:** `tools/nl-mock/` (sandbox mocks + `nltest.py`, 44 checks), `tools/click-sweep.js` (newsletter flow on the mocks).

## 9. Tests (7 Nov 2026, round 4)
**Mocks, Destination Both — `tools/nl-mock/nltest.py` 44/44:**
- One email only, and no provider call before confirmation.
- Opening the link changes nothing; the button click confirms.
- Mailchimp PUT `subscribed` + Email permission only + tags + note with the confirmation time.
- Zoho listsubscribe + 3 tags.
- Tampered link → calm page, no change. Expired (8 days) → "has expired… fresh link".
- Mailchimp webhook → Zoho unsubscribed (no Mailchimp call). The same webhook again → no calls.
- Zoho webhook (JSON) → Mailchimp unsubscribed. Zoho soft bounce ignored; hard bounce → bounced + Mailchimp unsubscribed.
- Site unsubscribe link: opening it changes nothing; the click removes the person from both.
- Webhook with a wrong secret → 404. Mailchimp's GET check → 200.
- Provider down during an unsubscribe → queued → the cron finishes it.
- Mailchimp "compliance state" → falls back to pending. Zoho's own confirmation → warning.
- Mailchimp webhook created by API (events unsubscribe + cleaned, no API source) → Connected. Zoho → Connected after its first call.
- The same address twice in 24 hours → one email.

**Click sweep, sandbox with mocks:** 384/384. At 375/768/1280, by real clicks: Subscribe → one email → Confirm my subscription → both providers → Unsubscribe me → removed from both.

**Staging:**
- The settings page opens, no secrets in the fields, CSV export downloads (admin check 17/17).
- Click sweep 372/372, including the tampered-link pages.
