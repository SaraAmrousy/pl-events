#!/usr/bin/env node
/* Equal-height check (6.1b DS7, round 6): every visible card row on Home + Events (All/Upcoming/Past)
   at 1280/1024/768/375 → Δheight of cards and Δy of their bottom actions per row must be 0 px.
     node tools/card-rows.js [base]   exit 1 on any row ≠ 0 */
const { chromium } = require('playwright');
const BASE = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
(async () => {
  const b = await chromium.launch(); let bad = 0, rows = 0;
  for (const w of [1280, 1024, 768, 375]) {
    const ctx = await b.newContext({ viewport: { width: w, height: 900 }, reducedMotion: 'reduce' });
    await ctx.route(/^https?:\/\/(?!localhost|127\.0\.0\.1|saraamrousy\.net)/, r => r.abort());
    const p = await ctx.newPage();
    for (const u of ['/', '/events/', '/events/?tab=upcoming', '/events/?tab=past']) {
      await p.goto(BASE + u, { waitUntil: 'load' }); await p.waitForTimeout(600);
      const r = await p.evaluate(() => {
        const cards = [...document.querySelectorAll('article.pl-card')].filter(c => c.offsetParent && !c.hidden);
        const by = {};
        cards.forEach(c => { const t = Math.round(c.getBoundingClientRect().top + scrollY); (by[t] = by[t] || []).push(c); });
        return Object.values(by).filter(g => g.length > 1).map(g => {
          const h = g.map(c => Math.round(c.getBoundingClientRect().height));
          const a = g.map(c => { const s = c.querySelector('.ca-sub') || c.querySelector('.ca'); return s ? Math.round(s.getBoundingClientRect().bottom) : 0; });
          return [Math.max(...h) - Math.min(...h), Math.max(...a) - Math.min(...a), g.length];
        });
      });
      for (const [dh, da, n] of r) { rows++; if (dh || da) { bad++; console.log(`❌ ${w} ${u}: row of ${n} Δheight ${dh} Δactions ${da}`); } }
    }
    await ctx.close();
  }
  await b.close();
  console.log(`${rows - bad}/${rows} rows equal (Δheight 0, Δactions 0)`); process.exit(bad ? 1 : 0);
})();
