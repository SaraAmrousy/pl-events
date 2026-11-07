# Step 4.6 — Newsletter (Zoho Campaigns + Mailchimp)

**Date:** 7 Nov 2026 · **Status:** built, deployed to staging, tested against mock providers. **Waiting:** Sara's free test accounts (Zoho + Mailchimp) for the real connection test. No PL account is connected, and none will be until the reveal.

## 1. Decision: our own adapter, not Forminator's integration
| Option | Why not / why |
|---|---|
| Forminator's native integrations | Forminator Free has **Mailchimp but no Zoho Campaigns**. Our footer and event-page forms aren't Forminator forms (they're server-rendered shortcodes that need to stay light). Its integration has **no queue** (if Mailchimp is down, the sign-up is lost) and **no consent record**. |
| **Our adapter in `pl-events-core` (chosen)** | One interface `PL_NL_Provider::subscribe( $email, $name, $tags, $consent_meta )` with two implementations, `PL_NL_Mailchimp` and `PL_NL_Zoho`. One local record per sign-up, a cron retry and a consent record. All three forms (footer, event page, Contact checkbox) go through the same code. Forminator is still used for the Contact form UI only: when the box is ticked, our hook sends the sign-up through the adapter. |

## 2. The flow
1. **Form.** The visitor fills the form (email, optional topics) and clicks **Subscribe**.
   - JS posts to `POST /wp-json/pl/v1/subscribe`. Without JS, the form posts to `admin-post.php` and comes back with a message.
   - Checks: same-origin, honeypot, valid email, at most 5 tries per hour per IP.
2. **Local record first.** A private "Newsletter sign-up" post is saved (wp-admin › PL Website › Newsletter sign-ups, admins only) with:
   - the email, name and tags;
   - **consent_meta**: date (UTC), page URL, form, and the exact consent text shown;
   - the destination(s) and a status per provider.
3. **Provider, always double opt-in.**
   - Mailchimp: the member is created as **`pending`**, so Mailchimp sends the confirmation email. Tags are added, and the consent record is saved as a member note.
   - Zoho: `listsubscribe`, with the list's own double opt-in. Tags attach **after** the person confirms (Zoho only tags confirmed contacts), so the cron keeps retrying them.
4. **Message to the visitor:** "Almost done — check your inbox and confirm your email. It can take a few minutes."
   - When it fails, the message doesn't blame the visitor and offers a retry.
   - If the provider is down or no key is set, the visitor sees the same success message: the sign-up is saved and sent later.
5. **Queue.** When a provider is down, the key is missing, or no destination is chosen, the record stays **queued**.
   - An hourly cron (`pl_nl_retry`) retries it, up to 8 tries.
   - Errors that can't be fixed by retrying (for example "email rejected by the provider") are marked **failed** and not retried.
6. **Duplicates.** The same email within 24 hours makes no new provider calls.

### Forms
| Where | What | Tags it sends |
|---|---|---|
| Footer (every page) | Email + **Subscribe**; "Choose topics (optional)" opens specialty checkboxes + a country select | `specialty-*`, `country-*`, `source-footer` |
| Event page | "Get updates on [series]" + email | `series-*`, `specialty-*`, `country-*` of that event, `source-event` |
| Contact | Checkbox "Send me updates about upcoming PL congresses", **unchecked by default** | `source-contact` (only when ticked) |

Every form has:
- a visible label;
- a verb-first button;
- a privacy line under it (the consent text + a Privacy policy link);
- loading, success and error states, announced to screen readers (`aria-live`).

On staging, every sign-up also gets the tag **`pl-staging-test`** (`PL_NL_TEST_ONLY`, set by the staging mu-plugin).

## 3. Settings → PL Newsletter (wp-admin, admins only)
- **Destination:** None · Zoho · Mailchimp · Both.
  - With **Both**, a warning shows: two lists means two confirmation emails and two places to unsubscribe.
- **Mailchimp:** API key, Audience ID; the data center is read from the key (`…-us21`).
- **Zoho:** data center (com / eu / in / com.au / jp / sa), Client ID, Client secret, one-time grant code, List key.
- **Test connection** button per provider. It only reads the list name; it never adds a contact.
- **Security:**
  - Keys are stored **encrypted** (AES-256-GCM; the key is derived from the site's `AUTH_KEY` + `SECURE_AUTH_KEY`).
  - The option isn't autoloaded.
  - Fields are **write-only**: the page shows "saved ••••", never the key. An empty field keeps the stored key, and a checkbox forgets it.
  - The Zoho grant code is exchanged once for a refresh token and never stored.
  - Keys are never written to logs or error messages.

## 4. How PL segments and sends a campaign
**Tag scheme:**
- `specialty-ophthalmology`, `specialty-dermatology`, …
- `country-eg`, `country-sa`, …
- `series-<short name>`
- `source-footer` / `source-event` / `source-contact`

**Mailchimp:**
1. Audience › All contacts › filter by tag, or Audience › **Segments › Create segment** ("Tags contact is tagged `specialty-ophthalmology`" AND/OR `country-eg`).
2. Campaigns › Create › Email.
3. Choose the audience, then **Send to: a segment or tag**.
4. Design the email, send a test, then send or schedule.
- Only confirmed (subscribed) members receive campaigns.

**Zoho Campaigns:**
1. Contacts › **Tags** (filter contacts by tag), or Contacts › **Segments** › Create segment with "Tag is `specialty-ophthalmology`".
2. Campaigns › Create campaign › Email.
3. Recipients: the list + the segment.
4. Content, test email, then send or schedule.
- Only confirmed contacts receive campaigns.

**Consent record:**
- Mailchimp: the note on the member + the site record.
- Zoho: the site record only (Zoho has no notes API).
- To answer "when did this person agree?", search the email in **PL Website › Newsletter sign-ups**.

## 5. Staging rules
- No PL account. Only Sara's own free accounts with a **test list**.
- Every sign-up is tagged `pl-staging-test`, and wp-admin shows a staging banner on the settings page.
- The mail safety net (every site email → Sara) stays on. Note that the providers' confirmation emails are sent by Zoho or Mailchimp, not by the site, so test only with addresses Sara owns.
- Destination on staging today: **None**. Sign-ups are queued and sent once a destination is set.

## 6. What Sara needs to do (only in wp-admin on staging — never in the chat)
1. **Mailchimp (free):**
   - Create an account and an Audience (the test list).
   - Profile › Extras › **API keys** › Create a key.
   - Audience › Settings › **Audience ID**.
2. **Zoho Campaigns (free):**
   - Create an account and a mailing list, with **double opt-in on** in the list settings.
   - Find the List key: list › Setup › List key.
   - At **api-console.zoho.com** › Add client › **Self Client**:
     - copy the Client ID + Client secret;
     - Generate Code, scope `ZohoCampaigns.contact.ALL,ZohoCampaigns.campaign.READ`, 10 minutes.
   - Note the data center from the URL: `zoho.com` = com, `zoho.eu` = eu, …
3. Go to **saraamrousy.net/pl/wp-admin › Settings › PL Newsletter**:
   - paste the keys and Save (paste the Zoho grant code within its 10 minutes);
   - click **Test connection** for each;
   - choose a Destination.
4. Subscribe with your own email from the footer, confirm the email, and check the tags in each account. Then tell me, and I'll check the records.

## 7. Launch steps (add to 6.3)
- [ ] L6 answered (below).
- [ ] Remove Sara's test keys. Connect **PL's** account(s) in Settings › PL Newsletter (by PL or Sara in wp-admin), click Test connection, and choose the Destination.
- [ ] The staging mu-plugin (`PL_NL_TEST_ONLY`, the `pl-staging-test` tag) is **not** installed on live.
- [ ] **SPF + DKIM for the provider on plevents.org** (Mailchimp: Domains › Authenticate; Zoho: Sender domains › Authenticate). Otherwise confirmation emails and campaigns land in spam.
- [ ] Sender name/email on the list: e.g. "PL Events" `news@plevents.org`, with a physical address in the footer (anti-spam law).
- [ ] One real test sign-up → confirmation email → the tags show in PL's account. Then delete the test contact.
- [ ] Privacy page mentions the newsletter provider(s) as processors.

### L6 — questions for PL
1. Which account sends campaigns: Zoho, Mailchimp or both?
2. Which list/audience should the website add people to?
3. Who owns the account (login + billing), and who connects it on launch day?
4. Do they already have tags/groups/segments we should map to (instead of, or alongside, `specialty-*` / `country-*`)?

## 8. Files
- **Plugin `pl-events-core`:**
  - `includes/class-pl-nl-provider.php` (interface)
  - `includes/class-pl-nl-mailchimp.php`
  - `includes/class-pl-nl-zoho.php`
  - `includes/class-pl-secret.php` (encryption)
  - `includes/class-pl-newsletter.php` (records, queue, cron, REST, shortcode `[pl_newsletter context series specialties country title]`, Contact hook)
  - `includes/class-pl-newsletter-admin.php` (settings page)
  - `templates/newsletter-form.php`
  - `templates/event-page.php` (event form)
  - `assets/js/pl-events.js` (states)
  - `includes/class-pl-plugin.php` (init)
- **Theme `hello-pl-child`:**
  - `inc/chrome.php` (footer form)
  - `inc/forms.php` (Contact checkbox)
  - `assets/css/src/wp.css` → `pl.css`
- **Deploy:** `deploy/mu/pl-staging.php` (`PL_NL_TEST_ONLY`).

## 9. Tests (evidence in the thread)
**Mock providers (sandbox):**
- Mailchimp PUT pending + tags + note ✓; Zoho token + listsubscribe + tags ✓; Both ✓.
- Provider down → queued → the cron delivers ✓; no destination → queued → sent once one is set ✓.
- Honeypot not stored ✓; invalid email ✓; no Origin rejected ✓; duplicate within 24h makes no calls ✓; the 6th try in an hour is blocked ✓.
- Key encrypted in the DB, not autoloaded, not in the HTML or debug.log ✓; an empty field keeps the key ✓.
- Contact box: ticked → sign-up; unticked → none; unchecked by default ✓.
- Bug found and fixed: private-post titles came back as "Private: email" → the raw title is read.

**Staging, 375 px, footer with topics:** REST `queued`, success message shown, the field cleared, the record correct (tags + consent page + `pl-staging-test`). The test record was deleted.

## Open
- Sara's test accounts → the real connection test (section 6).
- L6 (PL).
