#!/usr/bin/env node
/**
 * Admin / login check (Round 4). Real browser, real clicks.
 *   PL_QA_USER=… PL_QA_PASS=… node tools/admin-check.js [base] [--lock]
 * Credentials come from the environment only and are never printed. Use a temporary QA account
 * (delete it afterwards). --lock also tests the rate limit (5 wrong passwords → paused), sending a
 * different forged X-Forwarded-For / X-Real-IP / CF-Connecting-IP on every try — the site must count by
 * REMOTE_ADDR only. Afterwards the IP stays paused for 15 minutes unless the transient is cleared.
 */
const { chromium } = require('playwright');
const BASE = (process.argv[2] || 'https://saraamrousy.net/pl').replace(/\/$/, '');
const LOCK = process.argv.includes('--lock');
const USER = process.env.PL_QA_USER, PASS = process.env.PL_QA_PASS;
if (!USER || !PASS) { console.error('Set PL_QA_USER and PL_QA_PASS.'); process.exit(2); }
const rows = [];
const row = (what, expected, got, ok) => { rows.push({ what, expected, got, ok }); console.log((ok ? '✅ ' : '❌ ') + what + ' — ' + got); };

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  page.setDefaultTimeout(20000);
  const status = async (path) => (await page.goto(BASE + path, { waitUntil: 'domcontentloaded' })).status();

  for (const p of ['/wp-admin/', '/wp-admin/options-general.php', '/wp-login.php', '/login', '/admin']) {
    const s = await status(p);
    row('Signed out: ' + p, '404', String(s), s === 404);
  }
  let s = await status('/plevents');
  row('/plevents opens the sign-in form', '200 + form at /plevents/', `${s} ${page.url().replace(BASE, '')}`, s === 200 && /\/plevents\/$/.test(page.url()) && await page.locator('#loginform').count() === 1);
  row('Sign-in form posts to /plevents/', 'action=/plevents/', (await page.locator('#loginform').getAttribute('action')).replace(BASE, ''), /\/plevents\/$/.test(await page.locator('#loginform').getAttribute('action')));
  const lost = await page.locator('#nav a').first().getAttribute('href');
  row('"Lost your password?" link', '/plevents/?action=lostpassword', lost.replace(BASE, ''), /\/plevents\/\?action=lostpassword/.test(lost));

  const login = async (pw) => {
    await page.goto(BASE + '/plevents/', { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login', USER);
    await page.fill('#user_pass', pw);
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#wp-submit')]);
  };

  if (LOCK) {
    // Same machine, but every try claims to come from somewhere else: X-Forwarded-For / X-Real-IP /
    // CF-Connecting-IP change each time. The site must count by REMOTE_ADDR only → paused after 5.
    const fake = (i) => `198.51.100.${10 + i}`;
    const spoof = async (i) => page.setExtraHTTPHeaders({ 'X-Forwarded-For': fake(i), 'X-Real-IP': fake(i + 50), 'CF-Connecting-IP': fake(i + 100), 'True-Client-IP': fake(i + 150), Forwarded: `for=${fake(i + 200)}` });
    for (let i = 1; i <= 5; i++) { await spoof(i); await login('wrong-' + i + '-' + Date.now()); }
    const err1 = (await page.locator('#login_error').innerText().catch(() => '')).trim();
    await spoof(6);
    await login(PASS);
    const err = (await page.locator('#login_error').innerText().catch(() => '')).trim();
    row('Rate limit: right password after 5 wrong ones, each with a different X-Forwarded-For / X-Real-IP / CF-Connecting-IP', 'paused 15 min (headers ignored), still on the form', err.slice(0, 70), /paused for 15 minutes/.test(err) && await page.locator('#wpadminbar').count() === 0);
    row('Rate limit message blames nobody', 'no "incorrect"/"invalid" in the pause message', err.slice(0, 40), /paused/.test(err) && !/incorrect|invalid/i.test(err));
    row('Wrong password before the pause', 'normal WordPress message', err1.slice(0, 50), err1.length > 0);
    console.log('LOCKED — clear the transient before signing in.');
    await browser.close();
    report();
    return;
  }

  await login(PASS);
  row('Sign in with the QA account', 'dashboard', page.url().replace(BASE, ''), /\/wp-admin\/?$/.test(page.url()) && await page.locator('#wpadminbar').count() === 1);
  s = await status('/wp-admin/options-general.php');
  row('Signed in: /wp-admin/ works', '200', String(s), s === 200);

  await page.goto(BASE + '/wp-admin/themes.php', { waitUntil: 'load' });
  const shot = page.locator('.theme.active .theme-screenshot img');
  const src = await shot.getAttribute('src').catch(() => '');
  const nat = await shot.evaluate((i) => i.naturalWidth + '×' + i.naturalHeight).catch(() => '0');
  row('Appearance › Themes shows the PL thumbnail', 'hello-pl-child/screenshot.png 1200×900', `${(src || '').split('/themes/')[1] || src} ${nat}`, /hello-pl-child\/screenshot\.png/.test(src || '') && nat === '1200×900');
  await page.screenshot({ path: process.env.PL_QA_SHOT || '/tmp/pl-themes.png' });

  await page.goto(BASE + '/wp-admin/options-general.php?page=pl-newsletter', { waitUntil: 'domcontentloaded' });
  const txt = await page.locator('#wpbody-content').innerText();
  row('Settings › PL Newsletter opens', 'webhooks section + no secrets printed', /Unsubscribe sync/.test(txt) ? 'ok' : 'missing', /Unsubscribe sync \(webhooks\)/.test(txt) && (await page.locator('input[type=password]').evaluateAll((a) => a.every((i) => i.value === ''))));
  const [dl] = await Promise.all([page.waitForEvent('download'), page.click('text=Export all sign-ups (CSV)')]);
  const fs = require('fs'); const p = await dl.path(); const head = fs.readFileSync(p, 'utf8').replace(/^﻿/, '').split('\n')[0];
  row('Export CSV (admin)', 'CSV with header row', `${dl.suggestedFilename()} · ${head.slice(0, 40)}…`, /^pl-newsletter-signups-\d{4}-\d\d-\d\d\.csv$/.test(dl.suggestedFilename()) && head.startsWith('email,name,status,tags'));


  /* ---- Step 6.2b: PL Help, "?" panel, media gate, guardrails (nothing is saved) ---- */
  await page.goto(BASE + '/wp-admin/admin.php?page=pl-help', { waitUntil: 'load' });
  const nCh = await page.locator('.plh-chapters .plh-card').count(), nTask = await page.locator('.plh-chapter .plh-task').count();
  row('PL Website › Help opens (RTL)', '11 chapters, ≥ 45 tasks, dir=rtl', `${nCh} chapters, ${nTask} tasks, dir=${await page.locator('.wrap.plh').getAttribute('dir')}`, nCh === 11 && nTask >= 45 && (await page.locator('.wrap.plh').getAttribute('dir')) === 'rtl');
  await page.fill('#plh-q', 'WhatsApp');
  const vis = await page.locator('.plh-chapter .plh-task:not([hidden])').count();
  row('Help search', '"WhatsApp" → a few tasks', `${vis} shown`, vis > 0 && vis < nTask);
  await page.fill('#plh-q', '');
  const imgs = await page.$$eval('.plh-fig img', (a) => a.map((i) => i.getAttribute('src')));
  const vids = await page.$$eval('.plh video source', (a) => a.map((s) => s.getAttribute('src')));
  let imgOk = 0; for (const u of imgs.slice(0, 60)) { const r = await page.request.get(u); if (r.ok() && /image\//.test(r.headers()['content-type'] || '')) imgOk++; }
  row('Help pictures load (signed in)', `${Math.min(imgs.length, 60)}/${Math.min(imgs.length, 60)}`, `${imgOk}/${Math.min(imgs.length, 60)}`, imgs.length > 40 && imgOk === Math.min(imgs.length, 60));
  const v0 = vids[0] ? await page.request.get(vids[0], { headers: { Range: 'bytes=0-1023' } }) : null;
  row('Help video plays from the gate (Range)', '206 video/mp4, ≥ 30 videos', v0 ? `${v0.status()} ${v0.headers()['content-type']} · ${vids.length} videos` : 'no video', !!v0 && v0.status() === 206 && /video\/mp4/.test(v0.headers()['content-type'] || '') && vids.length >= 30);
  const auto = await page.$$eval('.plh video', (a) => a.filter((v) => v.autoplay || v.preload !== 'none').length);
  row('Videos never start by themselves', 'no autoplay, preload=none', `${auto} not compliant`, auto === 0);
  row('Cheat sheet print button', 'Print / Save as PDF', (await page.locator('.plh-print').innerText().catch(() => '')).trim(), (await page.locator('.plh-print').count()) === 1);
  const anon = await (await browser.newContext()).newPage();
  for (const u of [vids[0] || imgs[0], BASE + '/wp-content/uploads/pl-help/' + (imgs[0] || '').split('f=')[1]?.split('&')[0].replace(/%2F/gi, '/')]) {
    const r = await anon.goto(u).catch(() => null);
    row('Signed out: help media hidden', '403/404 (never 200)', `${r ? r.status() : 'error'} ${u.replace(BASE, '').slice(0, 70)}`, !!r && r.status() !== 200);
  }
  await page.goto(BASE + '/wp-admin/post-new.php?post_type=event', { waitUntil: 'load' });
  await page.click('#plh-fab');
  const panelOpen = await page.locator('#plh-panel').isVisible(), first = (await page.locator('#plh-panel summary').first().innerText()).trim();
  await page.keyboard.press('Escape');
  const closed = await page.locator('#plh-panel').isHidden(), focusBack = await page.evaluate(() => document.activeElement && document.activeElement.id);
  row('"?" on Add event opens its task in a side panel', 'panel + "إضافة إيفنت جديد"; Esc closes, focus back on ?', `${panelOpen} ${first} · closed ${closed} · focus ${focusBack}`, panelOpen && /إضافة إيفنت/.test(first) && closed && focusBack === 'plh-fab');
  for (const [u, want] of [['/wp-admin/edit.php?post_type=pl_office', 'مكتب'], ['/wp-admin/edit.php?post_type=pl_nl_signup', 'النشرة'], ['/wp-admin/admin.php?page=forminator-entries', 'الرسايل'], ['/wp-admin/admin.php?page=pl-company', 'الهوم']]) {
    await page.goto(BASE + u, { waitUntil: 'domcontentloaded' });
    const t = (await page.locator('#plh-panel summary').allInnerTexts()).join(' / ');
    row('"?" on ' + u.replace('/wp-admin/', ''), 'button + matching task', t.slice(0, 60), (await page.locator('#plh-fab').count()) === 1 && t.includes(want));
  }
  s = await status('/wp-admin/theme-editor.php');
  const fe = await page.locator('#adminmenu a[href="theme-editor.php"], #adminmenu a[href="plugin-editor.php"]').count();
  row('File editor off (DISALLOW_FILE_EDIT)', 'no Theme/Plugin File Editor in the menu', `${fe} menu links`, fe === 0);

  const home = await page.evaluate(async (b) => { const r = await fetch(b + '/wp-json/wp/v2/pages?slug=home&_fields=id'); return (await r.json())[0]?.id; }, BASE);
  await page.goto(BASE + `/wp-admin/post.php?post=${home}&action=elementor`, { waitUntil: 'domcontentloaded' });
  const ed = await page.waitForSelector('#elementor-preview-iframe', { timeout: 90000 }).then(() => true).catch(() => false);
  let loaded = false;
  for (let i = 0; ed && i < 90 && !loaded; i++) { // the preview iframe navigates to ?elementor-preview=ID after the editor boots
    const f = page.frames().find((fr) => /elementor-preview=/.test(fr.url()));
    loaded = !!f && (await f.locator('[data-elementor-type]').count().catch(() => 0)) > 0 && !(await page.locator('#elementor-loading').isVisible().catch(() => false));
    if (!loaded) await page.waitForTimeout(1000);
  }
  row('Elementor editor opens (Home)', 'editor + preview loaded', ed ? (loaded ? 'loaded' : 'iframe, no content') : 'no editor', ed && loaded);

  await page.goto(BASE + '/wp-admin/', { waitUntil: 'domcontentloaded' });
  const out = await page.locator('#wp-admin-bar-logout a').getAttribute('href');
  row('Log out link', '/plevents/?action=logout', out.replace(BASE, '').split('&')[0], /\/plevents\/\?action=logout/.test(out));
  await page.goto(out, { waitUntil: 'domcontentloaded' });
  if (/action=logout/.test(page.url()) && await page.locator('a', { hasText: 'log out' }).count()) await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('a:has-text("log out")')]);
  row('Log out lands on the sign-in form', '/plevents/?loggedout=true', page.url().replace(BASE, ''), /\/plevents\/\?loggedout=true/.test(page.url()) && await page.locator('#loginform').count() === 1);
  s = await status('/wp-admin/');
  row('After log out: /wp-admin/', '404', String(s), s === 404);

  await browser.close();
  report();
})().catch((e) => { console.error(e); process.exit(1); });

function report() {
  const bad = rows.filter((r) => !r.ok).length;
  console.log(`\n${rows.length - bad}/${rows.length} ✅`);
  process.exitCode = bad ? 1 : 0;
}
