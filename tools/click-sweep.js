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
      for (const suite of [home, events, eventPage, about, contactForms, footer]) {
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

main().catch((e) => { console.error(e); process.exit(2); });
