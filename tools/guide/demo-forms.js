#!/usr/bin/env node
/* Demo messages for the Forms chapter: one Contact message and one job application with a test CV,
   sent through the real forms on the sandbox (so the entries look exactly like real ones).
   Test data only (example.com); run.sh restores the database afterwards. */
const fs = require('fs'), path = require('path');
const { chromium } = require('playwright');
const BASE = (process.env.PL_GUIDE_BASE || 'http://localhost:8080').replace(/\/$/, '');
if (!/^http:\/\/(localhost|127\.0\.0\.1)/.test(BASE)) process.exit(2);
const cv = path.join(__dirname, 'out', 'tmp', 'Test-CV.pdf');
fs.mkdirSync(path.dirname(cv), { recursive: true });
fs.writeFileSync(cv, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 595 842]/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj 4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj 5 0 obj<</Length 44>>stream\nBT /F1 24 Tf 72 760 Td (Test CV) Tj ET\nendstream endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
(async () => {
  const b = await chromium.launch(); const p = await b.newPage();
  const send = async (url, fill) => {
    await p.goto(BASE + url); await p.waitForLoadState('load');
    const f = p.locator('form.forminator-custom-form').first();
    await fill(f);
    await f.locator('button[type=submit], .forminator-button-submit').first().click();
    await p.waitForSelector('.forminator-response-message.forminator-success, .forminator-success', { timeout: 20000 }).catch(() => {});
    console.log(url, (await p.locator('.forminator-response-message').first().innerText().catch(() => '')).trim().slice(0, 60) || '(sent)');
  };
  await send('/contact/?topic=congress&office=alexandria', async f => {
    await f.locator('input[name="name-1"]').fill('Test Person (guide demo)');
    await f.locator('input[name="email-1"]').fill('test.person@example.com');
    await f.locator('input[name="phone-1"]').fill('+20 102 000 0000').catch(() => {});
    await f.locator('textarea[name="textarea-1"]').fill('This is a test message for the training guide. Please ignore.');
  });
  await send('/careers/', async f => {
    await f.locator('input[name="name-1"]').fill('Test Applicant (guide demo)');
    await f.locator('input[name="email-1"]').fill('test.applicant@example.com');
    await f.locator('input[name="phone-1"]').fill('+20 100 000 0000').catch(() => {});
    await f.locator('input[name="text-1"]').fill('Event coordinator').catch(() => {});
    await f.locator('input[type=file]').setInputFiles(cv);
    await f.locator('textarea[name="textarea-1"]').fill('Test application for the training guide.').catch(() => {});
  });
  await b.close();
})();
