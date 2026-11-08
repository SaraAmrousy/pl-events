# Step 6.2c — Training deck (8 Nov 2026)

**Status: ✅ final.** Slides artifact: https://claude.ai/artifact/6WUECTgYnQLhFNFR7cMRxn ("إزاي تديروا موقع PL Events"). Audience: PL's owners and the staff who will run the site. Export: PDF or PowerPoint from the deck's Export.

**Context:** PL's owners have already seen the staging site and liked it (Sara, 8 Nov 2026).

## Decisions
- Egyptian Arabic, plural imperative ("اضغطوا"); English button and field names exactly as on screen.
- PL Events Design System (paper / sand / navy / ink, blue #2C4894 accent; Montserrat for Latin, IBM Plex Sans Arabic for Arabic). The design system is installed in the deck (`project/ds/pl-events/tokens.json`).
- RTL: every Arabic line is wrapped in an RTL isolate, so mixed English words keep their place.
- The idea that runs through the deck: **every field has a place on the site**. Pairing slides show the wp-admin field on the right and the same thing on the site on the left, with the same numbers.
- Screenshots come from the guide export (`docs/guide/`). The sandbox admin bar ("PL sandbox", "Database: SQLite") was blanked before upload.
- The generated drawings (how the site works, forms, Mailchimp, results, hero safe zone) had RTL/bidi errors, so they were **rebuilt as native, editable slides** instead of images.

## Slides (21)
1. Cover
2. بتدخلوا الداتا، والصفحات بتتحدّث لوحدها (4-step flow)
3. الشرح موجود جوه الموقع (guardrails · ? button · PL Help)
4. الدخول من عنوان خاص (/plevents/)
5. كل المحتوى في قايمة واحدة (PL Website)
6. إضافة إيفنت جديد
7. Statement: كل خانة ليها مكان على الموقع
8. Registration ↔ Register now
9. WhatsApp for inquiries ↔ the card button
10. Image type: Poster vs Photo
11. Image sizes table (from PL_Image_Spec, 8 Nov)
12. Home hero safe zone (drawn)
13. Office phone / WhatsApp ↔ Contact
14. Page texts ↔ the page sentence
15. Where form messages go
16. Newsletter: one confirmation email → Zoho + Mailchimp with tags
17. Campaigns in Mailchimp and Zoho by tags
18. Reading results (Opens, Clicks, Unsubscribes, Bounces)
19. Guardrails + danger zone
20. Trouble table + PL Team
21. Closing: the full guide is inside the site (PL Website › Help)

## Still open (not blocking the deck)
- **Round 8, before launch:** see `docs/Step-6.3-Launch-Checklist.md` §0b (8.1–8.6).
- If round 8 changes a screen shown in the deck (e.g. the Help drawings), the deck slides that use screenshots may need a refresh: slides 3–6, 8–10, 13–14, 16.
- Mailchimp/Zoho slides are steps without real screenshots (no accounts). Replace them once PL's accounts are connected.
