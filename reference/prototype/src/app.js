/* ===== App: router (#name only, per artifact rules), state, handlers, prototype controls ===== */
PL.state = { ht: 'all', ev: { tab: 'all', view: 'grid', q: '', f: { spec: '', cc: '', year: '', type: '' } }, help: { role: 'doctor', spec: '', where: 'Anywhere' }, about: { mode: 'story', ch: 0, cc: '' }, contact: { topic: 'proposal', office: 'alexandria' } };
const ROUTES = ['home', 'events', 'about', 'services', 'careers', 'contact', 'privacy'];
const TITLES = { home: 'PL Events — Medical congress organiser', events: 'Events — PL Events', about: 'About — PL Events', services: 'Services — PL Events', careers: 'Careers — PL Events', contact: 'Contact — PL Events', privacy: 'Privacy — PL Events' };
let D = null, route = 'home', stillTimer = null;
const $ = (s, r) => (r || document).querySelector(s), $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

function parseRoute() {
  const h = (location.hash || '').slice(1);
  if (h.indexOf('event-') === 0) return { r: 'event', id: h.slice(6) };
  if (h === 'our-congresses') return { r: 'home', anchor: 'congresses' };
  return { r: ROUTES.indexOf(h) >= 0 ? h : 'home' };
}
function render(keepScroll) {
  Motion.kill();
  D = model();
  const p = parseRoute(); route = p.r;
  const over = route === 'home';
  $('#shell').innerHTML = header(route, D, over);
  const main = $('#main');
  main.innerHTML = route === 'event' ? V.event(D, p.id) : V[route](D, PL.state);
  $('#foot').innerHTML = footer(route);
  document.title = route === 'event' ? ((D.evs.find((e) => e.id === p.id) || { name: 'Event' }).name + ' — PL Events') : TITLES[route];
  if (route === 'events') renderResults(true);
  if (route === 'about') aboutPanel();
  if (route === 'home') startStills();
  else stopStills();
  renderControls();
  if (!keepScroll) window.scrollTo(0, 0);
  Motion.init(route);
  if (p.anchor) setTimeout(() => { const t = document.getElementById(p.anchor); t && t.scrollIntoView(); }, 50);
}
function renderResults(first) {
  const R = eventsResults(D, PL.state);
  const box = $('#results'); if (!box) return;
  box.innerHTML = R.html; $('#count').textContent = R.count; $('#chips').innerHTML = R.chips;
  const go = $('#sheetGo'); if (go) go.textContent = 'Show ' + R.count;
  const fb = $('[data-act="sheet"]'); if (fb) fb.textContent = 'Filters (' + Object.values(PL.state.ev.f).filter(Boolean).length + ')';
  if (!first) Motion.swap(box);
  Motion.viewfinder();
}
function aboutPanel() {
  const A = PL.state.about, side = $('#side'); if (!side) return;
  $('#cchips').innerHTML = [['', 'All countries', D.evs.length]].concat(D.countries.map((c) => [c, D.cn(c), D.evs.filter((e) => e.country === c).length])).map((c) => '<button type="button" class="choice' + (A.cc === c[0] ? ' on' : '') + '" aria-pressed="' + (A.cc === c[0]) + '" data-cc="' + c[0] + '">' + (c[0] ? flag(c[0]) : '') + esc(c[1]) + ' <span style="opacity:.65">' + c[2] + '</span></button>').join('');
  side.innerHTML = exploreSide(D, A.cc);
  if (A.mode === 'explore') Motion.mapState({ mode: 'explore', cc: A.cc });
}

/* hero stand-in showreel: crossfade between real portfolio stills (Sample) */
function startStills() {
  stopStills(); const ctx = Motion.ctxName(); if (ctx !== 'full' || PL.paused) return;
  let i = 0; stillTimer = setInterval(() => { const s = $$('.still'); if (!s.length) return; s[i].classList.remove('on'); i = (i + 1) % s.length; s[i].classList.add('on'); }, 5000);
}
function stopStills() { if (stillTimer) clearInterval(stillTimer); stillTimer = null; }

/* ---- events (delegation) ---- */
document.addEventListener('click', (ev) => {
  const t = ev.target.closest('[data-act],[data-ht],[data-tab],[data-view],[data-role],[data-unchip],[data-cc],[data-prefill],[data-preset-spec],[data-preset-cc],[data-pc]');
  if (!t) { const sc = ev.target.closest('[data-scroll]'); if (sc) { ev.preventDefault(); const el = document.getElementById(sc.dataset.scroll); if (el) { if (Motion.lenis) Motion.lenis.scrollTo(el, { offset: -76 }); else el.scrollIntoView({ behavior: Motion.ctxName() === 'reduced' ? 'auto' : 'smooth' }); } } return; }
  const S = PL.state;
  if (t.dataset.prefill) { S.contact.topic = t.dataset.prefill; if (t.dataset.office) S.contact.office = t.dataset.office; }
  if (t.dataset.presetSpec !== undefined) { S.ev = { tab: 'all', view: S.ev.view, q: '', f: { spec: t.dataset.presetSpec, cc: '', year: '', type: '' } }; }
  if (t.dataset.presetCc !== undefined) { S.ev = { tab: 'all', view: S.ev.view, q: '', f: { spec: '', cc: t.dataset.presetCc, year: '', type: '' } }; }
  if (t.dataset.ht) { S.ht = t.dataset.ht; const m = $('#main'); const scroll = window.scrollY; m.innerHTML = V.home(D, S); window.scrollTo(0, scroll); Motion.init('home', true); startStills(); return; }
  if (t.dataset.tab) { S.ev.tab = t.dataset.tab; $$('[data-tab]').forEach((b) => { const on = b.dataset.tab === S.ev.tab; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); }); renderResults(); return; }
  if (t.dataset.view) { S.ev.view = t.dataset.view; $$('[data-view]').forEach((b) => { const on = b.dataset.view === S.ev.view; b.classList.toggle('on', on); b.setAttribute('aria-pressed', on); }); renderResults(); return; }
  if (t.dataset.unchip) { if (t.dataset.unchip === 'q') { S.ev.q = ''; $('#q').value = ''; } else { S.ev.f[t.dataset.unchip] = ''; $$('[data-f="' + t.dataset.unchip + '"]').forEach((s) => (s.value = '')); } renderResults(); return; }
  if (t.dataset.cc !== undefined && t.closest('#cchips')) { S.about.cc = t.dataset.cc; aboutPanel(); Motion.swap($('#side')); return; }
  const a = t.dataset.act;
  if (a === 'clear') { S.ev.f = { spec: '', cc: '', year: '', type: '' }; S.ev.q = ''; $$('[data-f]').forEach((s) => (s.value = '')); const q = $('#q'); if (q) q.value = ''; renderResults(); }
  if (a === 'sheet') Motion.sheet(true);
  if (a === 'sheet-close') Motion.sheet(false);
  if (a === 'menu') { $('#mmenu').hidden = false; t.setAttribute('aria-expanded', 'true'); $('#mmenu a').focus(); }
  if (a === 'menu-close') { $('#mmenu').hidden = true; $('[data-act="menu"]').focus(); }
  if (a === 'film') Motion.film(true, t);
  if (a === 'film-close') Motion.film(false);
  if (a === 'pause') { PL.paused = !PL.paused; const hr = $('#hero'); hr && hr.classList.toggle('paused', PL.paused); t.textContent = PL.paused ? '▶' : '❚❚'; t.setAttribute('aria-label', PL.paused ? 'Play background video' : 'Pause background video'); PL.paused ? stopStills() : startStills(); }
  if (t.dataset.pc) pcAction(t.dataset.pc, t);
});
document.addEventListener('change', (ev) => {
  const t = ev.target, S = PL.state;
  if (t.dataset.f) { S.ev.f[t.dataset.f] = t.value; $$('[data-f="' + t.dataset.f + '"]').forEach((s) => (s.value = t.value)); renderResults(); }
  if (t.dataset.pcs) pcAction(t.dataset.pcs, t);
});
document.addEventListener('input', (ev) => { if (ev.target.id === 'q') { PL.state.ev.q = ev.target.value; renderResults(); } });
document.addEventListener('submit', (ev) => {
  ev.preventDefault(); const f = ev.target, note = $('#formNote', f), bad = $$('[required]', f).filter((i) => !i.value.trim() || (i.type === 'email' && !/^\S+@\S+\.\S+$/.test(i.value)));
  $$('.fld', f).forEach((l) => l.classList.remove('pl-field-error'));
  if (bad.length) { bad.forEach((i) => i.closest('.fld').classList.add('pl-field-error')); note.innerHTML = '<div class="note" style="background:#FDECEC">Please add ' + bad.map((i) => i.closest('.fld').querySelector('.fl').textContent.toLowerCase()).join(' and ') + '.</div>'; bad[0].focus(); return; }
  const off = PL.raw.offices.find((o) => o.id === (f.office ? f.office.value : 'alexandria')) || PL.raw.offices[0];
  note.innerHTML = '<div class="note">Prototype: nothing was sent. On the live site, Forminator emails the ' + esc(off.city) + ' team (' + esc(off.emails[0]) + ') and shows a confirmation here.</div>';
});
document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') { Motion.sheet(false); Motion.film(false); const m = $('#mmenu'); if (m && !m.hidden) { m.hidden = true; } } });
window.addEventListener('hashchange', () => { $('#mmenu') && ($('#mmenu').hidden = true); render(); const m = $('#main'); m && m.focus({ preventScroll: true }); });

/* ---- prototype controls (not part of the site) ---- */
function renderControls() {
  const box = $('#pc'); if (!box) return;
  const R = PL.raw, det = Motion.detected();
  box.innerHTML = '<h3>Prototype controls</h3><p class="mut" style="margin:0;font-size:13px">Not part of the site. Everything below changes only the data or the motion context — the pages re-render from the JSON.</p>' +
    '<label class="fld"><span class="fl">Date</span><select class="sel" data-pcs="sim"><option value=""' + (!PL.sim ? ' selected' : '') + '>Today</option><option value="2026-10-31"' + (PL.sim === '2026-10-31' ? ' selected' : '') + '>31 Oct 2026 — during 8th MEOM (Live)</option><option value="2027-01-29"' + (PL.sim === '2027-01-29' ? ' selected' : '') + '>29 Jan 2027 — DEOSS day 1 (Live)</option><option value="2027-06-01"' + (PL.sim === '2027-06-01' ? ' selected' : '') + '>1 Jun 2027 — nothing upcoming</option></select></label>' +
    '<label class="fld"><span class="fl">Motion context (detected: ' + det + ')</span><select class="sel" data-pcs="motion">' + ['auto', 'full', 'light', 'reduced'].map((m) => '<option value="' + m + '"' + (PL.motionOverride === m ? ' selected' : '') + '>' + m[0].toUpperCase() + m.slice(1) + '</option>').join('') + '</select></label>' +
    '<label class="fld"><span class="fl">Home headline (from Company settings)</span><select class="sel" data-pcs="headline">' + HEADLINES.map((h) => '<option value="' + h.id + '"' + (heroHL().id === h.id ? ' selected' : '') + '>' + esc(h.text + ' ' + h.em) + '</option>').join('') + '</select></label>' +
    '<label class="ck"><input type="checkbox" data-pcs="sample"' + (PL.showSample ? ' checked' : '') + '> Show “Sample” tags</label>' +
    '<button type="button" class="btn bs sm" data-pc="iris">Replay the iris (Home)</button>' +
    '<h3 style="margin-top:6px">Data test (DoD)</h3><p style="margin:0;font-size:13px"><strong>' + plural(D.evs.length, 'event') + ' · ' + plural(D.countries.length, 'country', 'countries') + ' · ' + plural(D.cities.length, 'city', 'cities') + '</strong> on the site now. Untick “Show on site” or add a row — map, calendar, grid, tabs and counts all follow.</p>' +
    '<button type="button" class="btn ' + (PL.sampleOn ? 'bs' : 'bp') + ' sm" data-pc="sampleEv">' + (PL.sampleOn ? 'Remove the sample event (Amman, Jordan)' : 'Add a sample event in a new country (Amman, Jordan)') + '</button>' +
    '<div class="evlist">' + R.events.map((e) => '<label class="ck"><input type="checkbox" data-pcs="ev" value="' + esc(e.id) + '"' + (PL.removed.has(e.id) ? '' : ' checked') + '> ' + esc(e.name) + ' <span class="mut">(' + esc(e.city) + ')</span></label>').join('') + '</div>';
}
function pcAction(k, t) {
  if (k === 'toggle') { const p = $('#pc'); p.hidden = !p.hidden; t.setAttribute('aria-expanded', String(!p.hidden)); return; }
  if (k === 'sim') { PL.sim = t.value; render(true); }
  if (k === 'motion') { PL.motionOverride = t.value; render(true); }
  if (k === 'headline') { PL.headline = t.value; render(true); }
  if (k === 'sample') { PL.showSample = t.checked; render(true); }
  if (k === 'ev') { t.checked ? PL.removed.delete(t.value) : PL.removed.add(t.value); render(true); }
  if (k === 'sampleEv') { PL.sampleOn = !PL.sampleOn; render(true); }
  if (k === 'iris') { try { sessionStorage.removeItem('plIris'); } catch (e) {} if (location.hash && location.hash !== '#home') location.hash = '#home'; else render(); }
  const b = $('.pc-btn'); $('#pc').hidden = false; b && b.setAttribute('aria-expanded', 'true');
}

/* ---- boot ---- */
fetch('data/pl-data.json').then((r) => r.json()).then((j) => { PL.raw = j; render(); })
  .catch(() => { $('#main').innerHTML = '<div class="wrap" style="padding:80px 20px"><h1 class="h2">The data file could not load.</h1><p class="lead">Open the prototype from its published link so data/pl-data.json is served next to it.</p></div>'; });
