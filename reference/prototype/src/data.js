/* ===== PL data layer (prototype) — mirrors pl-events-core: one JSON in, everything computed ===== */
const PL = { raw: null, removed: new Set(), sampleOn: false, sim: '', showSample: true, motionOverride: 'auto' };
const M3 = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const MF = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const WORDS = ['zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'];
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uniq = (a) => a.filter((x, i) => a.indexOf(x) === i);
const plural = (n, one, many) => n + ' ' + (n === 1 ? one : (many || one + 's'));

function nowDate() { return PL.sim ? new Date(PL.sim + 'T12:00:00Z') : new Date(); }
function localDay(tz, d) {
  try { return new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).format(d); }
  catch (e) { return d.toISOString().slice(0, 10); }
}
function parts(iso) { const [y, m, d] = iso.split('-').map(Number); return { y, m: m - 1, d }; }
function dayDiff(a, b) { return Math.round((Date.UTC(...ymd(b)) - Date.UTC(...ymd(a))) / 864e5); }
function ymd(iso) { const p = parts(iso); return [p.y, p.m, p.d]; }

function dateText(s, e) {
  const a = parts(s), b = parts(e);
  if (s === e) return a.d + ' ' + M3[a.m] + ' ' + a.y;
  if (a.y === b.y && a.m === b.m) return a.d + '–' + b.d + ' ' + M3[a.m] + ' ' + a.y;
  if (a.y === b.y) return a.d + ' ' + M3[a.m] + ' – ' + b.d + ' ' + M3[b.m] + ' ' + b.y;
  return a.d + ' ' + M3[a.m] + ' ' + a.y + ' – ' + b.d + ' ' + M3[b.m] + ' ' + b.y;
}

/* Build the computed model from the raw JSON. Called after every data change. */
function model() {
  const R = PL.raw, G = R.geo, NOW = nowDate();
  let src = R.events.slice();
  if (PL.sampleOn && R._sample_event) src.push(R._sample_event);
  src = src.filter((e) => e.show_on_site && !PL.removed.has(e.id));
  const evs = src.map((e) => {
    const c = G.countries[e.country] || { name: e.country, tz: 'UTC', centre: [30, 30] };
    const today = localDay(c.tz, NOW);
    const st = today > e.end ? 'past' : today >= e.start ? 'live' : 'upcoming';
    const a = parts(e.start), b = parts(e.end);
    const canReg = st !== 'past' && e.registration === 'open' && /^https?:\/\//.test(e.registration_url || '');
    const badge = st === 'live' ? ['Live now', 'b-live'] : st === 'past' ? ['Held ' + M3[a.m] + ' ' + a.y, 'b-past'] : canReg ? ['Registration open', 'b-open'] : ['Registration opens soon', 'b-soon'];
    const pt = G.cities[e.city] || c.centre;
    const dayN = st === 'live' ? dayDiff(e.start, today) + 1 : 0, dayT = dayDiff(e.start, e.end) + 1;
    return Object.assign({}, e, {
      st, today, year: a.y, monthShort: M3[a.m] + ' ' + a.y, range: dateText(e.start, e.end),
      tileD: e.start === e.end ? String(a.d) : (a.m === b.m ? a.d + '–' + b.d : a.d + ' ' + M3[a.m].slice(0, 3)), tileMY: a.m === b.m || e.start === e.end ? M3[a.m] + ' ' + a.y : '– ' + b.d + ' ' + M3[b.m] + ' ' + b.y,
      countryName: c.name, place: e.city + ', ' + c.name, canReg, badge, lonlat: pt, onCentre: !G.cities[e.city],
      regLabel: 'Register for ' + (e.series === 'Bridge Retina' ? 'BRC' : e.series) + ' ' + a.y,
      specLine: e.type + ' · ' + e.specialties.join(', '), liveDay: st === 'live' ? 'day ' + dayN + ' of ' + dayT : '',
      posterWord: e.series.length > 12 ? e.series.replace(/([a-z])([A-Z])/g, '$1 $2') : e.series, posterEd: e.edition
    });
  }).sort((x, y) => (x.start < y.start ? -1 : 1));
  const live = evs.filter((e) => e.st === 'live'), upcoming = evs.filter((e) => e.st === 'upcoming'), past = evs.filter((e) => e.st === 'past').reverse();
  const countries = uniq(evs.map((e) => e.country)), cities = uniq(evs.map((e) => e.city));
  const first = evs[0];
  return {
    NOW, evs, live, upcoming, past, ordered: live.concat(upcoming, past), countries, cities,
    since: first ? M3[parts(first.start).m] + ' ' + first.year : '—', sinceLong: first ? MF[parts(first.start).m] + ' ' + first.year : '—',
    specs: uniq(evs.reduce((a, e) => a.concat(e.specialties), [])), types: uniq(evs.map((e) => e.type)), years: uniq(evs.map((e) => e.year)),
    cn: (cc) => (G.countries[cc] || { name: cc }).name, word: (n) => WORDS[n] || String(n)
  };
}

/* Map projection shared by hero/story/explorer (equirectangular, same as v0.1 dots). */
const proj = (ll) => [(ll[0] + 14) * 10, (52 - ll[1]) * 10];
function arcPath(a, b) {
  const [x1, y1] = proj(a), [x2, y2] = proj(b); const mx = (x1 + x2) / 2, my = (y1 + y2) / 2, len = Math.hypot(x2 - x1, y2 - y1);
  const lift = Math.max(20, len * 0.3); return 'M' + x1.toFixed(1) + ',' + y1.toFixed(1) + ' Q' + mx.toFixed(1) + ',' + (my - lift).toFixed(1) + ' ' + x2.toFixed(1) + ',' + y2.toFixed(1);
}
/* Google Calendar link (all-day). Downloads (.ics) are blocked inside the artifact frame; the build serves .ics from the plugin. */
function gcalUrl(e) {
  const end = new Date(Date.UTC(...ymd(e.end)) + 864e5).toISOString().slice(0, 10).replace(/-/g, '');
  return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' + encodeURIComponent(e.name) + '&dates=' + e.start.replace(/-/g, '') + '/' + end + '&location=' + encodeURIComponent(e.venue + ', ' + e.place) + '&details=' + encodeURIComponent((e.website || '') + ' — organised by PL Events');
}
function waUrl(e) { return 'https://wa.me/?text=' + encodeURIComponent(e.name + ' · ' + e.range + ' · ' + e.place + ' — https://plevents.org/events/' + e.id + '/'); }
function flag(cc, big) { return '<span class="flag f-' + esc(cc) + '" aria-hidden="true"' + (big ? ' style="width:30px;height:21px"' : '') + '></span>'; }

const HEADLINES = [
  { id: 'stage', text: 'Your science.', em: 'Our stage.' },
  { id: 'remember', text: 'Congresses doctors', em: 'remember.' },
  { id: 'meets', text: 'Where medicine', em: 'meets.' },
  { id: 'focus', text: 'Science', em: 'in focus.' }
];
function heroHL() { const o = PL.headline && HEADLINES.find((h) => h.id === PL.headline); return o || PL.raw.company.hero_headline || HEADLINES[0]; }
