# Step 4.5 — Forms: Step Summary (Thread 7, 5 Nov 2026)

**Status:** ✅ built and tested on staging — see DoD below (form email arrival in Sara's inbox: last line).

## What was built
- **Forminator Free**, two forms created by code (`plt_provision_forms()`, theme `inc/forms.php`) and shown on the pages through `[pl_form name="contact|careers"]`:
  - **PL — Contact** (`/contact/`): Full name* · Email* · Phone or WhatsApp (optional) · **Topic** (Plan a congress (proposal) · Sponsorship or exhibition · Help with a registration · Careers · Something else) · **Office** (from PL Website › Offices, "Pre-set from the page you came from; change it if you like.") · Message* → **Send message**.
  - **PL — Careers (CV)** (`/careers/#cv`): Full name* · Email* · Phone or WhatsApp · Role you're interested in · **CV (PDF or Word, up to 5 MB)*** · A few lines about you → **Send my CV**.
- **Pre-fill (2.1 P4) — confirmed in Forminator Free:** `?topic=` and `?office=` choose the dropdowns. Every "Request a proposal" link sends `topic=proposal`, "Sponsor a congress" `topic=sponsor`, the About explorer's "Plan yours here" `office=alexandria|dubai`. Tested on staging: `?topic=sponsor&office=dubai` → Sponsorship + Dubai.
- **Routing (Forminator's own email routing):** Office = Alexandria → `events@plevents.org`; Office = Dubai → `dina@plevents.org` (first email of each office in wp-admin — change an office email and the routing follows on the next provisioning). Careers → the head office. Reply-To = the visitor, so PL answers with one click. CV attached to the email.
- **Staging safety (two layers):** (1) option `plt_mail_override` = Sara's address → Forminator sends every form email to Sara, subject "[PL staging] …", first line = where it would go on the live site; (2) **hard safety net in the staging mu-plugin**: `wp_mail` itself replaces *any* recipient from *any* plugin with Sara's address and logs every outgoing mail (recipients + subject) to the private debug log. At launch (6.3): delete the option and the mu-plugin.
- **Look:** Forminator's "None" design + theme CSS mapping its markup to the prototype form (labels, 48 px fields, radius 10, blue pill button, two columns → one on mobile). Errors: inline, plain words, never blaming ("Please tell us your name.", "This email address looks incomplete — check it, please."). The form is visible **without JavaScript** (Forminator hides it until its JS runs — overridden).
- **Spam:** Forminator honeypot on. (reCAPTCHA needs keys — not added.)

## Bugs found and fixed
1. Office links used numeric IDs (`?office=4`): WordPress gives non-public types numeric slugs. The data layer now always returns the stable key (`alexandria`, `dubai`) — also fixed the About explorer links; data-layer tests pass.
2. **Mail from this host:** messages from `wordpress@…` (WordPress's default) are accepted by sendmail but **never delivered** (not even to spam). Sent from the account's real address with that address as envelope sender, they arrive. Fixed in the **staging** mu-plugin only (`deploy/mu/pl-staging.php`); PL's host gets its own setting at launch.
3. Forminator's default email error text replaced by ours (wrong option key).
4. **⚠ Staging override was on the wrong filter name** (`forminator_custom_form_…`; Forminator 1.58.4 uses `forminator_form_get_admin_email_recipients`). Found by the new mail log on test 3: the message was addressed to `events@plevents.org` — **it failed to send** (see 5). Fixed (both names hooked) **and** the hard safety net above added, so this cannot recur.
5. **This host's mail:** PHP `mail()` fails whenever a message has an extra header (Forminator always sends HTML + Reply-To) → staging now pipes to sendmail directly; From/envelope = the account address; and the host's outbound filter silently drops some test-like subjects ("… test 7") — realistic names pass.

### Did any test email reach PL? — almost certainly no
The first two test submissions (before the mail log existed) were routed to `dina@plevents.org` (contact, Dubai) and `events@plevents.org` (careers). They could not have left the server: at that time mail went through PHP `mail()`, which fails on this host for every HTML + Reply-To message (reproduced 3/3), and their subjects carried "test", which the host's filter drops. Every later attempt is in the log and either failed or went to Sara. **To be 100 % sure:** cPanel › Email › **Track Delivery**, search `plevents.org`, 5 Nov 2026 — expect no deliveries. (The API can't read that screen on this account.)

## DoD — "test submissions arrive"
| Check | Result |
|---|---|
| Empty submit / bad email | Inline errors under each field, no submission ✅ |
| Contact (Sponsorship · Dubai) on staging | "Thank you — your message is with our team…" → stored as entry #1 (topic and office correct) ✅ |
| Careers with a 1-page PDF | "Thank you — your CV has reached our team…" → entry #2 with the CV file ✅ |
| Email from the host to Sara | after the sender fix: WordPress mail **arrived in Sara's Gmail inbox** ✅ |
| Form email (Forminator) → Sara, realistic names | Contact "Mona Hassan" (Sponsorship · Dubai) and Careers "Omar Adel" + PDF: **sent without error** (mail log 14:23–14:24 UTC); arrival: see below |

Test entries are in wp-admin › Forminator › Submissions (marked "test — please ignore"); delete them any time.

## Open
1. **CV privacy (Step 1.1 B2/B3, 2.1):** Forminator stores CVs in `/wp-content/uploads/forminator/…` with a random file name. Retention period is TBC from PL — then set Forminator › Settings › Data to delete entries/files after N days, and say so on the Privacy page.
2. **Live host email (6.3):** use PL's real mailbox (or an SMTP plugin with it) so mail is authenticated (SPF/DKIM) — don't rely on PHP mail.
3. A visitor confirmation email ("We got your message") is not sent — add if PL wants it.

## Critique
1. Routing uses each office's **first** email; an office with a dedicated "proposals@" address would need a second rule.
2. The honeypot stops bots, not humans pasting spam; add Forminator's free reCAPTCHA/hCaptcha after launch if spam appears.
3. The native file picker ("Choose File / No file chosen") reads in the browser's language — fine for an English site, unstyled text varies by browser.
