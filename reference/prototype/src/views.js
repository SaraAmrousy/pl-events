/* ===== Views: each returns HTML from the computed model. Same section order and class names as the approved hi-fi. ===== */
const I = {
  cal: '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
  wa: '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1.2-1.2 2 1-.4 1.6c-3.6.4-8.1-4.1-7.7-7.7L10.2 7l1 2z"/></svg>',
  search: '<svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><circle cx="8.5" cy="8.5" r="6" stroke="#57534C" stroke-width="2" fill="none"/><path d="M13 13l5 5" stroke="#57534C" stroke-width="2" stroke-linecap="round"/></svg>',
  menu: '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  x: '<svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
};
const words = (s) => esc(s).split(' ').map((w) => '<span class="w">' + w + '</span>').join(' ');
function nextStrip(D) {
  const e = D.live[0] || D.upcoming[0]; if (!e) return '';
  const days = e.st === 'live' ? 0 : dayDiff(e.today, e.start);
  const when = e.st === 'live' ? 'Live now · ' + e.liveDay : days === 1 ? 'tomorrow' : 'in ' + days + ' days';
  return '<a class="nextc" href="#event-' + esc(e.id) + '"><span class="lab" style="color:' + (e.st === 'live' ? 'var(--liveoi)' : 'var(--boi)') + '">' + (e.st === 'live' ? '<span class="ldot" aria-hidden="true"></span> ' : '') + (e.st === 'live' ? 'Happening now' : 'Next congress') + '</span><span class="nc-n">' + esc(e.series === 'Bridge Retina' ? 'BRC' : e.series) + ' ' + e.year + ' · ' + esc(e.city) + '</span><span class="nc-d">' + esc(when) + ' →</span></a>';
}
const smp = (label) => PL.showSample ? '<span class="smp">' + esc(label || 'Sample') + '</span>' : '';

function card(e, opt) {
  opt = opt || {};
  return '<article class="card pl-card" data-ev="' + esc(e.id) + '">' +
    '<div class="' + (e.st === 'past' ? 'poster past' : 'poster') + '" aria-hidden="true"><span class="pw' + (e.posterWord.length > 9 ? ' long' : '') + '">' + esc(e.posterWord) + '</span><span class="pe">' + esc(e.posterEd) + '<br>' + esc(e.city) + '</span><span class="date"><b>' + esc(e.tileD) + '</b><span>' + esc(e.tileMY) + '</span></span></div>' +
    '<span class="badge ' + e.badge[1] + '">' + (e.st === 'live' ? '<span class="ldot" style="background:#fff" aria-hidden="true"></span>' : '<i aria-hidden="true"></i>') + esc(e.badge[0]) + '</span>' +
    '<div class="cb"><span class="lab mut">' + esc(e.specLine) + (e.sample ? ' ' + smp() : '') + '</span>' +
    '<h3 class="ct"><a href="#event-' + esc(e.id) + '" style="text-decoration:none">' + esc(e.name) + '</a></h3>' +
    '<span class="cm" style="display:flex;gap:8px;align-items:flex-start">' + flag(e.country).replace('aria-hidden', 'style="margin-top:3px" aria-hidden') + '<span>' + esc(e.range) + ' · ' + esc(e.place) + '<br>' + esc(e.venue) + '</span></span>' +
    '<div class="ca">' + (e.canReg ? '<a href="' + esc(e.registration_url) + '" class="btn bp sm" target="_blank" rel="noopener">' + esc(e.regLabel) + '</a>' : '') +
    (e.website ? '<a href="' + esc(e.website) + '" class="btn bs sm" target="_blank" rel="noopener">Website</a>' : '') +
    '<span class="push"></span>' +
    (e.st !== 'past' ? '<a class="ic-btn" href="' + esc(gcalUrl(e)) + '" target="_blank" rel="noopener" aria-label="Add ' + esc(e.name) + ' to Google Calendar">' + I.cal + '</a>' : '') +
    '<a class="ic-btn" href="' + esc(waUrl(e)) + '" target="_blank" rel="noopener" aria-label="Share ' + esc(e.name) + ' on WhatsApp">' + I.wa + '</a>' +
    '</div></div></article>';
}
function erow(e) {
  return '<div class="erow pl-card" data-ev="' + esc(e.id) + '"><span style="font-weight:800;font-size:16px;font-variant-numeric:tabular-nums">' + esc(e.range) + '</span>' +
    '<span style="display:flex;flex-direction:column;gap:2px;min-width:0"><a href="#event-' + esc(e.id) + '" style="font-weight:700;font-size:16px;line-height:1.3;text-decoration:none;overflow-wrap:anywhere">' + esc(e.name) + '</a><span class="mut" style="font-size:13px">' + esc(e.specLine) + '</span><span class="mut m-only" style="font-size:13px">' + esc(e.place) + '</span></span>' +
    '<span class="d-only" style="display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600">' + flag(e.country) + esc(e.place) + '</span>' +
    '<span class="d-only"><span class="badge ' + e.badge[1] + '"><i aria-hidden="true"></i>' + esc(e.badge[0]) + '</span></span>' +
    '<span style="display:flex;gap:8px;align-items:center;justify-content:flex-end">' + (e.canReg ? '<a href="' + esc(e.registration_url) + '" class="btn bp sm" target="_blank" rel="noopener">Register</a>' : '') + (e.website ? '<a href="' + esc(e.website) + '" class="btn bs sm d-only" target="_blank" rel="noopener">Website</a>' : '') + '</span></div>';
}

function header(route, D, over) {
  const cur = (r) => (route === r ? ' aria-current="page"' : '');
  return '<a class="skip" href="#main" data-skip>Skip to content</a><div class="topbar' + (over ? ' fx' : '') + '">' + ribbon(D) +
    '<header class="hdr' + (over ? ' over' : '') + '" id="hdr"><div class="wrap in">' +
    '<a href="#home" class="wm" aria-label="PL Events — Home"><img class="mk-i" src="assets/mark-ink.png" alt="" style="height:38px;width:auto"><img class="mk-w" src="assets/mark-white.png" alt="" style="height:38px;width:auto"><b>PL</b><span>EVENTS</span></a>' +
    '<nav aria-label="Main" class="nav"><a href="#home"' + cur('home') + '>Home</a><a href="#events"' + cur('events') + '>Events</a><a href="#services"' + cur('services') + '>Services</a><a href="#about"' + cur('about') + '>About</a><a href="#careers"' + cur('careers') + '>Careers</a><a href="#contact"' + cur('contact') + '>Contact</a></nav>' +
    '<div class="acts"><a href="#contact" data-prefill="proposal" class="btn bs sm">Request a proposal</a><a href="' + esc(PL.raw.company.portfolio.url) + '" class="btn bp sm" target="_blank" rel="noopener" aria-label="Portfolio, PDF, ' + esc(PL.raw.company.portfolio.size) + '">Portfolio</a>' +
    '<button type="button" class="btn ic menu-btn" data-act="menu" aria-label="Open menu" aria-expanded="false" style="border:1.5px solid currentColor;background:transparent;color:inherit">' + I.menu + '</button></div>' +
    '</div></header></div>' +
    '<div class="mmenu" id="mmenu" hidden role="dialog" aria-modal="true" aria-label="Menu"><div class="row" style="justify-content:space-between;margin-bottom:12px"><span class="wm"><img src="assets/mark-ink.png" alt="" style="height:32px"><b>PL</b><span>EVENTS</span></span><button type="button" class="btn ic" data-act="menu-close" aria-label="Close menu" style="border:1.5px solid var(--cb)">' + I.x + '</button></div>' +
    ['home', 'events', 'services', 'about', 'careers', 'contact'].map((r) => '<a class="l" href="#' + r + '"' + cur(r) + '>' + r[0].toUpperCase() + r.slice(1) + '</a>').join('') +
    '<a href="#contact" data-prefill="proposal" class="btn bp" style="margin-top:18px">Request a proposal</a></div>';
}
function ribbon(D) {
  const e = D.live[0]; if (!e) return '';
  return '<div class="ribbon"><div class="wrap in"><span class="lab" style="display:inline-flex;align-items:center;gap:8px;color:var(--live)"><span class="ldot" aria-hidden="true"></span>Live now</span>' +
    '<span style="font-weight:600">' + esc(e.name) + ' · ' + flag(e.country) + ' ' + esc(e.city) + ' · ' + esc(e.liveDay) + (D.live.length > 1 ? ' · +' + (D.live.length - 1) + ' more' : '') + '</span>' +
    (e.website ? '<a href="' + esc(e.website) + '" target="_blank" rel="noopener" style="margin-left:auto;font-weight:700;color:var(--blue)">Congress website →</a>' : '<a href="#event-' + esc(e.id) + '" style="margin-left:auto;font-weight:700;color:var(--blue)">Details →</a>') + '</div></div>';
}
function footer(route) {
  const R = PL.raw, offs = route === 'contact' ? [] : R.offices.filter((o) => o.show_on_site);
  return '<footer class="sand cutt foot" style="padding:112px 0 32px"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" id="ft">' +
    '<div style="grid-column:span 4;display:flex;flex-direction:column;gap:20px" class="ftc"><img src="assets/logo-ink-sm.png" alt="PL Events — Conference organization" style="width:120px;height:auto"><p style="font-size:44px;font-weight:800;line-height:.95;letter-spacing:-.035em">Science<br><span class="em">in focus.</span></p></div>' +
    '<div style="grid-column:span 6;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:28px;font-size:15px;line-height:1.5;align-content:start" class="ftc">' + (offs.length ? offs.map(officeBlock).join('') : '<p class="mut" style="grid-column:1/-1;font-size:17px;margin:0">Both offices are listed above, with phones, WhatsApp and email.</p>') + '</div>' +
    '<nav aria-label="Footer" class="nav ftc" style="grid-column:span 2;display:flex;flex-direction:column;gap:12px"><a href="#events">Events</a><a href="#services">Services</a><a href="#about">About</a><a href="#careers">Careers</a><a href="#contact">Contact</a><a href="' + esc(R.company.portfolio.url) + '" target="_blank" rel="noopener">Portfolio (PDF, ' + esc(R.company.portfolio.size) + ')</a></nav>' +
    '<div style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;border-top:2px solid var(--ink);padding-top:20px;font-size:13px"><span class="mut">© ' + nowDate().getFullYear() + ' PL Events · <a href="#privacy">Privacy</a> · ' + esc(R.geo.credits) + '</span><span style="display:flex;gap:18px;font-weight:700;flex-wrap:wrap">' + R.company.social.map((s) => '<a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + esc(s.label) + '</a>').join('') + '</span></div>' +
    '</div></footer>';
}
function officeBlock(o) {
  const c = PL.raw.geo.countries[o.country];
  return '<div style="display:flex;flex-direction:column;gap:6px"><span class="lab mut">' + esc(o.role) + '</span><strong style="font-size:20px;display:flex;align-items:center;gap:8px">' + flag(o.country) + esc(o.city) + ', ' + esc(c.name) + '</strong><span class="mut" style="overflow-wrap:anywhere">' + esc(o.address) + '</span>' +
    o.phones.map((p) => '<a href="tel:' + esc(p.number.replace(/\s/g, '')) + '" style="font-weight:600">' + esc(p.number) + '</a>' + (p.whatsapp ? '<a href="https://wa.me/' + esc(p.number.replace(/\D/g, '')) + '" target="_blank" rel="noopener" style="font-weight:600">WhatsApp ' + esc(p.number) + '</a>' : '')).join('') +
    o.emails.map((m) => '<a href="mailto:' + esc(m) + '" style="font-weight:600;overflow-wrap:anywhere">' + esc(m) + '</a>').join('') + '</div>';
}

/* --- shared map SVG (hero-free; About story/explorer and event page) --- */
/* viewBox (aspect 2.1) that frames a set of lon/lat points; min width keeps labels readable when zoomed in */
function viewBoxFor(lls, minW, aspect) {
  const all = lls.map(proj); minW = minW || 300; aspect = aspect || 2.1;
  let x0 = Math.min(...all.map((p) => p[0])) - 70, x1 = Math.max(...all.map((p) => p[0])) + 90, y0 = Math.min(...all.map((p) => p[1])) - 70, y1 = Math.max(...all.map((p) => p[1])) + 60;
  let w = Math.max(minW, x1 - x0, (y1 - y0) * aspect), h = w / aspect;
  if (h > 469) { h = 469; w = h * aspect; }
  x0 = Math.max(0, Math.min(783.5 - w, (x0 + x1) / 2 - w / 2)); y0 = Math.max(0, Math.min(469 - h, (y0 + y1) / 2 - h / 2));
  return [x0, y0, w, h].map((n) => n.toFixed(1)).join(' ');
}
function mapSvg(D, opt) {
  opt = opt || {};
  const hq = PL.raw.geo.cities.Alexandria;
  const pts = D.cities.map((c) => { const e = D.evs.find((x) => x.city === c); return { c, cc: e.country, ll: e.lonlat }; });
  const vb = viewBoxFor(pts.map((p) => p.ll).concat([hq]), 300, opt.aspect), [x0, y0, w, h] = vb.split(' ').map(Number);
  const arcs = pts.filter((p) => p.c !== 'Alexandria' && Math.hypot(...proj(p.ll).map((v, i) => v - proj(hq)[i])) > 18)
    .map((p) => '<path class="arc" data-city="' + esc(p.c) + '" data-cc="' + p.cc + '" d="' + arcPath(hq, p.ll) + '"/>').join('');
  const L = { Alexandria: [12, -10, 'start'], 'New Alamein': [-10, 16, 'end'], Tunis: [-10, -10, 'end'], Jeddah: [-10, 14, 'end'], Luxor: [10, 14, 'start'] };
  const pins = pts.map((p) => { const [x, y] = proj(p.ll); const l = L[p.c] || [10, -10, 'start']; const hqp = p.c === 'Alexandria'; const sub = hqp ? 'Head office' : p.c === 'Dubai' ? 'Regional office' : D.cn(p.cc);
    return '<g class="pin" data-city="' + esc(p.c) + '" data-cc="' + p.cc + '"><circle class="ring" cx="' + x.toFixed(1) + '" cy="' + y.toFixed(1) + '" r="4" fill="none" stroke="#FAFAF7" stroke-width="1" opacity="0"/><circle cx="' + x.toFixed(1) + '" cy="' + y.toFixed(1) + '" r="' + (hqp ? 5 : 4) + '" fill="' + (hqp ? '#FAFAF7' : '#9DB2F2') + '" stroke="#0A0D18" stroke-width="1.2"/>' +
      '<text class="ml" x="' + (x + l[0]).toFixed(1) + '" y="' + (y + l[1]).toFixed(1) + '" text-anchor="' + l[2] + '" fill="#FAFAF7" font-family="Montserrat,sans-serif" font-weight="700" font-size="10">' + esc(p.c) + '</text><text class="ms" x="' + (x + l[0]).toFixed(1) + '" y="' + (y + l[1] + 10).toFixed(1) + '" text-anchor="' + l[2] + '" fill="#B9BDC9" font-family="Montserrat,sans-serif" font-weight="600" font-size="7.5">' + esc(sub) + '</text></g>'; }).join('');
  return '<div class="map grain ' + (opt.cls || '') + '"><svg viewBox="' + vb + '" data-full="' + vb + '" data-aspect="' + (opt.aspect || 2.1) + '" role="img" aria-label="Map of PL Events congresses: ' + esc(D.cities.join(', ')) + '"><image href="assets/map-dots-ink.svg" x="0" y="0" width="783.5" height="469"/>' + arcs + pins + '</svg></div>';
}

/* ===================== PAGES ===================== */
const V = {};
V.home = (D, S) => {
  const C = PL.raw.company, ht = S.ht;
  const pools = { all: D.ordered, live: D.live, upcoming: D.upcoming, past: D.past };
  const pool = (pools[ht] && pools[ht].length) ? pools[ht] : D.ordered;
  const shown = pool.slice(0, 6), more = pool.length - shown.length;
  const tabs = [['all', 'All', D.evs.length], ['live', 'Live now', D.live.length], ['upcoming', 'Upcoming', D.upcoming.length], ['past', 'Past', D.past.length]].filter((t) => t[0] !== 'live' || D.live.length);
  const stills = C.showreel.stills.map((s, i) => '<img class="still' + (i === 0 ? ' on' : '') + '" src="' + esc(s) + '" alt=""' + (i ? ' loading="lazy"' : '') + '>').join('');
  return '<section class="hero" id="hero" aria-label="Introduction"><div class="media grain" id="heroMedia"><div class="iris" id="iris"><div class="iris-in" id="irisIn">' + stills + '</div></div><div class="shade" id="shade"></div></div>' +
    '<span id="apLogo" class="wm" aria-hidden="true" style="position:absolute;left:40px;top:24px;z-index:4;color:var(--ink);opacity:0"><img src="assets/mark-ink.png" alt="" style="height:38px"><b>PL</b><span>EVENTS</span></span><div class="wrap copy"><div id="heroFrame" style="position:relative;padding:56px 56px 60px;display:flex;flex-direction:column;gap:24px;max-width:1160px"><div class="brk vf vfw" aria-hidden="true"></div>' +
    '<span class="lab hfade" style="color:#E3E6EE">PL Events · Medical congress organiser · Alexandria · Dubai</span>' +
    '<h1 id="heroH1">' + words(heroHL().text) + '<br><span class="em">' + words(heroHL().em) + '</span></h1>' +
    '<p class="hfade hsub" style="font-size:21px;line-height:1.55;max-width:640px;color:#EEF0F5">We plan and run medical congresses, symposia, wetlabs and webinars for scientific societies — from Alexandria and Dubai to ' + D.countries.length + ' countries.</p>' +
    '<div class="row hfade"><a href="#our-congresses" data-scroll="congresses" class="btn bw">Browse events</a><a href="#contact" data-prefill="proposal" class="btn bso">Request a proposal</a><button type="button" class="btn" data-act="film" style="background:transparent;color:var(--oi);padding:0 10px"><span aria-hidden="true" style="width:34px;height:34px;border-radius:50%;background:var(--oi);color:var(--ink);display:inline-flex;align-items:center;justify-content:center;font-size:12px">▶</span>Watch the film · 0:45</button></div></div></div>' +
    '<div class="figbar hfade"><div class="wrap in"><div class="row figs-row" style="gap:36px"><span class="lab d-only" style="color:var(--oim)">Company figures</span>' + C.figures.map((f) => '<span style="display:inline-flex;align-items:baseline;gap:8px"><span class="num" style="font-size:34px">' + esc(f.value) + '</span><span style="font-size:14px;font-weight:600">' + esc(f.label) + '</span></span>').join('') + '</div>' +
    nextStrip(D) + '</div></div><div class="hero-ctrl row">' + (C.showreel.sample ? smp('Sample showreel') : '') + '<button type="button" class="btn ic bso" data-act="pause" aria-label="Pause background video" style="font-size:13px">❚❚</button></div></section>' +

    '<section class="sec" id="congresses"><div class="wrap" style="display:flex;flex-direction:column;gap:28px"><div class="sh"><div style="display:flex;flex-direction:column;gap:8px"><span class="lab mut">' + plural(D.upcoming.length + D.live.length, 'upcoming', 'upcoming') + ' · ' + D.past.length + ' held since ' + D.since + '</span><h2 class="h2 m-title">Our <span class="em">congresses.</span></h2></div><a href="#events" class="btn bs">See all events</a></div>' +
    '<div role="tablist" aria-label="Show" class="tabs" style="align-self:flex-start">' + tabs.map((t) => '<button type="button" role="tab" aria-selected="' + (ht === t[0]) + '" class="tab' + (ht === t[0] ? ' on' : '') + '" data-ht="' + t[0] + '">' + t[1] + ' <span class="n">· ' + t[2] + '</span></button>').join('') + '</div>' +
    '<div class="grid3 swap" id="homeGrid">' + (shown.length ? shown.map((e) => card(e)).join('') : '<div class="pl-empty"><strong>No events published yet.</strong></div>') + '</div>' +
    (more > 0 ? '<a href="#events" class="btn bg" style="align-self:flex-start">See ' + more + ' more in Events →</a>' : '') + '</div></section>' +

    '<section class="sec" style="border-top:1.5px solid var(--line);overflow:hidden;position:relative"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:center">' +
    '<div class="d-only" style="grid-column:span 5;margin-left:-120px" aria-hidden="true"><svg viewBox="0 0 520 330" width="520" height="330" style="overflow:visible"><text id="plOutline" x="0" y="320" fill="#FAFAF7" stroke="#0A0D18" stroke-width="4" style="font:800 420px Montserrat,sans-serif;letter-spacing:-.02em;paint-order:stroke fill">PL</text></svg></div>' +
    '<div style="grid-column:span 7;display:flex;flex-direction:column;gap:20px" class="nm"><p class="h2 nmh focus-set"><span class="hl-num">' + D.evs.length + '</span> congresses. <span class="hl-num">' + D.cities.length + '</span> cities. <span class="hl-num">' + D.countries.length + '</span> countries. One <span class="hl-city em">name.</span></p>' +
    '<p class="mut" style="font-size:17px;max-width:56ch">Counted from every event on this site since ' + D.sinceLong + '. The societies below put their name next to ours.</p>' +
    '<div class="grid5 pl-tiles" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px">' + PL.raw.societies.filter((s) => s.show_on_site).map((s) => '<div class="tile" style="font-size:13px">' + esc(s.name) + '</div>').join('') + '</div>' +
    '<a href="#about" class="btn bg" style="align-self:flex-start">Explore the record on the map →</a></div></div></section>' +

    '<section class="sand cutb" style="padding:96px 0 136px"><div class="wrap" style="display:flex;flex-direction:column;gap:32px"><div class="sh"><h2 class="h2 m-title">Behind the <span class="em">lens.</span></h2><p class="mut" style="font-size:17px;max-width:400px">One team runs the science, the sponsors, the stage and the travel.</p></div>' +
    '<div class="svc4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;align-items:start">' + PL.raw.services.map((s, i) => '<a href="#services" class="card pl-panel" style="text-decoration:none;overflow:hidden;' + (i % 2 ? 'margin-top:40px' : '') + '"><img src="' + esc(s.image) + '" alt="' + esc(s.image_alt) + '" class="photo rack" style="aspect-ratio:4/3" loading="lazy"><div class="cb" style="gap:10px"><span class="out" style="font-size:56px;-webkit-text-stroke-width:1.5px">0' + (i + 1) + '</span><strong style="font-size:19px;line-height:1.3">' + esc(s.group) + '</strong><span class="mut" style="font-size:15px;line-height:1.5">' + esc(s.line) + '</span></div></a>').join('') + '</div>' +
    '<a href="#services" class="btn bs" style="align-self:flex-start">All services</a></div></section>' +

    V.cta(D) +
    '';
};
V.cta = (D) => {
  const up = D.live.concat(D.upcoming);
  const sp = up.length ? (up.length === 1 ? '1 upcoming congress' : up.length + ' upcoming congresses') + ' in ' + uniq(up.map((e) => e.city)).join(' and ') + (up.length === 1 ? ' is' : ' are') + ' open to sponsors.' : 'Tell us the specialty you want to reach — we’ll match you to the right congress.';
  const col = (lab, h, line, btn, cls, pre, extra) => '<div class="ctacol" style="display:flex;flex-direction:column;gap:18px;padding:8px 0">' +
    '<span class="lab mut">' + lab + '</span><h2 class="h2 m-title h2l">' + h + '</h2><p style="font-size:18px;line-height:1.55;color:var(--oim);max-width:46ch">' + esc(line) + '</p>' +
    '<div class="row"><a href="#contact" data-prefill="' + pre + '" class="btn ' + cls + '">' + btn + '</a>' + (extra || '') + '</div></div>';
  return '<section class="ink" style="padding:112px 0 120px" id="cta"><div class="wrap ctag" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0">' +
    col('For scientific societies', 'Planning a <span class="em">congress?</span>', 'Our Alexandria and Dubai teams plan the science, the sponsors, the stage and the travel — one team from programme to gala dinner.', 'Request a proposal', 'bw', 'proposal') +
    '<div class="ctar" style="border-left:1.5px solid #2A3149;padding-left:56px">' + col('For sponsors & exhibitors', 'Put your brand <span class="em">in focus.</span>', sp, 'Sponsor a congress', 'bso', 'sponsor', '<a href="#services" class="btn bg">See sponsorship</a>') + '</div>' +
    '</div></section>';
};

function officeFor(countryName) {
  const offs = PL.raw.offices.filter((o) => o.show_on_site);
  const cc = Object.keys(PL.raw.geo.countries).find((k) => PL.raw.geo.countries[k].name === countryName);
  return offs.find((o) => cc && o.covers.indexOf(cc) >= 0) || offs[0];
}

V.events = (D, S) => {
  const E = S.ev, hasLive = D.live.length > 0, tab = E.tab === 'live' && !hasLive ? 'all' : E.tab;
  const tabs = [['all', 'All', D.evs.length], ['live', 'Live now', D.live.length], ['upcoming', 'Upcoming', D.upcoming.length], ['past', 'Past', D.past.length]].filter((t) => t[0] !== 'live' || hasLive);
  const F = E.f, opts = (k, all, vals) => '<option value="">' + all + '</option>' + vals.map((v) => '<option value="' + esc(v[0]) + '"' + (F[k] === v[0] ? ' selected' : '') + '>' + esc(v[1]) + '</option>').join('');
  const fdefs = [['spec', 'Specialty', 'All specialties', D.specs.map((s) => [s, s])], ['cc', 'Country', 'All countries', D.countries.map((c) => [c, D.cn(c)])], ['year', 'Year', 'All years', D.years.slice().reverse().map((y) => [String(y), String(y)])], ['type', 'Type', 'All types', D.types.map((t) => [t, t])]];
  const filtersHtml = fdefs.map((f) => '<label class="fld"><span class="fl">' + f[1] + '</span><select class="sel" data-f="' + f[0] + '">' + opts(f[0], f[2], f[3]) + '</select></label>').join('');
  const fCount = fdefs.filter((f) => F[f[0]]).length;
  return '<section style="padding:72px 0 36px" class="pg-head"><div class="wrap sh" style="gap:32px"><div style="display:flex;flex-direction:column;gap:14px"><span class="lab mut">Calendar · ' + D.evs.length + ' events since ' + D.since + '</span><h1 class="h1">Events</h1></div><p class="mut lead" style="max-width:520px;font-size:18px">Every PL Events congress, symposium, course and webinar — live, upcoming and past, with dates, cities and registration.</p></div></section>' +
    '<div class="ebar" id="ebar"><div class="wrap in">' +
    '<div class="row" style="gap:16px"><div role="tablist" aria-label="When" class="tabs">' + tabs.map((t) => '<button type="button" role="tab" aria-selected="' + (tab === t[0]) + '" class="tab' + (tab === t[0] ? ' on' : '') + '" data-tab="' + t[0] + '">' + (t[0] === 'live' ? '<span class="ldot" aria-hidden="true" style="margin-right:6px"></span>' : '') + t[1] + ' <span class="n">· ' + t[2] + '</span></button>').join('') + '</div>' +
    '<label class="sbox"><span class="sr">Search events</span>' + I.search + '<input class="inp search" id="q" type="search" placeholder="Name, city or society" value="' + esc(E.q) + '" autocomplete="off"></label>' +
    '<button type="button" class="btn bs sm m-only" data-act="sheet" style="flex-grow:1">Filters (' + fCount + ')</button>' +
    '<div role="group" aria-label="View" class="tabs">' + [['grid', '▦ Grid', 'Grid view'], ['list', '☰ List', 'List view']].map((v) => '<button type="button" aria-pressed="' + (E.view === v[0]) + '" aria-label="' + v[2] + '" class="tab' + (E.view === v[0] ? ' on' : '') + '" data-view="' + v[0] + '">' + v[1] + '</button>').join('') + '</div></div>' +
    '<div class="frow">' + filtersHtml + '<span id="count" aria-live="polite" style="font-weight:800;font-size:16px;min-height:48px;display:flex;align-items:center;white-space:nowrap"></span></div>' +
    '<div class="row" id="chips" style="gap:8px"></div></div></div>' +
    (hasLive ? '<section class="ink" id="liveBand" style="padding:28px 0"><div class="wrap" style="display:flex;flex-direction:column;gap:16px">' + D.live.map((e) => '<div class="row" style="gap:24px"><span class="lab" style="display:inline-flex;align-items:center;gap:8px;color:var(--liveoi)"><span class="ldot" aria-hidden="true"></span>Live now</span><div style="flex:1 1 400px;display:flex;flex-direction:column;gap:2px"><a href="#event-' + esc(e.id) + '" style="font-size:22px;font-weight:700;text-decoration:none">' + esc(e.name) + '</a><span class="mut" style="font-size:15px">' + esc(e.range) + ' · ' + esc(e.place) + ' · ' + esc(e.venue) + ' · ' + esc(e.liveDay) + '</span></div>' + (e.website ? '<a href="' + esc(e.website) + '" target="_blank" rel="noopener" class="btn bw">Congress website</a>' : '') + '</div>').join('') + '</div></section>' : '') +
    '<section style="padding:44px 0 96px;min-height:520px"><div class="wrap"><div class="results swap" id="results"></div></div></section>' +
    '<section class="ink cutt" style="padding:120px 0 96px"><div class="wrap sh"><h2 class="h2 m-title" style="max-width:720px">Running a congress? See how we can <span class="em">help.</span></h2><a href="#contact" data-prefill="proposal" class="btn bw">Request a proposal</a></div></section>' +
    '<div id="sheet"><div class="backdrop" data-act="sheet-close"></div><div class="sheetp" role="dialog" aria-modal="true" aria-label="Filters"><div class="row" style="justify-content:space-between"><strong style="font-size:20px">Filters</strong><button type="button" class="btn ic" style="border:1.5px solid var(--cb);background:var(--surface)" aria-label="Close filters" data-act="sheet-close">' + I.x + '</button></div>' + filtersHtml + '<div class="row" style="margin-top:6px"><button type="button" class="btn bs" data-act="clear">Clear all</button><button type="button" class="btn bp" style="flex-grow:1" data-act="sheet-close" id="sheetGo">Show events</button></div></div></div>';
};
/* the results block is re-rendered on its own so search keeps focus */
function eventsResults(D, S) {
  const E = S.ev, F = E.f, hasLive = D.live.length > 0, tab = E.tab === 'live' && !hasLive ? 'all' : E.tab;
  const base = tab === 'live' ? D.live : tab === 'upcoming' ? D.upcoming : tab === 'past' ? D.past : (hasLive ? D.upcoming.concat(D.past) : D.ordered);
  const q = E.q.trim().toLowerCase();
  const match = (e) => (!F.spec || e.specialties.indexOf(F.spec) >= 0) && (!F.cc || e.country === F.cc) && (!F.year || String(e.year) === F.year) && (!F.type || e.type === F.type) && (!q || (e.name + ' ' + e.city + ' ' + e.countryName + ' ' + e.series + ' ' + e.venue).toLowerCase().indexOf(q) >= 0);
  const list = base.filter(match);
  const chips = [];
  if (F.spec) chips.push(['spec', 'Specialty: ' + F.spec]); if (F.cc) chips.push(['cc', 'Country: ' + D.cn(F.cc)]); if (F.year) chips.push(['year', 'Year: ' + F.year]); if (F.type) chips.push(['type', 'Type: ' + F.type]); if (q) chips.push(['q', '“' + E.q.trim() + '”']);
  let html;
  if (!list.length) {
    const near = D.ordered.find((e) => !F.spec || e.specialties.indexOf(F.spec) >= 0);
    html = chips.length ? '<div class="pl-empty" style="display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"><strong style="font-size:20px">No events match these filters.</strong><span class="mut">' + (near ? 'Closest match: <a href="#event-' + esc(near.id) + '">' + esc(near.name) + '</a> · ' + esc(near.range) + ' · ' + esc(near.city) + '.' : 'Try a wider search.') + '</span><button type="button" class="btn bs" data-act="clear">Clear filters</button></div>'
      : '<div style="display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"><strong style="font-size:20px">' + (tab === 'upcoming' ? 'No upcoming congresses announced yet.' : 'Nothing here yet.') + '</strong><span class="mut">New dates appear here as soon as societies confirm them.</span><button type="button" class="btn bs" data-tab="past">See past congresses</button></div>';
  } else html = E.view === 'list' ? '<div>' + list.map(erow).join('') + '</div>' : '<div class="grid3">' + list.map((e) => card(e)).join('') + '</div><div class="vf-float" id="vfFloat" aria-hidden="true"></div>';
  return { html, count: plural(list.length, 'event'), chips: chips.map((c) => '<button type="button" class="chip" data-unchip="' + c[0] + '" aria-label="Remove filter ' + esc(c[1]) + '">' + esc(c[1]) + ' <span class="x" aria-hidden="true">×</span></button>').join('') + (chips.length ? '<button type="button" class="btn bg sm" data-act="clear">Clear all</button>' : ''), n: list.length };
}

V.about = (D, S) => {
  const R = PL.raw, wide = window.innerWidth >= 1024;
  return '<section style="padding:88px 0 72px"><div class="wrap" style="display:flex;flex-direction:column;gap:36px"><span class="lab mut">About PL Events</span><h1 class="h1 h1xl focus-set">From <span class="hl-city">Alexandria</span> to <span class="hl-num">' + D.countries.length + '</span> countries.</h1>' +
    '<div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" class="ab-int"><p class="lead" style="grid-column:span 6">PL Events plans and runs medical congresses for scientific societies, from a head office in Alexandria and a regional office in Dubai. Most of our work is ophthalmology; vascular, lymphology and nephrology are growing.</p><p class="mut" style="grid-column:8 / span 5;font-size:17px">One team handles the science, the sponsors, the stage and the travel — and every congress we run is on this site, with its date, city and registration.</p></div>' +
    '<figure style="margin:0"><img src="assets/hall-wide.jpg" alt="Delegates seated in a congress hall set up by PL Events" class="photo rack" style="height:420px;border-radius:10px" loading="lazy"><figcaption class="lab mut" style="margin-top:10px">From PL’s portfolio</figcaption></figure></div></section>' +
    '<section class="ink" id="story" aria-label="Our record on the map"><div class="wrap" style="padding-top:88px;padding-bottom:96px;display:flex;flex-direction:column;gap:28px">' +
    '<div style="display:flex;flex-direction:column;gap:10px"><span class="lab mut">' + (wide ? 'The story so far · ' : 'Our record · ') + plural(D.evs.length, 'event') + ' since ' + D.since + '</span><h2 class="h2 m-title">' + (wide ? 'One head office. A region of <span class="em">congresses.</span>' : 'Explore it <span class="em">yourself.</span>') + '</h2></div>' +
    '<div class="story2"><div class="mapcol"><div class="stick vf vfoi" id="mapWrap">' + mapSvg(D, { aspect: wide ? 1.3 : 2.1 }) + '</div></div><div class="steps">' +
    (wide ? chapters(D).map((ch, i, all) => '<article class="step" data-ch="' + i + '">' + storySide(D, ch, i, all.length) + '</article>').join('') : '') +
    '<div class="step stepx" data-explore="1">' + (wide ? '<h3 class="h3" style="font-size:32px">Now explore it <span class="em">yourself.</span></h3>' : '') + '<div class="chips" id="cchips"></div><div id="side" aria-live="polite"></div></div>' +
    '</div></div></div></section>' +
    '<section class="sec"><div class="wrap figs" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:end"><div style="grid-column:span 4;display:flex;flex-direction:column;gap:12px" class="fg-l"><span class="lab mut">Company figures</span><h2 class="h2 m-title" style="font-size:40px">The longer <span class="em">record.</span></h2><p class="mut" style="font-size:15px">As stated by PL Events, including work before this site’s records begin in ' + D.since + '.</p></div>' +
    '<div style="grid-column:span 8;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px" class="fg-r">' + R.company.figures.map((f) => '<div style="display:flex;flex-direction:column;gap:6px;border-top:2px solid var(--ink);padding-top:16px"><span class="num" style="font-size:88px">' + esc(f.value) + '</span><span style="font-size:17px;font-weight:600">' + esc(f.label) + '</span></div>').join('') + '</div></div></section>' +
    '<section class="sand sec" style="padding:88px 0"><div class="wrap" style="display:flex;flex-direction:column;gap:24px"><h2 class="h2 m-title" style="font-size:40px">Societies that trust <span class="em">us.</span></h2><div class="pl-tiles" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px">' + R.societies.filter((s) => s.show_on_site).map((s) => '<div class="tile">' + esc(s.name) + '</div>').join('') + '</div></div></section>' +
    '<section class="split" aria-label="Our offices">' + R.offices.filter((o) => o.show_on_site).map((o, i) => '<div class="' + (i ? 'sand r' : '') + '" style="' + (i ? '' : 'display:flex;justify-content:flex-end') + '"><div style="max-width:520px;display:flex;flex-direction:column;gap:12px"><span class="lab mut">' + esc(o.role) + '</span><strong class="of-c">' + flag(o.country, true) + esc(o.city) + '</strong><span class="mut" style="font-size:16px;line-height:1.5">' + esc(o.address) + '</span><div class="row" style="gap:10px;margin-top:8px">' + o.phones.map((p) => '<a href="tel:' + esc(p.number.replace(/\s/g, '')) + '" class="btn bs sm">' + esc(p.number) + '</a>').join('') + '<a href="https://wa.me/' + esc(o.phones[0].number.replace(/\D/g, '')) + '" target="_blank" rel="noopener" class="btn bs sm">WhatsApp</a>' + o.emails.map((m) => '<a href="mailto:' + esc(m) + '" class="btn bs sm">' + esc(m) + '</a>').join('') + '</div>' + (o.hours.length ? '' : '') + '</div></div>').join('') + '</section>' +
    '<section class="sec" style="padding:104px 0 112px;position:relative;overflow:hidden;border-top:1.5px solid var(--line)"><div aria-hidden="true" class="out pl-out d-only" style="position:absolute;right:40px;top:50%;transform:translateY(-50%);font-size:340px;line-height:.8;-webkit-text-stroke-color:#D9D5CC">PL</div><div class="wrap" style="position:relative;display:flex;flex-direction:column;gap:22px"><h2 class="h2 h2xl h2xxl">Two cities. <span class="em">One team.</span></h2><p class="mut" style="font-size:18px;max-width:560px">Tell us about your congress — the Alexandria or Dubai team answers.</p><div class="row"><a href="#contact" data-prefill="proposal" class="btn bp">Request a proposal</a><a href="' + esc(R.company.portfolio.url) + '" target="_blank" rel="noopener" class="btn bs">Download our portfolio (PDF, ' + esc(R.company.portfolio.size) + ')</a></div></div></section>';
};
/* About side panel: story chapter or explorer */
function chapters(D) {
  const ys = D.years; if (ys.length <= 6) return ys.map((y) => ({ label: String(y), from: y, to: y }));
  const keep = ys.slice(-5); return [{ label: ys[0] + '–' + (keep[0] - 1), from: ys[0], to: keep[0] - 1 }].concat(keep.map((y) => ({ label: String(y), from: y, to: y })));
}
function storySide(D, ch, idx, n) {
  const yr = D.evs.filter((e) => e.year >= ch.from && e.year <= ch.to), upto = D.evs.filter((e) => e.year <= ch.to);
  const before = D.evs.filter((e) => e.year < ch.from).map((e) => e.city), newC = uniq(yr.map((e) => e.city)).filter((c) => before.indexOf(c) < 0);
  return '<div class="sidein" style="display:flex;flex-direction:column;gap:16px"><span class="lab mut">Chapter ' + (idx + 1) + ' / ' + n + '</span><span class="num" style="font-size:96px">' + esc(ch.label) + '</span><p style="font-size:18px;font-weight:600;line-height:1.45">' + plural(yr.length, 'event') + ' in ' + plural(uniq(yr.map((e) => e.city)).length, 'city', 'cities') + (newC.length ? ' · new on the map: ' + esc(newC.join(', ')) : '') + (yr.some((e) => e.st !== 'past') ? ' (announced)' : '') + '.</p>' +
    '<ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:10px;font-size:15px;line-height:1.45">' + yr.map((e) => '<li style="display:flex;gap:10px;align-items:flex-start">' + flag(e.country).replace('aria-hidden', 'style="margin-top:4px" aria-hidden') + '<span><a href="#event-' + esc(e.id) + '" style="font-weight:700;text-decoration:none">' + esc(e.name) + '</a><br><span class="mut">' + esc(e.monthShort) + ' · ' + esc(e.city) + '</span></span></li>').join('') + '</ul>' +
    '<div style="border-top:1.5px solid #2A3149;padding-top:14px;display:flex;gap:22px;flex-wrap:wrap">' + [[upto.length, 'congresses'], [uniq(upto.map((e) => e.city)).length, 'cities'], [uniq(upto.map((e) => e.country)).length, 'countries']].map((t) => '<span style="display:flex;flex-direction:column;gap:2px"><span class="num" style="font-size:36px">' + t[0] + '</span><span class="mut" style="font-size:13px;font-weight:600">' + t[1] + '</span></span>').join('') + '</div><span class="lab mut">Since ' + D.since + ' · counted from the events on this site</span></div>';
}
function exploreSide(D, cc) {
  const ex = cc ? D.evs.filter((e) => e.country === cc) : D.evs, next = ex.find((e) => e.st !== 'past'), last = ex.filter((e) => e.st === 'past').slice(-1)[0];
  const name = cc ? D.cn(cc) : 'All countries';
  return '<div class="sidein" style="display:flex;flex-direction:column;gap:16px"><strong style="font-size:34px;line-height:1.1;letter-spacing:-.02em;display:flex;gap:12px;align-items:center">' + (cc ? flag(cc, true) : '') + esc(name) + '</strong><dl class="dl"><dt>Congresses</dt><dd>' + ex.length + '</dd><dt>Cities</dt><dd>' + esc(uniq(ex.map((e) => e.city)).join(', ')) + '</dd><dt>First with PL</dt><dd>' + esc(ex[0].name) + ', ' + esc(ex[0].monthShort) + '</dd><dt>Specialties</dt><dd>' + esc(uniq(ex.reduce((a, e) => a.concat(e.specialties), [])).join(', ')) + '</dd><dt>' + (next ? 'Next' : 'Latest') + '</dt><dd>' + (next ? '<a href="#event-' + esc(next.id) + '">' + esc(next.name) + '</a>, ' + esc(next.monthShort) : last ? esc(last.name) + ', ' + esc(last.monthShort) : '—') + '</dd></dl>' +
    '<div class="row" style="margin-top:6px"><a href="#events" data-preset-cc="' + esc(cc || '') + '" class="btn bw">See events in ' + esc(name) + '</a><a href="#contact" data-prefill="proposal" data-office="' + officeFor(cc ? D.cn(cc) : '').id + '" class="btn bso">Plan yours here</a></div></div>';
}

V.services = (D) => {
  const R = PL.raw;
  return '<section style="padding:72px 0 24px"><div class="wrap" style="display:flex;flex-direction:column;gap:20px"><span class="lab mut">Services</span><h1 class="h1">Behind the <span class="em">lens.</span></h1><p class="lead">One team runs the science, the sponsors, the stage and the travel — for congresses, symposia, wetlabs and webinars.</p></div></section>' +
    '<section style="padding:24px 0 72px"><div class="wrap">' + R.services.map((s, i) => '<div class="svc pl-panel' + (i % 2 ? ' svc-r' : '') + '" id="svc-' + i + '"><div class="ph grain" style="border-radius:12px;overflow:hidden"><img src="' + esc(s.image) + '" alt="' + esc(s.image_alt) + '" class="photo rack" style="aspect-ratio:4/3" loading="lazy"></div><div class="tx" style="display:flex;flex-direction:column;gap:14px"><span class="out" style="font-size:64px;-webkit-text-stroke-width:1.5px">0' + (i + 1) + '</span><h2 class="h3" style="font-size:34px">' + esc(s.group) + '</h2><p class="mut" style="font-size:18px">' + esc(s.line) + '</p><ul>' + s.items.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul></div></div>').join('') + '</div></section>' +
    '<section class="ink sec" id="sponsorship"><div class="wrap" style="display:flex;flex-direction:column;gap:24px"><span class="lab mut">Sponsorship</span><h2 class="h2 m-title">Put your brand <span class="em">in focus.</span></h2><p class="lead" style="color:var(--oim)">' + (D.upcoming.length ? plural(D.upcoming.length, 'upcoming congress', 'upcoming congresses') + ' in ' + esc(uniq(D.upcoming.map((e) => e.city)).join(' and ')) + ' are open to sponsors.' : 'Tell us the specialty you want to reach.') + ' Companies that have sponsored PL congresses:</p>' +
    '<div class="pl-tiles" style="display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:10px">' + R.sponsors.filter((s) => s.show_on_site).map((s) => '<div class="tile" style="min-height:56px;font-size:13px">' + esc(s.name) + '</div>').join('') + '</div><div class="row"><a href="#contact" data-prefill="sponsor" class="btn bw">Sponsor a congress</a></div></div></section>' +
    '<section class="sec"><div class="wrap sh"><h2 class="h2 m-title" style="max-width:720px">Planning a congress? Talk to our <span class="em">team.</span></h2><a href="#contact" data-prefill="proposal" class="btn bp">Request a proposal</a></div></section>';
};

V.careers = () => {
  const jobs = PL.raw.jobs.filter((j) => j.show_on_site !== false);
  return '<section style="padding:72px 0 40px"><div class="wrap" style="display:flex;flex-direction:column;gap:20px"><span class="lab mut">Careers</span><h1 class="h1">Work behind the <span class="em">lens.</span></h1><p class="lead">We hire for congress operations, production, registration and sponsorship in Alexandria and Dubai.</p></div></section>' +
    '<section style="padding:24px 0 56px"><div class="wrap" style="display:flex;flex-direction:column;gap:20px"><h2 class="h3">Open roles</h2>' + (jobs.length ? jobs.map((j) => '<div class="erow"><strong>' + esc(j.title) + '</strong></div>').join('') : '<div style="display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"><strong style="font-size:20px">No open roles right now.</strong><span class="mut">Send your CV below and we’ll contact you when a role fits.</span></div>') + '</div></section>' +
    '<section class="sand sec"><div class="wrap" style="max-width:860px;display:flex;flex-direction:column;gap:20px"><h2 class="h2 m-title" style="font-size:40px">Send your CV</h2>' + formHtml('careers') + '</div></section>';
};
function formHtml(kind) {
  const C = PL.state.contact, offs = PL.raw.offices.filter((o) => o.show_on_site);
  const topics = [['proposal', 'Plan a congress (proposal)'], ['sponsor', 'Sponsorship or exhibition'], ['registration', 'Help with a registration'], ['careers', 'Careers'], ['other', 'Something else']];
  const f = (id, label, input, full, help) => '<label class="fld' + (full ? ' full' : '') + '" for="' + id + '"><span class="fl">' + label + '</span>' + input + (help ? '<span class="pl-field-help mut" style="font-size:13px">' + help + '</span>' : '') + '</label>';
  return '<form class="form" id="form-' + kind + '" novalidate>' +
    f('f-name', 'Full name', '<input class="inp" id="f-name" name="name" autocomplete="name" required>') + f('f-email', 'Email', '<input class="inp" id="f-email" name="email" type="email" autocomplete="email" required>') +
    f('f-phone', 'Phone or WhatsApp (optional)', '<input class="inp" id="f-phone" name="phone" type="tel" autocomplete="tel">') +
    (kind === 'careers' ? f('f-role', 'Role you’re interested in', '<input class="inp" id="f-role" name="role">') + f('f-cv', 'CV (PDF or Word, up to 5 MB)', '<input class="inp" id="f-cv" name="cv" type="file" accept=".pdf,.doc,.docx" style="padding-top:10px">', true)
      : f('f-topic', 'Topic', '<select class="sel" id="f-topic" name="topic">' + topics.map((t) => '<option value="' + t[0] + '"' + (C.topic === t[0] ? ' selected' : '') + '>' + t[1] + '</option>').join('') + '</select>') +
        f('f-office', 'Office', '<select class="sel" id="f-office" name="office">' + offs.map((o) => '<option value="' + o.id + '"' + (C.office === o.id ? ' selected' : '') + '>' + esc(o.city) + ' — ' + esc(o.role.toLowerCase()) + '</option>').join('') + '</select>', false, 'Pre-set from the page you came from; change it if you like.')) +
    f('f-msg', kind === 'careers' ? 'A few lines about you' : 'Message', '<textarea class="inp" id="f-msg" name="message" rows="5"></textarea>', true) +
    '<div class="full row"><button type="submit" class="btn bp">' + (kind === 'careers' ? 'Send my CV' : 'Send message') + '</button></div><div class="full" id="formNote" aria-live="polite"></div></form>';
}

V.contact = () => {
  const R = PL.raw;
  return '<section style="padding:72px 0 40px"><div class="wrap" style="display:flex;flex-direction:column;gap:20px"><span class="lab mut">Contact</span><h1 class="h1">Talk to our <span class="em">team.</span></h1><p class="lead">Alexandria and Dubai. Tell us about your congress, your sponsorship or your registration — the right office answers.</p></div></section>' +
    '<section style="padding:0 0 72px"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:40px;align-items:start" class="ct-grid"><div style="grid-column:span 7" class="ct-f">' + formHtml('contact') + '</div><div style="grid-column:span 5;display:flex;flex-direction:column;gap:28px" class="ct-o">' + R.offices.filter((o) => o.show_on_site).map((o) => '<div class="card" style="padding:24px">' + officeBlock(o) + '</div>').join('') + '<span class="lab mut">Opening hours: TBC — added by PL</span></div></div></section>';
};
V.privacy = () => '<section style="padding:72px 0 96px"><div class="wrap" style="max-width:860px;display:flex;flex-direction:column;gap:18px"><span class="lab mut">Privacy</span><h1 class="h1" style="font-size:64px">Privacy policy</h1><p class="lead">The full text comes from PL Events (reveal-day request). It will cover the contact and careers forms, CV retention and cookies.</p>' + smp('Text TBC') + '</div></section>';

function evMap(e) {
  const hq = PL.raw.geo.cities.Alexandria, near = Math.hypot(...proj(e.lonlat).map((x, i) => x - proj(hq)[i])) < 18;
  const vb = viewBoxFor([hq, e.lonlat], 260), [x, y] = proj(e.lonlat), [hx, hy] = proj(hq);
  return '<div class="map grain evmap"><svg viewBox="' + vb + '" role="img" aria-label="Map: ' + esc(e.city) + ', ' + esc(e.countryName) + (near ? '' : ', linked to PL’s head office in Alexandria') + '"><image href="assets/map-dots-ink.svg" x="0" y="0" width="783.5" height="469"/>' +
    (near ? '' : '<path class="arc evarc" d="' + arcPath(hq, e.lonlat) + '"/><circle cx="' + hx.toFixed(1) + '" cy="' + hy.toFixed(1) + '" r="4" fill="#FAFAF7" stroke="#0A0D18" stroke-width="1.2"/><text x="' + (hx + 8).toFixed(1) + '" y="' + (hy - 8).toFixed(1) + '" fill="#B9BDC9" style="font:600 8px Montserrat,sans-serif">Alexandria</text>') +
    '<circle class="evring" cx="' + x.toFixed(1) + '" cy="' + y.toFixed(1) + '" r="10" fill="none" stroke="#9DB2F2" stroke-width="1.5" opacity=".7"/><circle cx="' + x.toFixed(1) + '" cy="' + y.toFixed(1) + '" r="5.5" fill="#9DB2F2" stroke="#0A0D18" stroke-width="1.2"/><text x="' + (x + 12).toFixed(1) + '" y="' + (y + 4).toFixed(1) + '" fill="#FAFAF7" style="font:700 11px Montserrat,sans-serif">' + esc(e.city) + '</text></svg></div>';
}
V.event = (D, id) => {
  const e = D.evs.find((x) => x.id === id);
  if (!e) return '<section style="padding:96px 0"><div class="wrap" style="display:flex;flex-direction:column;gap:16px"><h1 class="h1" style="font-size:64px">Event not found</h1><p class="lead">It may have been removed or renamed.</p><a href="#events" class="btn bp" style="align-self:flex-start">Back to all events</a></div></section>';
  const eds = D.evs.filter((x) => x.series === e.series).sort((a, b) => (a.start < b.start ? 1 : -1));
  const more = D.ordered.filter((x) => x.id !== e.id && x.st !== 'past' && x.specialties.some((s) => e.specialties.indexOf(s) >= 0)).slice(0, 3);
  const ho = PL.raw.offices[0];
  return '<section class="ev-hero" style="padding:64px 0 72px"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:40px;align-items:start" class="evh">' +
    '<div style="grid-column:span 7;display:flex;flex-direction:column;gap:18px" class="evh-l"><a href="#events" style="font-weight:700;color:var(--boi);text-decoration:none">← All events</a><div class="row"><span class="badge ' + e.badge[1] + '">' + (e.st === 'live' ? '<span class="ldot" style="background:#fff" aria-hidden="true"></span>' : '<i aria-hidden="true"></i>') + esc(e.badge[0]) + '</span><span class="lab" style="color:var(--oim)">' + esc(e.specLine) + ' · ' + esc(e.series) + ' series</span>' + (e.sample ? smp() : '') + '</div>' +
    '<h1 class="h1 h1ev">' + esc(e.name) + '</h1>' +
    '<dl class="dl" style="font-size:17px"><dt>When</dt><dd>' + esc(e.range) + (e.st === 'live' ? ' · ' + esc(e.liveDay) : '') + '</dd><dt>Where</dt><dd style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">' + flag(e.country) + esc(e.venue) + ', ' + esc(e.place) + '</dd><dt>Edition</dt><dd>' + esc(e.edition) + '</dd></dl>' +
    '<div class="row">' + (e.canReg ? '<a href="' + esc(e.registration_url) + '" target="_blank" rel="noopener" class="btn bw">' + esc(e.regLabel) + '</a>' : '') + (e.website ? '<a href="' + esc(e.website) + '" target="_blank" rel="noopener" class="btn bso">Congress website</a>' : '') + (e.st !== 'past' ? '<a href="' + esc(gcalUrl(e)) + '" target="_blank" rel="noopener" class="btn bso">Add to Google Calendar</a>' : '') + '<a href="' + esc(waUrl(e)) + '" target="_blank" rel="noopener" class="btn bso">Share on WhatsApp</a></div>' +
    (!e.canReg && e.st !== 'past' ? '<p class="mut" style="font-size:15px">Registration opens soon — the button appears here when it does.</p>' : '') + '</div>' +
    '<div style="grid-column:span 5;display:flex;flex-direction:column;gap:16px" class="evh-r"><div class="' + (e.st === 'past' ? 'poster past' : 'poster') + ' grain" style="border-radius:10px;aspect-ratio:16/10" aria-hidden="true"><span class="pw" style="font-size:52px;top:28px">' + esc(e.posterWord) + '</span><span class="pe">' + esc(e.posterEd) + '<br>' + esc(e.city) + '</span><span class="date"><b>' + esc(e.tileD) + '</b><span>' + esc(e.tileMY) + '</span></span></div>' + evMap(e) + '<span class="lab" style="color:var(--oim)">Poster or logo at build · map: ' + esc(e.city) + (e.onCentre ? ' (country centre)' : '') + '</span></div></div></section>' +
    (eds.length > 1 ? '<section class="sec"><div class="wrap" style="display:flex;flex-direction:column;gap:18px"><h2 class="h3" style="font-size:32px">' + esc(e.series) + ' editions with PL</h2><div>' + eds.map((x) => '<div class="erow" style="grid-template-columns:200px minmax(0,1fr) auto">' + '<span style="font-weight:800">' + esc(x.range) + '</span><a href="#event-' + esc(x.id) + '" style="font-weight:700;text-decoration:none"' + (x.id === e.id ? ' aria-current="page"' : '') + '>' + esc(x.name) + (x.id === e.id ? ' (this page)' : '') + '</a><span class="badge ' + x.badge[1] + '"><i aria-hidden="true"></i>' + esc(x.badge[0]) + '</span></div>').join('') + '</div></div></section>' : '') +
    '<section class="sand sec" style="padding:64px 0"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" class="evc"><div style="grid-column:span 5;display:flex;flex-direction:column;gap:10px" class="evc-l"><h2 class="h3">Contact for this event</h2><p class="mut">' + (e.phones.length ? '' : 'PL’s head office handles this event’s questions.') + '</p></div><div style="grid-column:span 7" class="evc-r">' + officeBlock(ho) + '</div></div></section>' +
    (more.length ? '<section class="sec"><div class="wrap" style="display:flex;flex-direction:column;gap:24px"><div class="sh"><h2 class="h3" style="font-size:32px">More upcoming ' + esc(e.specialties[0].toLowerCase()) + ' congresses</h2><a href="#events" class="btn bs">Back to all events</a></div><div class="grid3">' + more.map((x) => card(x)).join('') + '</div></div></section>' : '');
};
