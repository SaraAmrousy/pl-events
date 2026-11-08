#!/usr/bin/env node
/**
 * Build step after record.js (run.sh calls it). One source: tools/guide/guide.json.
 *  1. wp-content/plugins/pl-events-core/help/guide.json — what PL Help reads (text + which media exist;
 *     recording instructions and selectors stripped).
 *  2. docs/guide/ — the deck export: guide.json (+ media list), shots/*.png, pairs/*.png, video/*.mp4
 *     (+ .jpg poster, .vtt captions), gif/*.gif (short loops of the key tasks), README.md.
 *  3. Sandbox: copies the media to wp-content/uploads/pl-help/ so PL Help can be tested locally.
 * Staging gets the media with deploy/deploy-help.sh.
 */
const fs = require('fs'), path = require('path'), cp = require('child_process');
const DIR = __dirname, OUT = path.join(DIR, 'out'), REPO = path.join(DIR, '..', '..');
const DOCS = path.join(REPO, 'docs', 'guide');
const PLUGIN = path.join(REPO, 'wp-content', 'plugins', 'pl-events-core', 'help');
const SANDBOX = path.join(process.env.PL_SANDBOX || '/home/user/wp-sandbox', 'site', 'wp-content', 'uploads', 'pl-help');
const GIFS = (process.env.PL_GUIDE_GIFS || 'event-add,event-registration,office-phone,nl-remove').split(',');
const g = JSON.parse(fs.readFileSync(path.join(DIR, 'guide.json'), 'utf8'));
const m = fs.existsSync(path.join(OUT, 'manifest.json')) ? JSON.parse(fs.readFileSync(path.join(OUT, 'manifest.json'), 'utf8')) : { shots: {}, pairs: {}, videos: {} };
const exists = f => fs.existsSync(path.join(OUT, f));

const clean = {
  version: g.version, updated: g.updated, built: new Date().toISOString().slice(0, 16).replace(/\D/g, ''), title: g.title, lang: g.lang,
  chapters: g.chapters,
  tasks: g.tasks.map(t => {
    const o = { id: t.id, chapter: t.chapter, title: t.title, why: t.why, steps: t.steps, role: t.role, danger: !!t.danger, screens: t.screens || [] };
    for (const k of ['tip', 'warning', 'table', 'links', 'placeholders', 'cheat', 'cheatsheet', 'support', 'spec_table']) if (t[k] !== undefined) o[k] = t[k];
    o.screenshots = (t.screenshots || []).filter(s => exists('shots/' + s.id + '.png')).map(s => ({ id: s.id, file: 'shots/' + s.id + '.png', diagram: !!s.diagram, alt: s.alt || t.title, callouts: (s.callouts || []).map(c => ({ n: c.n, text: c.text })) }));
    if (t.pair && exists('pairs/' + t.pair.id + '.png')) o.pair = { id: t.pair.id, title: t.pair.title, text: t.pair.text, legend: t.pair.legend };
    if (t.video && exists('video/' + t.id + '.mp4')) { o.video = true; o.video_seconds = (m.videos[t.id] || {}).seconds || null; }
    return o;
  }),
};
fs.mkdirSync(PLUGIN, { recursive: true });
fs.writeFileSync(path.join(PLUGIN, 'guide.json'), JSON.stringify(clean));

// docs/guide export
// Only files of tasks that still exist (a removed task's video must not linger in the export or on the site).
const LIVE = new Set([...clean.tasks.map(t => t.id), ...clean.tasks.flatMap(t => t.screenshots.map(s => s.id)), ...g.tasks.filter(t => t.pair).flatMap(t => [t.pair.id, t.pair.id + '-admin', t.pair.id + '-site'])]);
const copyDir = (src, dst, re) => { fs.mkdirSync(dst, { recursive: true }); for (const f of fs.readdirSync(src)) if ((!re || re.test(f)) && LIVE.has(f.replace(/\.[a-z0-9]+$/, ''))) fs.copyFileSync(path.join(src, f), path.join(dst, f)); };
for (const d of ['shots', 'pairs', 'video', 'gif']) { fs.rmSync(path.join(DOCS, d), { recursive: true, force: true }); }
copyDir(path.join(OUT, 'shots'), path.join(DOCS, 'shots'), /\.png$/);
copyDir(path.join(OUT, 'pairs'), path.join(DOCS, 'pairs'), /\.png$/);
copyDir(path.join(OUT, 'video'), path.join(DOCS, 'video'), /\.(mp4|jpg|vtt)$/);
fs.mkdirSync(path.join(DOCS, 'gif'), { recursive: true });
for (const id of GIFS) {
  const mp4 = path.join(OUT, 'video', id + '.mp4');
  if (!fs.existsSync(mp4)) continue;
  const gif = path.join(DOCS, 'gif', id + '.gif');
  // From 4 s (after the title card), 16 s, 800 px, 10 fps, one palette — a light loop for slides.
  cp.execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', '4', '-t', '16', '-i', mp4, '-vf', 'fps=10,scale=800:-1:flags=lanczos,split[a][b];[a]palettegen=max_colors=96:stats_mode=diff[p];[b][p]paletteuse=dither=bayer:bayer_scale=4:diff_mode=rectangle', gif]);
}
fs.writeFileSync(path.join(DOCS, 'guide.json'), JSON.stringify({ ...clean, media: m }, null, 1));
const kb = f => Math.round(fs.statSync(f).size / 1024);
const list = d => fs.existsSync(path.join(DOCS, d)) ? fs.readdirSync(path.join(DOCS, d)).sort() : [];
const md = ['# PL Help — deck export (Step 6.2b)', '', `Built ${new Date().toISOString().slice(0, 16).replace('T', ' ')} UTC from \`tools/guide/guide.json\` by \`bash tools/guide/run.sh\`. Don't edit these files by hand: change guide.json (or the site) and run it again.`, '',
  `- **guide.json**: ${clean.chapters.length} chapters, ${clean.tasks.length} tasks (title, why, steps, tip, warning, callouts, links) + the media list.`,
  `- **shots/** (${list('shots').length} PNG): 1440×900 (mobile 390×844 @2x), numbered callouts in #2C4894; \`*-diagram.png\` = our own drawings @2x.`,
  `- **pairs/** (${list('pairs').length} PNG): wp-admin field ↔ the same thing on the site, same numbers.`,
  `- **video/** (${list('video').filter(f => f.endsWith('.mp4')).length} MP4, 1280×800 H.264, + poster .jpg + captions .vtt): served to staff only by PL Help.`,
  `- **gif/** (${list('gif').length}): short loops for slides.`, '', '| Task | Chapter | Screens | Video |', '|---|---|---|---|',
  ...clean.tasks.map(t => `| ${t.id} — ${t.title} | ${t.chapter} | ${t.screenshots.map(s => s.id).join(', ')}${t.pair ? ' + ' + t.pair.id : ''} | ${t.video ? (t.video_seconds || '?') + ' s' : '—'} |`)];
fs.writeFileSync(path.join(DOCS, 'README.md'), md.join('\n') + '\n');

// sandbox copy (local testing of PL Help)
if (fs.existsSync(path.dirname(SANDBOX))) {
  for (const d of ['shots', 'pairs', 'video']) copyDir(path.join(DOCS, d), path.join(SANDBOX, d));
}
console.log(`help/guide.json ${kb(path.join(PLUGIN, 'guide.json'))} KB · docs/guide: ${list('shots').length} shots, ${list('pairs').length} pairs, ${list('video').filter(f => f.endsWith('.mp4')).length} videos, ${list('gif').length} gifs (${list('gif').map(f => f + ' ' + kb(path.join(DOCS, 'gif', f)) + ' KB').join(', ')})`);
