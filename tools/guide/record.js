#!/usr/bin/env node
/**
 * PL guide recorder (Step 6.2b). Reads tools/guide/guide.json and, on the SANDBOX only, produces:
 *   out/shots/<id>.png        screenshots 1440×900 (mobile: 390×844 @2x), numbered callouts drawn in the page
 *   out/pairs/<id>.png        pairing shots: the wp-admin field + the same thing on the site, same numbers
 *   out/video/<task>.mp4      one video per task, 1280×800, H.264, cursor + click circles + Arabic captions
 *   out/video/<task>.vtt      the same captions as a WebVTT track; out/video/<task>.jpg poster
 *   out/manifest.json         what was made (sizes, durations, callout checks)
 * After every task the database and uploads go back exactly as they were.
 *
 *   node tools/guide/record.js [--only id,id] [--no-video] [--no-shots]
 * Normally run through tools/guide/run.sh (creates the temporary QA user, checks the data afterwards).
 */
const fs = require('fs'), path = require('path'), cp = require('child_process');
const { chromium } = require('playwright');

const DIR = __dirname, OUT = path.join(DIR, 'out');
const BASE = (process.env.PL_GUIDE_BASE || 'http://localhost:8080').replace(/\/$/, '');
if (!/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(BASE)) { console.error('The recorder changes data: sandbox only (localhost).'); process.exit(2); }
const USER = process.env.PL_QA_USER, PASS = process.env.PL_QA_PASS;
const DB = process.env.PL_GUIDE_DB, UPLOADS = process.env.PL_GUIDE_UPLOADS;
if (!USER || !PASS || !DB || !UPLOADS) { console.error('Set PL_QA_USER, PL_QA_PASS, PL_GUIDE_DB, PL_GUIDE_UPLOADS (run.sh does).'); process.exit(2); }
const FONT_BASE = BASE + '/wp-content/plugins/pl-events-core/assets/fonts/';
// Fonts as data: URLs for pages we render ourselves (file:// and setContent can't load cross-origin fonts).
const FONT_DIR = path.join(DIR, '..', '..', 'wp-content', 'plugins', 'pl-events-core', 'assets', 'fonts');
const b64 = f => 'data:font/woff2;base64,' + fs.readFileSync(path.join(FONT_DIR, f)).toString('base64');
const FONT_CSS = `@font-face{font-family:A;font-weight:400 600;src:url(${b64('plex-arabic-500.woff2')}) format("woff2")}@font-face{font-family:A;font-weight:700 900;src:url(${b64('plex-arabic-700.woff2')}) format("woff2")}@font-face{font-family:M;font-weight:400 800;src:url(${b64('montserrat-normal-latin.woff2')}) format("woff2")}`;
const argv = process.argv.slice(2);
const ONLY = (argv.find(a => a.startsWith('--only=')) || '').replace('--only=', '').split(',').filter(Boolean);
const NO_VIDEO = argv.includes('--no-video'), NO_SHOTS = argv.includes('--no-shots');
const guide = JSON.parse(fs.readFileSync(path.join(DIR, 'guide.json'), 'utf8'));
const OVERLAY = fs.readFileSync(path.join(DIR, 'overlay.js'), 'utf8');
for (const d of ['shots', 'pairs', 'video', 'tmp']) fs.mkdirSync(path.join(OUT, d), { recursive: true });
const manifestFile = path.join(OUT, 'manifest.json');
const manifest = fs.existsSync(manifestFile) ? JSON.parse(fs.readFileSync(manifestFile, 'utf8')) : { shots: {}, pairs: {}, videos: {} };
const problems = [];
const log = (...a) => console.log(...a);

/* ---------------------------------------------------------------- data restore */
const BAK = path.join(OUT, 'tmp', 'base.sqlite');
const listFiles = d => { const out = []; (function walk(p) { for (const f of fs.readdirSync(p, { withFileTypes: true })) { const q = path.join(p, f.name); f.isDirectory() ? walk(q) : out.push(q); } })(d); return out; };
let baseUploads;
const DBSNAP = path.join(DIR, 'dbsnap.py'); // WAL-safe (SQLite backup API): a plain file copy misses the -wal writes
function snapshot() { cp.execFileSync('python3', [DBSNAP, 'save', DB, BAK]); baseUploads = new Set(listFiles(UPLOADS)); }
function restore() {
  cp.execFileSync('python3', [DBSNAP, 'restore', BAK, DB]);
  for (const f of listFiles(UPLOADS)) if (!baseUploads.has(f)) fs.unlinkSync(f);
}

/* ------------------------------------------------------------------- helpers */
const WPCLI = (process.env.PL_SANDBOX || '/home/user/wp-sandbox');
function php(code) { // sandbox-only data setup (e.g. put a small image on an event); restored after the task
  return cp.execFileSync('php', ['-d', 'memory_limit=512M', WPCLI + '/.bin/wp', '--allow-root', '--path=' + WPCLI + '/site', 'eval', code], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
}
// "@edit:event:deoss-atmo-2027" → /wp-admin/post.php?post=57&action=edit ; "@id:pl_office:alexandria" → 9
function resolve(p) {
  p = String(p).replace(/@form:([a-z]+)/g, (m, k) => php(`echo (int) ( (array) get_option( 'plt_forms' ) )['${k}'];`));
  return String(p).replace(/@(edit|id|view):([a-z_]+):([a-z0-9-]+)/g, (m, k, type, slug) => {
    const id = php(`$p = get_posts( array( 'post_type' => '${type}', 'name' => '${slug}', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ); echo $p ? $p[0] : 0;`);
    return k === 'edit' ? `/wp-admin/post.php?post=${id}&action=edit` : k === 'view' ? php(`echo wp_make_link_relative( get_permalink( ${id} ) );`) : id;
  });
}
// Image sizes from the plugin (PL_Image_Spec — one source): fills {{spec:key.field}} in captions and feeds the drawings.
let SPEC = null;
function spec() { if (!SPEC) SPEC = JSON.parse(php('echo wp_json_encode( PL_Image_Spec::all(), JSON_UNESCAPED_UNICODE );')); return SPEC; }
function fillSpec(t) {
  return String(t).replace(/\{\{spec:([a-z_]+)\.([a-z_]+)\}\}/g, (m, k, f) => {
    const v = (spec()[k] || {})[f]; if (v === undefined) return m;
    if (f === 'upload' || f === 'min') return v[0] + ' × ' + v[1] + ' px';
    if (f === 'max_kb') return v >= 1024 ? (Math.round(v / 102.4) / 10) + ' MB' : v + ' KB';
    return Array.isArray(v) ? v.join(' · ') : String(v);
  });
}
const sleep = ms => new Promise(r => setTimeout(r, ms));
const readMs = t => Math.max(2300, 900 + String(t).replace(/\*\*/g, '').length * 62);
const url = p => /^https?:/.test(p) ? p : BASE + resolve(p);
let storage = null;
async function login(browser) {
  const ctx = await browser.newContext();
  const p = await ctx.newPage();
  await p.goto(BASE + '/plevents/');
  await p.fill('#user_login', USER); await p.fill('#user_pass', PASS);
  await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
  storage = await ctx.storageState();
  await ctx.close();
}
async function newCtx(browser, o) {
  const opt = { viewport: o.viewport, deviceScaleFactor: o.dsf || 1, locale: 'en-GB', reducedMotion: o.reducedMotion || 'reduce', storageState: o.anon ? undefined : storage };
  if (o.video) opt.recordVideo = { dir: path.join(OUT, 'tmp'), size: o.viewport };
  if (o.isMobile) Object.assign(opt, { isMobile: true, hasTouch: true });
  const ctx = await browser.newContext(opt);
  await ctx.addInitScript(`window.__PL_FONT_BASE=${JSON.stringify(FONT_BASE)};window.__PL_VIDEO=${o.video ? 'true' : 'false'};window.__PL_BLUR=${JSON.stringify(guide.blur || [])};`);
  await ctx.addInitScript(OVERLAY);
  await ctx.addInitScript(`(() => { const s = document.createElement('style'); s.textContent = ${JSON.stringify(guide.hide_css || '')}; document.addEventListener('DOMContentLoaded', () => document.head.appendChild(s)); })();`);
  // Outside hosts never get a request (the sandbox can't reach them anyway).
  await ctx.route(/^https?:\/\/(?!localhost|127\.0\.0\.1)/, r => r.fulfill({ status: 204, body: '' }));
  return ctx;
}
async function settle(page) { await page.waitForLoadState('domcontentloaded').catch(() => {}); await page.waitForLoadState('load', { timeout: 8000 }).catch(() => {}); await page.evaluate(() => document.fonts && document.fonts.ready).catch(() => {}); await sleep(250); }
const loc = (page, sel) => page.locator(resolve(sel)).first();
async function center(page, sel) {
  const l = loc(page, sel);
  await l.scrollIntoViewIfNeeded().catch(() => {});
  const b = await l.boundingBox();
  if (!b) throw new Error('not visible: ' + sel);
  return b;
}

/* --------------------------------------------------------------------- actions */
// video=true: animated (mouse glides, typing, reading pauses). video=false: as fast as possible.
async function run(page, actions, video, caps) {
  const t0 = run.t0;
  for (const a of actions || []) {
    const capNow = async (t) => {
      if (!video || !t) return;
      t = fillSpec(t);
      for (let i = 0; i < 4; i++) { // after Publish WordPress redirects twice: wait for the page that stays
        try { await page.waitForLoadState('domcontentloaded'); await page.evaluate(x => window.__pl && window.__pl.cap(x), t); break; }
        catch (e) { if (!/context was destroyed|navigation/i.test(e.message) || i === 3) throw e; await sleep(800); }
      }
      caps.push({ t: (Date.now() - t0) / 1000, text: t });
      await sleep(readMs(t));
    };
    const glide = async (sel) => {
      const b = await center(page, sel);
      const x = b.x + Math.min(b.width / 2, 60), y = b.y + b.height / 2;
      if (video) {
        await page.evaluate(bx => window.__pl && window.__pl.ringBox(bx, true), [b.x, b.y, b.width, b.height]);
        await page.mouse.move(x, y, { steps: 28 }); await sleep(450);
      } else await page.mouse.move(x, y);
      return { x, y };
    };
    try {
      if (a.cap && !a.click && !a.fill && !a.select && !a.check && !a.go && !a.select2 && !a.date) await capNow(a.cap);
      if (a.php) php(resolve(a.php));
      if (a.go) { if (a.cap) await capNow(a.cap); await page.goto(url(a.go), { waitUntil: 'domcontentloaded', timeout: 120000 }); await settle(page); if (video) await sleep(500); }
      if (a.scroll) { await page.evaluate(([s, sm]) => { const e = document.querySelector(s); e && e.scrollIntoView({ block: 'center', behavior: sm ? 'smooth' : 'instant' }); }, [a.scroll, video]).catch(() => {}); await sleep(video ? 1000 : 150); }
      if (a.scrollTop) { await page.evaluate(() => scrollTo(0, 0)); }
      if (a.ring) { if (video) { const b = await center(page, a.ring); await page.evaluate(bx => window.__pl.ringBox(bx, true), [b.x, b.y, b.width, b.height]); } }
      if (a.hover) { await glide(a.hover); await loc(page, a.hover).hover(); await sleep(video ? 700 : 200); }
      if (a.click) {
        if (a.cap) await capNow(a.cap);
        await glide(a.click);
        const nav = page.waitForNavigation({ timeout: a.nav === false ? 1 : 15000 }).catch(() => {});
        await loc(page, a.click).click(a.force ? { force: true } : {});
        if (a.nav) await nav;
        await settle(page); await sleep(video ? 650 : 150);
      }
      if (a.fill) {
        if (a.cap) await capNow(a.cap);
        await glide(a.fill);
        const l = loc(page, a.fill);
        await l.click(); await l.fill('');
        if (video) await l.pressSequentially(String(a.text), { delay: 55 }); else await l.fill(String(a.text));
        if (a.blur !== false) await l.evaluate(e => e.dispatchEvent(new Event('change', { bubbles: true })));
        await sleep(video ? 600 : 100);
      }
      if (a.select) { if (a.cap) await capNow(a.cap); await glide(a.select); await loc(page, a.select).selectOption(a.value); await sleep(video ? 700 : 100); }
      if (a.check) { if (a.cap) await capNow(a.cap); await glide(a.check); await loc(page, a.check).click(); await sleep(video ? 650 : 100); }
      if (a.select2) { // SCF dropdown with search (Type, Specialty, Series, Country)
        if (a.cap) await capNow(a.cap);
        await glide(a.select2 + ' .select2-selection');
        await loc(page, a.select2 + ' .select2-selection').click();
        const box = page.locator('.select2-container--open .select2-search__field').first();
        if (video) await box.pressSequentially(String(a.text), { delay: 70 }); else await box.fill(String(a.text));
        await page.waitForSelector('.select2-results__option--highlighted, .select2-results__option[aria-selected]', { timeout: 10000 }).catch(() => {});
        await sleep(video ? 700 : 300); await page.keyboard.press('Enter'); await sleep(video ? 600 : 150);
      }
      if (a.date) { // SCF date picker: open it (so it's seen), then set the date
        if (a.cap) await capNow(a.cap);
        const sel = '[data-name="' + a.date + '"] input.hasDatepicker';
        await glide(sel); await loc(page, sel).click(); await sleep(video ? 900 : 100);
        await page.evaluate(([s2, v]) => { const $ = window.jQuery; const d = v.split('-').map(Number); $(s2).datepicker('setDate', new Date(d[0], d[1] - 1, d[2])).trigger('change'); }, [sel, a.value]);
        await sleep(video ? 500 : 100); await page.keyboard.press('Escape'); await sleep(video ? 500 : 100);
      }
      if (a.press) { await page.keyboard.press(a.press); await sleep(video ? 500 : 100); }
      if (a.eval) { await page.evaluate(a.eval); await sleep(video ? 400 : 100); }
      if (a.wait) await sleep(video ? a.wait : Math.min(a.wait, 400));
      if (a.waitFor) await page.waitForSelector(a.waitFor, { timeout: 20000 }).catch(() => {});
      if (a.unring && video) await page.evaluate(() => window.__pl.unring());
      if (a.uncap && video) await page.evaluate(() => window.__pl.cap(null));
    } catch (e) {
      if (!a.optional) throw new Error(JSON.stringify(a).slice(0, 120) + ' → ' + e.message.split('\n')[0]);
    }
  }
}

/* --------------------------------------------------------------------- shots */
const SIZES = { desktop: { viewport: { width: 1440, height: 900 }, dsf: 1 }, mobile: { viewport: { width: 390, height: 844 }, dsf: 2, isMobile: true } };
async function shot(browser, task, s, file) {
  const size = SIZES[s.size || 'desktop'];
  const ctx = await newCtx(browser, { ...size, anon: s.anon, reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  try {
    await page.goto(url(s.page), { waitUntil: 'domcontentloaded', timeout: 120000 }); await settle(page);
    await run(page, s.do, false, []);
    if (s.scroll) { await page.evaluate(([sel, blk]) => { const e = document.querySelector(sel); e && e.scrollIntoView({ block: blk, behavior: 'instant' }); }, [s.scroll, s.block || 'center']).catch(() => {}); await sleep(300); }
    await page.evaluate(() => window.__pl && window.__pl.blur());
    await page.evaluate(() => window.__pl && window.__pl.cap(null));
    const boxes = [], checks = [];
    for (const c of s.callouts || []) {
      const b = await loc(page, c.sel).boundingBox().catch(() => null);
      const vw = size.viewport.width, vh = size.viewport.height;
      const ok = !!b && b.width > 0 && b.y + b.height > 4 && b.y < vh - 4 && b.x + b.width > 4 && b.x < vw - 4;
      checks.push({ n: c.n, ok, sel: c.sel });
      if (!ok) problems.push(`${file}: callout ${c.n} (${c.sel}) ${b ? 'off-screen' : 'not found'}`);
      if (b) boxes.push({ n: c.n, box: [b.x, b.y, b.width, b.height] });
    }
    await page.evaluate(list => window.__pl.callouts(list), boxes);
    await sleep(150);
    const f = path.join(OUT, file);
    await page.screenshot({ path: f });
    return { file, w: size.viewport.width * size.dsf, h: size.viewport.height * size.dsf, callouts: checks, bytes: fs.statSync(f).size };
  } finally { await ctx.close(); }
}

async function diagram(browser, d) {
  // Our own drawings (tools/guide/diagrams/<name>.html), rendered at 1440 wide, @2x for the deck.
  const ctx = await browser.newContext({ viewport: { width: d.width || 1440, height: d.height || 900 }, deviceScaleFactor: 2 });
  if (d.spec) await ctx.addInitScript(`window.__SPEC=${JSON.stringify(spec())};window.__SPEC_KEY=${JSON.stringify(d.spec)};`);
  const page = await ctx.newPage();
  await page.goto('file://' + path.join(DIR, 'diagrams', d.diagram + '.html'));
  await page.addStyleTag({ content: FONT_CSS });
  await page.evaluate(() => document.fonts.ready); await sleep(400);
  const f = path.join(OUT, 'shots', d.id + '.png');
  await page.screenshot({ path: f, fullPage: true }); await ctx.close();
  return { file: 'shots/' + d.id + '.png', diagram: true, bytes: fs.statSync(f).size };
}

async function pairImage(browser, id, adminFile, siteFile, p) {
  // One picture: wp-admin on the right (RTL reading order), the site on the left, an arrow between.
  const img = f => 'data:image/png;base64,' + fs.readFileSync(path.join(OUT, f)).toString('base64');
  const html = `<!doctype html><html dir="rtl"><head><meta charset="utf-8"><style>
${FONT_CSS}
body{margin:0;background:#FAFAF7;font-family:A,M;color:#0A0D18;width:1600px;padding:40px 48px;box-sizing:border-box}
h1{font:700 34px/1.3 A,M;margin:0 0 6px}p{font:500 20px/1.6 A,M;color:#57534C;margin:0 0 26px}
.row{display:flex;gap:28px;align-items:center}.col{flex:1}.lab{font:700 14px/1 M;letter-spacing:.12em;text-transform:uppercase;color:#2C4894;margin:0 0 10px;direction:ltr;text-align:right}
.col img{width:100%;border-radius:10px;border:1px solid #DAD6CC;display:block;box-shadow:0 8px 24px rgba(10,13,24,.08)}
.ar{font:800 44px/1 M;color:#2C4894}.legend{display:flex;gap:28px;flex-wrap:wrap;margin-top:22px;font:500 19px/1.5 A,M}
.legend span{display:inline-flex;gap:10px;align-items:center}.legend i{font:700 15px/30px M;font-style:normal;width:30px;height:30px;border-radius:50%;background:#2C4894;color:#fff;text-align:center}
b{font-family:M;font-weight:700;unicode-bidi:isolate}
</style></head><body><h1>${p.title}</h1><p>${p.text || ''}</p><div class="row">
<div class="col"><div class="lab">wp-admin</div><img src="${img(adminFile)}"></div><div class="ar">←</div>
<div class="col"><div class="lab">The website</div><img src="${img(siteFile)}"></div></div>
<div class="legend">${(p.legend || []).map((t, i) => `<span><i>${i + 1}</i>${t.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')}</span>`).join('')}</div></body></html>`;
  const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 }, deviceScaleFactor: 1 });
  const page = await ctx.newPage(); await page.setContent(html); await page.evaluate(() => document.fonts.ready); await sleep(200);
  const f = path.join(OUT, 'pairs', id + '.png');
  await page.screenshot({ path: f, fullPage: true }); await ctx.close();
  return { file: 'pairs/' + id + '.png', bytes: fs.statSync(f).size };
}

/* --------------------------------------------------------------------- video */
const vtt = s => { const h = Math.floor(s / 3600), m = Math.floor(s / 60) % 60, x = (s % 60).toFixed(3).padStart(6, '0'); return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${x}`; };
async function video(browser, task) {
  const v = task.video;
  const ctx = await newCtx(browser, { viewport: { width: 1280, height: 800 }, video: true, reducedMotion: v.motion || 'reduce', anon: v.anon });
  const page = await ctx.newPage();
  run.t0 = Date.now();
  const caps = [];
  await page.goto(url(v.start || '/wp-admin/index.php'), { waitUntil: 'domcontentloaded', timeout: 120000 }); await settle(page);
  await page.mouse.move(640, 420);
  // Title card — the video starts here (the page load before it is cut off)
  const cardAt = (Date.now() - run.t0) / 1000;
  await page.evaluate(([k, t, w]) => window.__pl.card(k, t, w), [guide.chapters.find(c => c.id === task.chapter).kicker || 'PL Help', fillSpec(task.title), fillSpec(task.why)]);
  caps.push({ t: (Date.now() - run.t0) / 1000, text: task.title + ' — ' + task.why });
  await sleep(Math.max(3600, readMs(task.why) - 400));
  await page.evaluate(() => window.__pl.uncard()); await sleep(500);
  await run(page, v.actions, true, caps);
  if (v.end !== false) {
    await page.evaluate(() => window.__pl.cap(null));
    await page.evaluate(t => window.__pl.card('✓', t, ''), v.done || 'تمام كده.');
    caps.push({ t: (Date.now() - run.t0) / 1000, text: v.done || 'تمام كده.' });
    await sleep(2200);
  }
  const dur = (Date.now() - run.t0) / 1000; // (minus the cut, below)
  const raw = await page.video().path();
  await ctx.close();
  const out = path.join(OUT, 'video', task.id + '.mp4');
  const cut = Math.max(0.4, cardAt - 0.15), len = dur - cut; // the video starts at the title card
  const enc = crf => cp.execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', String(Math.max(0.4, cardAt - 0.15)), '-i', raw, '-vf', 'fps=20,format=yuv420p', '-c:v', 'libx264', '-preset', 'veryfast', '-crf', String(crf), '-tune', 'stillimage', '-movflags', '+faststart', '-an', out]);
  enc(30);
  if (fs.statSync(out).size > 2.9e6) enc(35);
  cp.execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', String(Math.min(len * 0.45, 20)), '-i', out, '-frames:v', '1', '-q:v', '4', path.join(OUT, 'video', task.id + '.jpg')]);
  fs.unlinkSync(raw);
  // WebVTT (same captions, for the <track>)
  const lines = ['WEBVTT', ''];
  caps.forEach((c, i) => { const s = Math.max(0, c.t - cut), e = Math.max(s + 1, (caps[i + 1] ? caps[i + 1].t : dur) - cut); lines.push(String(i + 1), `${vtt(s)} --> ${vtt(e)}`, c.text.replace(/\*\*(.+?)\*\*/g, '$1').replace(/^\[\d+\]\s*/, ''), ''); });
  fs.writeFileSync(path.join(OUT, 'video', task.id + '.vtt'), lines.join('\n'));
  const bytes = fs.statSync(out).size;
  if (len < 28 || len > 62) problems.push(`${task.id}.mp4: ${len.toFixed(0)} s (target 30–60)`);
  if (bytes > 3.2e6) problems.push(`${task.id}.mp4: ${(bytes / 1e6).toFixed(1)} MB (target < 3)`);
  return { file: 'video/' + task.id + '.mp4', poster: 'video/' + task.id + '.jpg', vtt: 'video/' + task.id + '.vtt', seconds: Math.round(len), bytes };
}

/* ---------------------------------------------------------------------- main */
(async () => {
  const browser = await chromium.launch();
  snapshot();
  const tasks = guide.tasks.filter(t => !ONLY.length || ONLY.includes(t.id));
  for (const t of tasks) {
    const t1 = Date.now();
    try {
      if (!NO_SHOTS) {
        for (const s of t.screenshots || []) {
          if (s.diagram) { manifest.shots[s.id] = await diagram(browser, s); continue; }
          restore(); await login(browser);
          manifest.shots[s.id] = await shot(browser, t, s, 'shots/' + s.id + '.png');
        }
        if (t.pair) {
          restore(); await login(browser);
          const a = await shot(browser, t, t.pair.admin, 'shots/' + t.pair.id + '-admin.png');
          const b = await shot(browser, t, t.pair.site, 'shots/' + t.pair.id + '-site.png');
          const c = await pairImage(browser, t.pair.id, a.file, b.file, t.pair);
          manifest.pairs[t.pair.id] = { admin: a, site: b, image: c };
        }
      }
      if (!NO_VIDEO && t.video && t.video.actions) {
        restore(); await login(browser);
        manifest.videos[t.id] = await video(browser, t);
      }
      log(`✓ ${t.id} (${((Date.now() - t1) / 1000).toFixed(0)} s)${manifest.videos[t.id] ? ` video ${manifest.videos[t.id].seconds}s ${(manifest.videos[t.id].bytes / 1e6).toFixed(2)}MB` : ''}`);
    } catch (e) {
      problems.push(`${t.id}: ${e.message}`);
      log(`✗ ${t.id}: ${e.message}`);
    } finally {
      restore();
    }
  }
  await browser.close();
  manifest.generated = new Date().toISOString();
  manifest.problems = problems;
  fs.writeFileSync(manifestFile, JSON.stringify(manifest, null, 1));
  log(problems.length ? `\n${problems.length} problem(s):\n- ` + problems.join('\n- ') : '\nAll done, no problems.');
  process.exit(problems.length ? 1 : 0);
})();
