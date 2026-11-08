#!/usr/bin/env node
/*
 * Click sweep (6.1b round 3) — REAL clicks on every interactive element, not href reading (lesson of R16).
 *
 *   node tools/click-sweep.js [base-url] [report.md]
 *   default base: https://saraamrousy.net/pl   default report: click-sweep-report.md (current folder)
 *
 * 375 / 768 / 1280 × Full / Reduced motion. Outside sites are never contacted: every request to another
 * host is answered by a local stub (so new tabs still open and their URL can be checked). Forms are only
 * submitted empty / with an invalid email (validation paths) — no real message or sign-up is ever sent.
 * Round 4 adds: the sign-in address (/plevents/ opens the form, /wp-admin/ and /wp-login.php → 404) and the
 * newsletter link pages (tampered confirm / unsubscribe links get a calm page). The full newsletter flow
 * (sign up → one email → confirm → both providers → unsubscribe) runs only against the sandbox mocks:
 *   PL_NL_MAILLOG=…/mail.jsonl PL_NL_MOCKLOG=…/log.jsonl node tools/click-sweep.js http://localhost:8080
 * Exit code 1 if any check fails. Needs Playwright (preinstalled in the build sandbox).
 */
const { chromium } = require('playwright');
const fs = require('fs');

const BASE = (process.argv[2] || 'https://saraamrousy.net/pl').replace(/\/$/, '');
const OUT = process.argv[3] || 'click-sweep-report.md';
const HOST = new URL(BASE).host;
const rows = [];

async function main() {
  const br = await chromium.launch();
  for (const w of [375, 768, 1280]) {
    for (const motion of ['no-preference', 'reduce']) {
      const label = `${w} ${motion === 'reduce' ? 'Reduced' : 'Full'}`;
      const ctx = await br.newContext({ viewport: { width: w, height: w === 1280 ? 900 : 860 }, reducedMotion: motion, hasTouch: w < 1024, isMobile: w < 1024, acceptDownloads: true });
      await ctx.route('**/*', (route) => { const u = new URL(route.request().url()); return u.host === HOST || u.protocol === 'data:' ? route.continue() : route.fulfill({ status: 200, contentType: 'text/html', body: '<title>stub</title>stub' }); });
      const pg = await ctx.newPage();
      pg.setDefaultTimeout(10000);
      const T = { ctx, pg, w, label };
      for (const suite of [home, events, eventPage, about, contactForms, footer, access, nlLinks, nlFlow]) {
        try { await suite(T); } catch (e) { const m = e.message.split('\n'); rows.push([suite.name, '-', label, 'suite runs', 'crashed: ' + [m[0], ...m.filter((l) => /waiting for locator|intercepts pointer|not visible|not stable/.test(l)).slice(0, 2)].join(' / ').slice(0, 300), false]); }
      }
      await ctx.close();
    }
  }
  await br.close();
  const bad = rows.filter((r) => !r[5]);
  const md = [`# Click sweep — ${BASE}`, '', `${new Date().toISOString()} · ${rows.length} checks · ✅ ${rows.length - bad.length} · ❌ ${bad.length}`, '', '| Element | Page | Size | Expected | What happened | |', '|---|---|---|---|---|---|']
    .concat(rows.map((r) => `| ${r[0]} | ${r[1]} | ${r[2]} | ${r[3]} | ${String(r[4]).replace(/\|/g, '/')} | ${r[5] ? '✅' : '❌'} |`)).join('\n');
  fs.writeFileSync(OUT, md + '\n');
  console.log(`${rows.length} checks · ${rows.length - bad.length} ✅ · ${bad.length} ❌  → ${OUT}`);
  bad.forEach((r) => console.log('❌', r.slice(0, 5).join(' · ')));
  process.exit(bad.length ? 1 : 0);
}

/* ---------- helpers ---------- */
function rec(T, el, page, expect, got, ok) { rows.push([el, page, T.label, expect, got, !!ok]); }
async function go(T, path) { await T.pg.goto(BASE + path, { waitUntil: 'networkidle' }); await T.pg.addStyleTag({ content: '#masthead,header{position:static!important}.ebar-m{position:static!important}' }).catch(() => {}); }
async function vis(loc) { const n = await loc.count(); for (let i = 0; i < n; i++) { if (await loc.nth(i).isVisible()) return loc.nth(i); } return null; }
async function newTab(T, loc) { const [p] = await Promise.all([T.ctx.waitForEvent('page', { timeout: 6000 }).catch(() => null), loc.click()]); if (!p) return null; const u = p.url(); await p.close(); return u; }
async function navigates(T, loc) { const before = T.pg.url(); await Promise.all([T.pg.waitForNavigation({ timeout: 8000 }).catch(() => null), loc.click()]); return T.pg.url() !== before ? T.pg.url().replace(BASE, '') : null; }
async function openMenu(T) { if (T.w >= 1024) return; const b = await vis(T.pg.locator('.menu-btn')); if (b) { await b.click(); await T.pg.waitForTimeout(350); } }

async function cardActions(T, page, scope) {
  const card = await vis(T.pg.locator(`${scope} .card:has(.pl-reg), ${scope} .erow:has(.pl-reg)`));
  if (!card) { rec(T, 'Card actions', page, 'an upcoming card', 'none visible', false); return; }
  await card.scrollIntoViewIfNeeded();
  const reg = card.locator('.pl-reg').first(); let u = await newTab(T, reg); rec(T, 'Register now', page, 'new tab → registration', u || 'no tab', !!u);
  const web = await vis(card.locator('.pl-web')); if (web) { u = await newTab(T, web); rec(T, 'Website', page, 'new tab → society site', u || 'no tab', !!u); }
  const sum = card.locator('details.calm > summary').first(); await sum.click(); await T.pg.waitForTimeout(150);
  const open = await sum.evaluate((s) => s.parentElement.open); rec(T, 'Add to calendar', page, 'menu opens', open ? 'opened' : 'closed', open);
  if (open) {
    const links = card.locator('.cal-menu a');
    u = await newTab(T, links.nth(0)); rec(T, 'Calendar › Google', page, 'new tab calendar.google.com', u ? new URL(u).host : 'no tab', u && /calendar\.google\.com/.test(u));
    await sum.click(); await T.pg.waitForTimeout(150);
    const icsHref = await links.nth(1).getAttribute('href');
    const r = await T.pg.request.get(icsHref);
    const ct = r.headers()['content-type'] || '', cd = r.headers()['content-disposition'] || '';
    const [dl] = await Promise.all([T.pg.waitForEvent('download', { timeout: 5000 }).catch(() => null), links.nth(1).click()]);
    rec(T, 'Calendar › Apple (.ics)', page, '200 text/calendar, named .ics', `${r.status()} ${ct.split(';')[0]} ${(cd.match(/filename="([^"]+)"/) || [])[1] || ''}${dl ? ' · download ' + dl.suggestedFilename() : ''}`, r.status() === 200 && /text\/calendar/.test(ct) && /\.ics/.test(cd));
    if (T.pg.url().includes('/ics/')) await T.pg.goBack({ waitUntil: 'networkidle' }).catch(() => {});
    const card2 = await vis(T.pg.locator(`${scope} .card:has(.pl-reg), ${scope} .erow:has(.pl-reg)`)); const sum2 = card2.locator('details.calm > summary').first();
    if (!(await sum2.evaluate((s) => s.parentElement.open))) { await sum2.click(); await T.pg.waitForTimeout(150); }
    u = await newTab(T, card2.locator('.cal-menu a').nth(2)); rec(T, 'Calendar › Outlook', page, 'new tab outlook.live.com', u ? new URL(u).host : 'no tab', u && /outlook\.live\.com/.test(u));
    await T.pg.keyboard.press('Escape'); await T.pg.waitForTimeout(100);
    rec(T, 'Calendar menu Esc', page, 'closes', (await sum2.evaluate((s) => s.parentElement.open)) ? 'still open' : 'closed', !(await sum2.evaluate((s) => s.parentElement.open)));
    const wa = card2.locator('.wa-btn, .wa-text').first(); u = await newTab(T, wa); rec(T, 'WhatsApp', page, 'new tab wa.me', u ? new URL(u).host : 'no tab', u && /wa\.me/.test(u));
    const title = card2.locator('.ct a, .r-nm a').first(); u = await navigates(T, title); rec(T, 'Card title', page, 'opens the event page', u || 'stayed', u && /\/events\/[^/]+\/$/.test(u));
  }
}

/* ---------- suites ---------- */
async function home(T) {
  await go(T, '/');
  // nav
  await openMenu(T);
  const nav = await vis(T.pg.locator('header a, #masthead a, .menu a').filter({ hasText: /^Events$/ }));
  let u = nav ? await navigates(T, nav) : null; rec(T, 'Nav › Events' + (T.w < 1024 ? ' (menu)' : ''), '/', 'opens /events/', u || 'no', u === '/events/');
  await go(T, '/');
  const prop = await vis(T.pg.locator('a', { hasText: 'Request a proposal' })); u = prop ? await navigates(T, prop) : null; rec(T, 'Request a proposal', '/', '/contact/?topic=proposal', u || 'no', /\/contact\/\?topic=proposal/.test(u || ''));
  await go(T, '/');
  // tabs + row
  const up = T.pg.locator('[data-ht="upcoming"]').first(); await up.scrollIntoViewIfNeeded(); await up.click(); await T.pg.waitForTimeout(250);
  const shown = await T.pg.$$eval('.rail .card:not([hidden])', (c) => c.map((x) => x.dataset.st)); rec(T, 'Tab › Upcoming', '/', 'only upcoming cards', shown.join(','), shown.length && shown.every((s) => s !== 'past'));
  await T.pg.locator('[data-ht="all"]').first().click(); await T.pg.waitForTimeout(250);
  const next = T.pg.locator('[data-rail="next"]').first(); const x0 = await T.pg.$eval('.rail', (r) => r.scrollLeft); await next.click(); await T.pg.waitForTimeout(700);
  const x1 = await T.pg.$eval('.rail', (r) => r.scrollLeft); rec(T, 'Row › Next', '/', 'row scrolls', `${x0}→${x1}`, x1 > x0);
  const prev = T.pg.locator('[data-rail="prev"]').first(); await prev.click(); await T.pg.waitForTimeout(700);
  const x2 = await T.pg.$eval('.rail', (r) => r.scrollLeft); rec(T, 'Row › Previous', '/', 'row scrolls back', `${x1}→${x2}`, x2 < x1);
  await cardActions(T, '/', '.rail');
}

async function events(T) {
  await go(T, '/events/');
  const tab = T.pg.locator('button[data-tab="past"]').first(); await tab.click(); await T.pg.waitForTimeout(250);
  const st = await T.pg.$$eval('.pl-view-grid .card:not([hidden])', (c) => [...new Set(c.map((x) => x.dataset.st))]); rec(T, 'Tab › Past', '/events/', 'only past', st.join(','), st.length === 1 && st[0] === 'past');
  await T.pg.locator('button[data-tab="all"]').first().click(); await T.pg.waitForTimeout(250);
  if (T.w >= 1024) {
    const sel = T.pg.locator('#pl-frow select[data-f="country"]'); await sel.selectOption('AE'); await T.pg.waitForTimeout(300);
    rec(T, 'Filter › Country (dropdown)', '/events/', 'count + URL update', (await T.pg.locator('#count').innerText()) + ' ' + new URL(T.pg.url()).search, /country=AE/.test(T.pg.url()));
  } else {
    const fb = T.pg.locator('#ebar-m [data-act="sheet"]'); await fb.click(); await T.pg.waitForTimeout(400);
    const open = await T.pg.$eval('#sheet', (s) => !s.hidden); rec(T, 'Sticky bar › Filters', '/events/', 'bottom sheet opens', open ? 'opened' : 'closed', open);
    if (open) { await T.pg.locator('#sheet select[data-f="country"]').selectOption('AE'); await T.pg.waitForTimeout(250); await T.pg.keyboard.press('Escape'); await T.pg.waitForTimeout(300); }
    const lbl = (await fb.innerText()).replace(/\s+/g, ' '); rec(T, 'Filter › Country (sheet)', '/events/', '"Filters (1)" + count + URL', lbl + ' · ' + (await T.pg.locator('.pl-count-m').innerText()) + ' · ' + new URL(T.pg.url()).search, /\(1\)/.test(lbl) && /country=AE/.test(T.pg.url()));
  }
  const chip = await vis(T.pg.locator('#chips .chip')); if (chip) { await chip.click(); await T.pg.waitForTimeout(250); rec(T, 'Chip × (remove filter)', '/events/', 'filter removed', new URL(T.pg.url()).search || '(none)', !/country=/.test(T.pg.url())); }
  const q = T.pg.locator('input[name=q]'); await q.fill('asaic'); await T.pg.waitForTimeout(400); rec(T, 'Search "asaic"', '/events/', '3 events', T.w >= 1024 ? await T.pg.locator('#count').innerText() : await T.pg.locator('.pl-count-m').innerText(), /^3 events/.test(T.w >= 1024 ? await T.pg.locator('#count').innerText() : await T.pg.locator('.pl-count-m').innerText()));
  await q.fill(''); await T.pg.waitForTimeout(300);
  const list = await vis(T.pg.locator('button[data-view="list"]')); await list.click(); await T.pg.waitForTimeout(250);
  rec(T, 'Grid/List › List', '/events/', 'list shows', (await T.pg.$eval('.pl-view-list', (l) => !l.hidden)) ? 'list' : 'grid', await T.pg.$eval('.pl-view-list', (l) => !l.hidden));
  await cardActions(T, '/events/ (list)', '.pl-view-list');
  await go(T, '/events/'); await cardActions(T, '/events/ (grid)', '.pl-view-grid');
}

async function eventPage(T) {
  await go(T, '/events/deoss-atmo-2027/');
  const acts = '.ev-acts';
  let u = await newTab(T, T.pg.locator(`${acts} .pl-reg`)); rec(T, 'Register now', 'event page', 'new tab', u || 'no tab', !!u);
  u = await newTab(T, T.pg.locator(`${acts} .pl-web`)); rec(T, 'Congress website', 'event page', 'new tab', u || 'no tab', !!u);
  const sum = T.pg.locator(`${acts} details.calm > summary`); await sum.click(); await T.pg.waitForTimeout(150);
  rec(T, 'Add to calendar', 'event page', 'menu opens', (await sum.evaluate((s) => s.parentElement.open)) ? 'opened' : 'closed', await sum.evaluate((s) => s.parentElement.open));
  u = await newTab(T, T.pg.locator(`${acts} .cal-menu a`).nth(0)); rec(T, 'Calendar › Google', 'event page', 'calendar.google.com', u ? new URL(u).host : 'no tab', /calendar\.google/.test(u || ''));
  u = await newTab(T, T.pg.locator(`${acts} .wa-text`)); rec(T, 'Share on WhatsApp', 'event page', 'wa.me', u ? new URL(u).host : 'no tab', /wa\.me/.test(u || ''));
  const nl = T.pg.locator('form.pl-nl-event'); await nl.scrollIntoViewIfNeeded(); await nl.locator('input[type=email]').fill('not-an-email'); await nl.locator('button[type=submit]').click(); await T.pg.waitForTimeout(300);
  const m = await nl.locator('.pl-nl-msg').innerText(); rec(T, 'Newsletter (invalid email)', 'event page', 'blame-free message, nothing sent', m.slice(0, 50), /doesn’t look complete/.test(m));
  const back = T.pg.locator('a', { hasText: '← All events' }).first(); u = await navigates(T, back); rec(T, '← All events', 'event page', '/events/', u || 'no', u === '/events/');
}

async function about(T) {
  await go(T, '/about/');
  const chip = await vis(T.pg.locator('.story2 .chips [data-cc="EG"], .story2 .chips button, .story2 .chips a').filter({ hasText: 'Egypt' }));
  if (chip) { await chip.scrollIntoViewIfNeeded(); await chip.click(); await T.pg.waitForTimeout(500); const name = await T.pg.locator('.pl-explore:not([hidden]) strong').first().innerText(); rec(T, 'Explorer chip › Egypt', '/about/', 'panel shows Egypt', name.trim(), /Egypt/.test(name)); }
  else rec(T, 'Explorer chip › Egypt', '/about/', 'chip visible', 'not found', false);
  const link = await vis(T.pg.locator('.pl-explore:not([hidden]) a.btn').first()); const txt = link ? (await link.innerText()).trim() : '';
  const u = link ? await navigates(T, link) : null; rec(T, 'Explorer › "' + txt + '"', '/about/', 'opens Events (filtered)', u || 'no', /\/events\//.test(u || ''));
}

async function contactForms(T) {
  for (const [p, n] of [['/contact/', 3], ['/careers/', 3]]) {
    await go(T, p);
    const f = T.pg.locator('.forminator-custom-form').first(); await f.scrollIntoViewIfNeeded();
    await f.locator('.forminator-button-submit').click(); await T.pg.waitForTimeout(1200);
    const errs = await f.locator('.forminator-error-message').filter({ visible: true }).count();
    rec(T, 'Form › Send (empty)', p, `${n} field messages, nothing sent`, `${errs} messages`, errs >= n);
    if (p === '/contact/') { const box = f.locator('input[type=checkbox][value="updates"]'); rec(T, 'Updates checkbox', p, 'unticked by default', (await box.isChecked()) ? 'ticked' : 'unticked', !(await box.isChecked())); }
  }
}

async function footer(T) {
  await go(T, '/services/');
  for (const name of ['Events', 'About', 'Contact']) {
    await go(T, '/services/');
    const a = await vis(T.pg.locator('footer nav a', { hasText: new RegExp('^' + name + '$') })); const u = a ? await navigates(T, a) : null;
    rec(T, 'Footer › ' + name, '/services/', 'opens /' + name.toLowerCase() + '/', u || 'no', u === '/' + name.toLowerCase() + '/');
  }
  await go(T, '/services/');
  const soc = await vis(T.pg.locator('footer a', { hasText: 'LinkedIn' })); let u = soc ? await newTab(T, soc) : null; rec(T, 'Footer › LinkedIn', '/services/', 'new tab', u ? new URL(u).host : 'no tab', !!u);
  const nl = T.pg.locator('form.pl-nl-footer'); await nl.scrollIntoViewIfNeeded();
  const topics = nl.locator('.pl-nl-topics > summary'); await topics.click(); await T.pg.waitForTimeout(150);
  rec(T, 'Newsletter › Choose topics', 'footer', 'opens', (await topics.evaluate((s) => s.parentElement.open)) ? 'opened' : 'closed', await topics.evaluate((s) => s.parentElement.open));
  await nl.locator('button[type=submit]').click(); await T.pg.waitForTimeout(300);
  const m = await nl.locator('.pl-nl-msg').innerText(); rec(T, 'Newsletter › Subscribe (empty)', 'footer', 'message, nothing sent', m.slice(0, 50), /doesn’t look complete/.test(m));
}

async function access(T) {
  let r = await T.pg.goto(BASE + '/plevents', { waitUntil: 'domcontentloaded' });
  rec(T, 'Sign-in address', '/plevents', 'sign-in form (200)', `${r.status()} ${T.pg.url().replace(BASE, '')}`, r.status() === 200 && (await T.pg.locator('#loginform').isVisible()));
  const lost = await vis(T.pg.locator('#nav a', { hasText: 'Lost your password' })); const u = lost ? await navigates(T, lost) : null;
  rec(T, 'Sign-in › Lost your password?', '/plevents/', 'opens the reset form at /plevents/', u || 'no', /^\/plevents\/\?action=lostpassword/.test(u || '') && (await T.pg.locator('#lostpasswordform').isVisible()));
  for (const p of ['/wp-admin/', '/wp-admin/admin.php?page=pl-help', '/wp-login.php']) {
    r = await T.pg.goto(BASE + p, { waitUntil: 'domcontentloaded' });
    rec(T, 'Signed out', p, '404', String(r.status()), r.status() === 404);
  }
  const h = await vis(T.pg.locator('a', { hasText: 'Go to the PL Events homepage' })); const hu = h ? await navigates(T, h) : null;
  rec(T, '404 › Go to the homepage', '/wp-login.php', 'opens /', hu || 'no', hu === '/');
  // Step 6.2b: PL Help media is staff-only (gate + .htaccess); never reachable by a visitor.
  for (const p of ['/wp-admin/admin-post.php?action=pl_help_media&f=shots%2Fevent-add-1.png', '/wp-content/uploads/pl-help/shots/event-add-1.png', '/wp-content/uploads/pl-help/video/event-add.mp4']) {
    r = await T.pg.goto(BASE + p, { waitUntil: 'domcontentloaded' }).catch(() => null);
    const st = r ? r.status() : 0;
    rec(T, 'Signed out: PL Help media hidden', p.split('?')[0].replace('/wp-content/uploads', '…'), 'not 200', String(st), st !== 200 && st !== 206);
  }
}

async function nlLinks(T) {
  await go(T, '/?pl_nl_act=confirm&r=1&x=9999999999&s=tampered');
  let t = await T.pg.locator('main').innerText();
  rec(T, 'Newsletter › tampered confirm link', '/?pl_nl_act=confirm', 'calm page, sign up again', t.split('\n')[0], /isn’t complete/.test(t) && /sign up again/i.test(t) && !/invalid|wrong/i.test(t));
  await go(T, '/?pl_nl_act=unsubscribe&r=1&s=tampered');
  t = await T.pg.locator('main').innerText();
  const w = await vis(T.pg.locator('main a', { hasText: 'write to us' })); const u = w ? await navigates(T, w) : null;
  rec(T, 'Newsletter › tampered unsubscribe link › write to us', '/?pl_nl_act=unsubscribe', 'calm page → Contact', u || 'no', /isn’t complete/.test(t) && /^\/contact\//.test(u || ''));
}

async function nlFlow(T) {
  const ML = process.env.PL_NL_MAILLOG, PL = process.env.PL_NL_MOCKLOG;
  if (!ML || !PL || /Reduced/.test(T.label)) return; // sandbox mocks only; once per size
  const rd = (f) => (fs.existsSync(f) ? fs.readFileSync(f, 'utf8') : '');
  const email = `sweep.${T.w}.${Date.now()}@example.org`;
  await go(T, '/services/');
  const nl = T.pg.locator('form.pl-nl-footer'); await nl.scrollIntoViewIfNeeded();
  await nl.locator('input[type=email]').fill(email);
  await nl.locator('button[type=submit]').click();
  await T.pg.waitForFunction(() => /inbox/.test(document.querySelector('form.pl-nl-footer .pl-nl-msg')?.textContent || ''), null, { timeout: 8000 }).catch(() => {});
  const m = await nl.locator('.pl-nl-msg').innerText();
  rec(T, 'Newsletter › Subscribe (real, mock)', 'footer', '"check your inbox"', m.slice(0, 50), /check your inbox/.test(m));
  const mails = rd(ML).trim().split('\n').filter(Boolean).map((l) => JSON.parse(l)).filter((x) => [].concat(x.to).includes(email));
  rec(T, 'Newsletter › confirmation email', 'mail', 'exactly one', String(mails.length), mails.length === 1);
  const link = (mails[0]?.message.match(/href="([^"]*pl_nl_act=confirm[^"]*)"/) || [])[1]?.replace(/&#038;|&amp;/g, '&');
  await T.pg.goto(link, { waitUntil: 'domcontentloaded' });
  const before = rd(PL);
  const btn = await vis(T.pg.locator('main button', { hasText: 'Confirm my subscription' }));
  if (btn) await Promise.all([T.pg.waitForNavigation(), btn.click()]);
  const h1 = await T.pg.locator('main h1').innerText();
  const calls = rd(PL).slice(before.length);
  rec(T, 'Newsletter › Confirm my subscription', 'confirm page', 'subscribed in Mailchimp + Zoho', h1, /You’re subscribed/.test(h1) && /"m":"PUT".*mailchimp/.test(calls) && calls.includes('listsubscribe') && calls.includes(encodeURIComponent(email).replace('%40', '@')) !== null);
  const un = await vis(T.pg.locator('main a', { hasText: 'Unsubscribe' }));
  if (un) await navigates(T, un);
  const b2 = await vis(T.pg.locator('main button', { hasText: 'Unsubscribe me' }));
  const before2 = rd(PL);
  if (b2) await Promise.all([T.pg.waitForNavigation(), b2.click()]);
  const h2 = await T.pg.locator('main h1').innerText();
  const c2 = rd(PL).slice(before2.length);
  rec(T, 'Newsletter › Unsubscribe me', 'unsubscribe page', 'removed from both', h2, /You’re unsubscribed/.test(h2) && /PATCH/.test(c2) && c2.includes('listunsubscribe'));
}

main().catch((e) => { console.error(e); process.exit(2); });
