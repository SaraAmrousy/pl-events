#!/usr/bin/env node
/**
 * Guardrails test (Step 6.2b). Real browser, real clicks in wp-admin.
 *   PL_QA_USER=… PL_QA_PASS=… node tools/guard-test.js [base] [--write]
 * Without --write (staging): only checks that change nothing — blocked saves (the save never
 * happens) and live hints in the form. With --write (sandbox only, the caller restores the
 * database afterwards): also the saves that must go through, the automatic fixes and the warnings.
 * Exit code 1 on any ❌. Credentials come from the environment and are never printed.
 */
const { chromium } = require('playwright');
const BASE = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
const WRITE = process.argv.includes('--write');
const USER = process.env.PL_QA_USER || 'admin', PASS = process.env.PL_QA_PASS || 'admin';
if (WRITE && !/^http:\/\/(localhost|127\.0\.0\.1)/.test(BASE)) { console.error('--write is for the sandbox only.'); process.exit(2); }
const rows = [];
const row = (what, expected, got, ok) => { rows.push({ what, expected, got: String(got).replace(/\s+/g, ' ').slice(0, 140), ok: !!ok }); console.log((ok ? '✅ ' : '❌ ') + what + ' — ' + String(got).replace(/\s+/g, ' ').slice(0, 140)); };

(async () => {
  const browser = await chromium.launch();
  const page = await (await browser.newContext({ viewport: { width: 1280, height: 1000 } })).newPage();
  page.setDefaultTimeout(20000);
  await page.goto(BASE + '/plevents/');
  await page.fill('#user_login', USER); await page.fill('#user_pass', PASS);
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  // An upcoming and a past event, from the wp-admin list (Status column).
  await page.goto(BASE + '/wp-admin/edit.php?post_type=event&posts_per_page=100');
  const evs = await page.$$eval('#the-list tr', trs => trs.map(t => ({ post_id: t.id.replace('post-', ''), name: (t.querySelector('.row-title') || {}).textContent || '', status: ((t.querySelector('.column-pl_status') || {}).textContent || '').trim().toLowerCase() })));
  const up = evs.find(e => e.status === 'upcoming') || evs[0];
  const past = evs.find(e => e.status === 'past');
  const edit = id => BASE + '/wp-admin/post.php?post=' + id + '&action=edit';
  // Like a person: wait until the form's scripts (SCF/ACF validation) are ready before clicking.
  const origGoto = page.goto.bind(page);
  page.goto = async (u, o) => { const r = await origGoto(u, o); await page.waitForLoadState('load'); await page.waitForFunction(() => !document.getElementById('post') || (window.acf && window.acf.validation && window.jQuery && jQuery('#post').data('acf') !== undefined), null, { timeout: 30000 }).catch(() => {}); await page.waitForTimeout(1500); return r; };
  const setDate = (name, ymd) => page.evaluate(([n, v]) => {
    const w = document.querySelector('[data-name="' + n + '"]');
    w.querySelector('input[type=hidden]').value = v;
    const t = w.querySelector('input[type=text]'); t.value = v; t.dispatchEvent(new Event('change', { bubbles: true }));
  }, [name, ymd]);
  const update = async () => {
    await page.click('#publish');
    await page.waitForTimeout(400);
    await Promise.race([
      page.waitForSelector('.acf-error-message, .acf-notice.-error', { state: 'visible', timeout: 45000 }),
      page.waitForURL(/message=\d+/, { timeout: 45000 }),
    ]).catch(() => {});
    await page.waitForLoadState('domcontentloaded');
  };
  const errors = async () => (await page.locator('.acf-field .acf-notice.-error, .acf-error-message').allInnerTexts()).join(' | ');
  const saved = async () => /message=\d+/.test(page.url()) || (await page.locator('#message.updated, .notice-success').count()) > 0;

  /* 1. Registration Open without a link → blocked */
  await page.goto(edit(up.post_id));
  await page.selectOption('[data-name="pl_registration"] select', 'open');
  await page.fill('[data-name="pl_registration_url"] input', '');
  const live1 = await page.locator('[data-name="pl_registration"] .pl-hint.err').innerText().catch(() => '');
  row('Live hint: Open without a link', 'red hint under Registration', live1, /needs the/.test(live1));
  await update();
  let e = await errors();
  row('Open without a link → Update blocked', 'error + how to fix, not saved', e, !(await saved()) && /Registration is "Open"/.test(e) && /Paste it here/.test(e));

  /* 2. End before start → blocked */
  await page.goto(edit(up.post_id));
  await setDate('pl_start', '20270210'); await setDate('pl_end', '20270205');
  const live2 = await page.locator('[data-name="pl_end"] .pl-hint.err').innerText().catch(() => '');
  row('Live hint: end before start', 'red hint under End date', live2, /before the start/.test(live2));
  await update();
  e = await errors();
  row('End date before start date → Update blocked', 'error + how to fix, not saved', e, !(await saved()) && /end date is before/i.test(e) && /Pick an end date/.test(e));

  /* 3. A link without https:// gets it; text that isn't a link is blocked */
  await page.goto(edit(up.post_id));
  await page.fill('[data-name="pl_website"] input', 'www.example.org/congress');
  await page.locator('[data-name="pl_website"] input').blur();
  const v3 = await page.inputValue('[data-name="pl_website"] input');
  row('Link typed without https:// → added', 'https://www.example.org/congress', v3, v3 === 'https://www.example.org/congress');
  await page.fill('[data-name="pl_website"] input', 'coming soon');
  await page.locator('[data-name="pl_website"] input').blur();
  await update();
  e = await errors();
  row('Text that isn’t a link → blocked', 'error + example, not saved', e, !(await saved()) && /doesn’t look like a web address/.test(e));

  /* 4. Phone: a code typed into the number moves to Country code (live) */
  await page.goto(edit(up.post_id));
  await page.locator('[data-name="pl_phones"] .acf-actions [data-event="add-row"]').click();
  const r = page.locator('[data-name="pl_phones"] .acf-row:not(.acf-clone)').last();
  await r.locator('[data-name="number"] input').fill('+971 55-158 2689');
  await r.locator('[data-name="number"] input').blur();
  const code4 = await r.locator('[data-name="code"] select').inputValue(), num4 = await r.locator('[data-name="number"] input').inputValue();
  const prev4 = await r.locator('.pl-hint.ok').innerText().catch(() => '');
  row('Phone "+971 55-158 2689" with code +20', 'code +971, number 551582689, preview', `${code4} ${num4} · ${prev4}`, code4 === '+971' && num4 === '551582689' && /\+971 55 158 2689/.test(prev4));
  await r.locator('[data-name="number"] input').fill('12345');
  await r.locator('[data-name="number"] input').blur();
  const w4 = await r.locator('.pl-hint.warn').innerText().catch(() => '');
  row('Phone too short → yellow hint (no block)', 'Looks short…', w4, /Looks short/.test(w4));

  /* 5. Office: switching off the only head office → blocked */
  await page.goto(BASE + '/wp-admin/edit.php?post_type=pl_office');
  const offices = await page.$$eval('#the-list tr', trs => trs.map(t => ({ id: t.id.replace('post-', ''), name: (t.querySelector('.row-title') || {}).textContent })));
  let head = null;
  for (const o of offices) {
    await page.goto(edit(o.id));
    if (await page.locator('[data-name="pl_head_office"] input[type=checkbox]').isChecked()) { head = o; break; }
  }
  const other = offices.find(o => head && o.id !== head.id);
  row('Exactly one head office in the data', '1', head ? head.name : 'none', !!head);
  await page.locator('[data-name="pl_head_office"] .acf-switch').click();
  await update();
  e = await errors();
  row('Switch off the only head office → blocked', 'error + how to move it, not saved', e, !(await saved()) && /needs one head office/.test(e));

  /* 6. Image size note (existing, 4.4): shown under image fields */
  await page.goto(edit(up.post_id));
  const imgNote = await page.locator('[data-name="pl_poster"] .description, [data-name="pl_poster"] p').allInnerTexts();
  row('Image fields say what size they need', '“at least 1280 × 800 px”', imgNote.join(' '), /1280 × 800/.test(imgNote.join(' ')));

  if (WRITE) {
    /* 7. A correct event still saves (no false block) + https added on save + phone fixed on save */
    await page.goto(edit(up.post_id));
    await page.selectOption('[data-name="pl_registration"] select', 'open');
    await page.fill('[data-name="pl_registration_url"] input', 'register.example.org/now');
    await page.locator('[data-name="pl_phones"] .acf-actions [data-event="add-row"]').click();
    const r7 = page.locator('[data-name="pl_phones"] .acf-row:not(.acf-clone)').last();
    await r7.locator('[data-name="number"] input').fill('+966 12 345 6789');
    await update();
    const v7 = await page.inputValue('[data-name="pl_registration_url"] input');
    const last = page.locator('[data-name="pl_phones"] .acf-row:not(.acf-clone)').last();
    const c7 = await last.locator('[data-name="code"] select').inputValue(), n7 = await last.locator('[data-name="number"] input').inputValue();
    row('Correct event (Open + link) → saves', 'saved, link https://register.example.org/now, phone +966 123456789', `${await saved()} ${v7} ${c7} ${n7}`, (await saved()) && v7 === 'https://register.example.org/now' && c7 === '+966' && n7 === '123456789');

    /* 8. Past event + Open → saves, but warns */
    if (past) {
      await page.goto(edit(past.post_id));
      await page.selectOption('[data-name="pl_registration"] select', 'open');
      await page.fill('[data-name="pl_registration_url"] input', 'https://register.example.org/old');
      const live8 = await page.locator('[data-name="pl_registration"] .pl-hint.warn').innerText().catch(() => '');
      await update();
      const n8 = await page.locator('.pl-guard-warn').allInnerTexts();
      row('Past event + Open → saves with a yellow warning', 'saved + “has ended… set Registration to Closed”', `${await saved()} ${live8} ${n8.join(' ')}`, (await saved()) && /already ended/.test(live8) && /has ended/.test(n8.join(' ')) && /Closed/.test(n8.join(' ')));
      await page.goto(BASE + '/wp-admin/edit.php?post_type=event');
      const l8 = await page.locator('.pl-guard-warn').allInnerTexts();
      row('Events list names the ended-but-Open events', 'notice with a link', l8.join(' '), l8.join(' ').includes(past.name.slice(0, 12)));
    }

    /* 9. Office: a new head office switches the old one off */
    if (head && other) {
      await page.goto(edit(other.id));
      await page.locator('[data-name="pl_head_office"] .acf-switch').click();
      await update();
      const n9 = (await page.locator('.pl-guard-note').allInnerTexts()).join(' '), s9 = await saved();
      await page.goto(edit(head.id));
      const off = !(await page.locator('[data-name="pl_head_office"] input[type=checkbox]').isChecked());
      row('Make another office the head office', `${other.name} head, ${head.name} switched off + note`, `${s9} ${off} ${n9}`, s9 && off && /is now the head office/.test(n9));
      /* 10. Trash the head office → stopped with a fix */
      await page.goto(BASE + '/wp-admin/edit.php?post_type=pl_office');
      const href = await page.locator(`#post-${other.id} .submitdelete`).getAttribute('href');
      await page.goto(href.startsWith('http') ? href : BASE + '/wp-admin/' + href);
      const body = await page.locator('body').innerText();
      row('Bin the head office → stopped', '“is the head office… switch Head office on…”', body, /is the head office/.test(body) && /switch Head office on/.test(body));
    }
  }

  await browser.close();
  const bad = rows.filter(x => !x.ok).length;
  console.log(`\n${rows.length - bad}/${rows.length} ✅`);
  process.exit(bad ? 1 : 0);
})().catch(err => { console.error(err); process.exit(1); });
