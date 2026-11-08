# Site emails — final texts (6.1b round 7)

Captured on the sandbox on 8 Nov 2026 (nothing was sent: a temporary hook stopped every email and wrote it down; data restored afterwards). Site name on staging/live = "PL Events" (the sandbox prints "PL sandbox"). Links shown with the sandbox address; on the live site they start with `https://plevents.org/` and the sign-in links use `/plevents/`. Test names/emails are placeholders.

## 1. New account (to the new user)

- **From:** PL Events Team
- **Subject:** [PL sandbox] Login Details

```
Hello,

An account was made for you on the PL Events website.

Username: mail-test

To set your password, open this link:
http://localhost:8080/plevents/?action=rp&key=…&login=mail-test

Then sign in at http://localhost:8080/plevents/ (bookmark it).

— PL Events Team
```

## 2. Password reset (to the user)

- **From:** PL Events Team
- **Subject:** [PL sandbox] Password Reset

```
Hello,

Someone asked to reset the password of the PL Events website account "mail-test".

To choose a new password, open this link (it works once, for a short time):
http://localhost:8080/plevents/?action=rp&key=…&login=mail-test

If you didn't ask for this, ignore this email: nothing changes.

— PL Events Team
```

## 3. Password changed

- **From:** PL Events Team
- **Subject:** [PL sandbox] Password Changed

```
Hi mail-test,

This notice confirms that your password was changed on PL sandbox.

If you did not change your password, please contact the Site Administrator at
admin@example.com

This email has been sent to mail-test@example.com

— PL Events Team
```

## 4. Email address changed (to the old address)

- **From:** PL Events Team
- **Subject:** [PL sandbox] Email Changed

```
Hi mail-test,

This notice confirms that your email address on PL sandbox was changed to mail-test2@example.com.

If you did not change your email, please contact the Site Administrator at
admin@example.com

This email has been sent to mail-test@example.com

— PL Events Team
```

## 5. Password changed (notice to the site admin)

- **From:** PL Events Team
- **Subject:** [PL sandbox] Password Changed

```
Password changed for user: mail-test

— PL Events Team
```

## 6. Newsletter confirmation (to the subscriber)

- **From:** PL Events Team
- **Subject:** Confirm your subscription to PL Events updates

```
Hello Test,
Please confirm that you’d like emails from PL Events about upcoming medical congresses.
Confirm my subscription [http://localhost:8080/?pl_nl_act=confirm&r=106&x=1794749182&s=…]
The link works for 7 days. If you didn’t ask for this, ignore this email — you won’t be added.
Button not working? Copy this link into your browser:
http://localhost:8080/?pl_nl_act=confirm&r=106&x=1794749182&s=…
— PL Events Team · localhost [http://localhost:8080/]
```

## 7. Contact form notification (to the office)

- **From:** PL Events Team
- **Subject:** Website: Plan a congress (proposal) — Test Person (guide demo)

```
New message from the PL Events website:

Full name
Test Person (guide demo)

Email
test.person@example.com [mailto:test.person@example.com]

Phone or WhatsApp (optional)
+20 102 000 0000

Topic
Plan a congress (proposal)

Office
Alexandria — head office

Message
This is a test message for the training guide. Please ignore.

Updates (optional)

---
Reply to this email to answer Test Person (guide demo) directly.

— PL Events Team
```

## 8. Careers form notification, with the CV (to "CVs go to")

- **From:** PL Events Team
- **Subject:** Website CV: Test Applicant (guide demo) — Event coordinator

```
A CV was sent through the PL Events website:

Full name
Test Applicant (guide demo)

Email
test.applicant@example.com [mailto:test.applicant@example.com]

Phone or WhatsApp (optional)
+20 100 000 0000

Role you’re interested in
Event coordinator

CV (PDF or Word, up to 5 MB)
Ok8gQVXpNHwk-Test-CV.pdf

A few lines about you
Test application for the training guide.

— PL Events Team
```
