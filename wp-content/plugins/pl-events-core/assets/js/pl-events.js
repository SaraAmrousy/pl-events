/*!
 * pl-events-core front-end — progressive enhancement only. Every shortcode is a finished,
 * server-rendered section without this file. This adds:
 *  1. status re-check in each event country's local time (cached pages stay honest, D9)
 *  2. Home tabs without reload ([pl_upcoming])
 *  3. Events calendar: live filtering, chips, counts, Grid/List, URL state, mobile bottom sheet
 *  4. About explorer: country chips swap the side panel and frame the map
 * Motion lives in the child theme (Step 4.2); this file never animates.
 */
(function () {
  'use strict';
  var M3 = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  function localDay(tz) {
    try { return new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date()); }
    catch (e) { return new Date().toISOString().slice(0, 10); }
  }
  function statusOf(el) {
    var t = localDay(el.getAttribute('data-tz') || 'Africa/Cairo');
    var s = el.getAttribute('data-start'), e = el.getAttribute('data-end');
    return t > e ? 'past' : (t >= s ? 'live' : 'upcoming');
  }

  /* 1 — status re-check ------------------------------------------------------- */
  function recheck() {
    $$('[data-st][data-start]').forEach(function (el) {
      var now = statusOf(el), was = el.getAttribute('data-st');
      if (now === was) return;
      el.setAttribute('data-st', now);
      var b = el.querySelector('.badge'), bt = b && b.querySelector('.bt');
      if (b && bt) {
        var p = el.getAttribute('data-start').split('-');
        b.className = 'badge ' + (now === 'past' ? 'b-past' : now === 'live' ? 'b-live' : b.className.replace('badge', '').trim());
        if (now === 'past') bt.textContent = 'Held ' + M3[+p[1] - 1] + ' ' + p[0];
        if (now === 'live') bt.textContent = 'Live now';
      }
      if (now === 'past') $$('.pl-reg, .pl-cal', el).forEach(function (a) { a.remove(); });
      if (now === 'past' && (el.classList.contains('ribbon') || el.classList.contains('nextc'))) el.hidden = true;
    });
  }

  function setParam(params) {
    if (!window.history || !history.replaceState) return;
    var u = new URL(location.href);
    Object.keys(params).forEach(function (k) { if (params[k]) u.searchParams.set(k, params[k]); else u.searchParams.delete(k); });
    history.replaceState(null, '', u.toString());
  }

  /* 2 — Home tabs --------------------------------------------------------------- */
  function upcoming(root) {
    var limit = +root.getAttribute('data-limit') || 6, cards = $$('.pl-card', root), more = root.querySelector('.pl-more');
    function show(tab) {
      var pool = cards.filter(function (c) { return tab === 'all' || c.getAttribute('data-st') === tab; });
      cards.forEach(function (c) { c.hidden = pool.indexOf(c) < 0 || pool.indexOf(c) >= limit; });
      $$('[data-ht]', root).forEach(function (t) { var on = t.getAttribute('data-ht') === tab; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
      if (more) {
        var n = Math.max(0, pool.length - limit);
        more.hidden = n === 0;
        var mn = more.querySelector('.pl-more-n'); if (mn) mn.textContent = n;
        var u = new URL(more.href); if (tab === 'all') u.searchParams.delete('tab'); else u.searchParams.set('tab', tab); more.href = u.toString();
      }
      root.dispatchEvent(new CustomEvent('pl:swap', { bubbles: true, detail: { tab: tab } }));
    }
    $$('[data-ht]', root).forEach(function (t) {
      t.addEventListener('click', function (ev) { ev.preventDefault(); show(t.getAttribute('data-ht')); setParam({ ht: t.getAttribute('data-ht') === 'all' ? '' : t.getAttribute('data-ht') }); });
    });
  }

  /* 3 — Events calendar --------------------------------------------------------- */
  function calendar(root) {
    var form = root.querySelector('form.ebar'); if (!form) return;
    var def = root.getAttribute('data-default') || 'all';
    var grid = root.querySelector('.pl-view-grid'), list = root.querySelector('.pl-view-list');
    var items = $$('.pl-card', root).filter(function (c) { return c.closest('.pl-view-grid, .pl-view-list'); });
    var hasLive = !!root.querySelector('#liveBand');
    var labels = { specialty: 'Specialty', country: 'Country', yr: 'Year', type: 'Type' };
    var hTab = form.querySelector('input[type=hidden][name=tab]'), hView = form.querySelector('input[type=hidden][name=view]'), qIn = form.querySelector('input[name=q]');
    var st = { tab: hTab.value, view: hView.value, q: qIn ? qIn.value : '', f: {} };
    $$('select[data-f]', form).forEach(function (s) { st.f[s.getAttribute('data-f')] = s.value; });
    $$('.pl-nojs', root).forEach(function (b) { b.hidden = true; });
    $$('.pl-js-only', root).forEach(function (b) { b.hidden = false; });

    function match(c) {
      var s = c.getAttribute('data-st');
      if (st.tab === 'all' && hasLive && s === 'live') return false;
      if (st.tab !== 'all' && s !== st.tab) return false;
      if (st.f.specialty && c.getAttribute('data-spec').split('|').indexOf(st.f.specialty) < 0) return false;
      if (st.f.country && c.getAttribute('data-cc') !== st.f.country) return false;
      if (st.f.yr && c.getAttribute('data-year') !== st.f.yr) return false;
      if (st.f.type && c.getAttribute('data-type') !== st.f.type) return false;
      var q = st.q.trim().toLowerCase();
      return !q || c.getAttribute('data-q').indexOf(q) >= 0;
    }
    function optLabel(k, v) { var s = form.querySelector('select[data-f="' + k + '"]'); var o = s && s.querySelector('option[value="' + v.replace(/"/g, '') + '"]'); return o ? o.textContent : v; }
    function render() {
      var n = 0;
      items.forEach(function (c) { var m = match(c); c.hidden = !m; if (m && c.closest('.pl-view-grid')) n++; });
      grid.hidden = st.view !== 'grid'; list.hidden = st.view !== 'list';
      root.querySelector('#count').textContent = n + ' ' + (n === 1 ? 'event' : 'events');
      $$('[data-tab]', form).forEach(function (t) { var on = t.getAttribute('data-tab') === st.tab; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
      $$('[data-view]', form).forEach(function (t) { var on = t.getAttribute('data-view') === st.view; t.classList.toggle('on', on); t.setAttribute('aria-pressed', on ? 'true' : 'false'); });
      var chips = root.querySelector('#chips'); chips.innerHTML = '';
      var any = false;
      Object.keys(labels).forEach(function (k) { if (st.f[k]) { any = true; chips.appendChild(chip(k, labels[k] + ': ' + optLabel(k, st.f[k]))); } });
      if (st.q.trim()) { any = true; chips.appendChild(chip('q', '“' + st.q.trim() + '”')); }
      if (any) { var c = document.createElement('button'); c.type = 'button'; c.className = 'btn bg sm'; c.textContent = 'Clear all'; c.setAttribute('data-act', 'clear'); chips.appendChild(c); }
      var fc = root.querySelector('.pl-fcount'); if (fc) fc.textContent = Object.keys(labels).filter(function (k) { return st.f[k]; }).length;
      root.querySelector('.pl-empty-filtered').hidden = !(n === 0 && any);
      var et = root.querySelector('.pl-empty-tab'); et.hidden = !(n === 0 && !any);
      var tt = et.querySelector('.pl-empty-title'); if (tt) tt.textContent = st.tab === 'upcoming' ? 'No upcoming congresses announced yet.' : 'Nothing here yet.';
      setParam({ tab: st.tab === def ? '' : st.tab, view: st.view === 'grid' ? '' : st.view, q: st.q.trim(), specialty: st.f.specialty, country: st.f.country, yr: st.f.yr, type: st.f.type });
      root.dispatchEvent(new CustomEvent('pl:swap', { bubbles: true, detail: st }));
    }
    function chip(k, text) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'chip'; b.setAttribute('data-unchip', k);
      b.setAttribute('aria-label', 'Remove filter ' + text); b.textContent = text + ' ';
      var x = document.createElement('span'); x.className = 'x'; x.setAttribute('aria-hidden', 'true'); x.textContent = '×'; b.appendChild(x); return b;
    }
    function syncSelects() { $$('select[data-f]', root).forEach(function (s) { s.value = st.f[s.getAttribute('data-f')] || ''; }); }
    function clear() { Object.keys(st.f).forEach(function (k) { st.f[k] = ''; }); st.q = ''; if (qIn) qIn.value = ''; syncSelects(); render(); }

    form.addEventListener('submit', function (ev) { ev.preventDefault(); });
    root.addEventListener('click', function (ev) {
      var t = ev.target.closest('[data-tab],[data-view],[data-unchip],[data-act]'); if (!t || !root.contains(t)) return;
      if (t.hasAttribute('data-tab')) { ev.preventDefault(); st.tab = t.getAttribute('data-tab'); hTab.value = st.tab; render(); }
      else if (t.hasAttribute('data-view')) { ev.preventDefault(); st.view = t.getAttribute('data-view'); hView.value = st.view; render(); }
      else if (t.hasAttribute('data-unchip')) { ev.preventDefault(); var k = t.getAttribute('data-unchip'); if (k === 'q') { st.q = ''; if (qIn) qIn.value = ''; } else st.f[k] = ''; syncSelects(); render(); }
      else if (t.getAttribute('data-act') === 'clear') { ev.preventDefault(); clear(); }
      else if (t.getAttribute('data-act') === 'sheet') { ev.preventDefault(); openSheet(t); }
      else if (t.getAttribute('data-act') === 'sheet-close') { ev.preventDefault(); closeSheet(); }
    });
    root.addEventListener('change', function (ev) { var s = ev.target.closest('select[data-f]'); if (s) { st.f[s.getAttribute('data-f')] = s.value; syncSelects(); render(); } });
    var deb; if (qIn) qIn.addEventListener('input', function () { clearTimeout(deb); deb = setTimeout(function () { st.q = qIn.value; render(); }, 120); });

    /* mobile bottom sheet: the same fields move in and out (one set of controls) */
    var sheet = root.querySelector('#sheet'), frow = root.querySelector('#pl-frow'), opener = null, moved = [];
    function openSheet(btn) {
      opener = btn; var box = sheet.querySelector('.pl-sheet-fields');
      moved = $$('label.fld', frow); moved.forEach(function (l) { box.appendChild(l); });
      sheet.hidden = false; document.documentElement.classList.add('pl-sheet-open');
      var first = sheet.querySelector('select, button'); if (first) first.focus();
      document.addEventListener('keydown', esc);
    }
    function closeSheet() {
      var anchor = frow.querySelector('.pl-nojs'); moved.forEach(function (l) { frow.insertBefore(l, anchor); }); moved = [];
      sheet.hidden = true; document.documentElement.classList.remove('pl-sheet-open');
      document.removeEventListener('keydown', esc); if (opener) opener.focus();
    }
    function esc(ev) { if (ev.key === 'Escape') closeSheet(); }

    render();
  }

  /* 4 — About explorer + chapters ------------------------------------------------ */
  function story(root) {
    var svg = root.querySelector('.map svg'); if (!svg) return;
    function frame(vb) { if (vb) svg.setAttribute('viewBox', vb); root.dispatchEvent(new CustomEvent('pl:frame', { bubbles: true, detail: { viewBox: vb } })); }
    function mark(cc, cities) {
      $$('.pin, .arc', svg).forEach(function (p) {
        var on = cities ? cities.indexOf(p.getAttribute('data-city')) >= 0 : (!cc || p.getAttribute('data-cc') === cc);
        p.classList.toggle('dim', !on);
      });
    }
    $$('#cchips [data-cc]', root).forEach(function (chip) {
      chip.addEventListener('click', function (ev) {
        ev.preventDefault();
        var cc = chip.getAttribute('data-cc');
        $$('#cchips [data-cc]', root).forEach(function (c) { var on = c === chip; c.classList.toggle('on', on); c.setAttribute('aria-pressed', on ? 'true' : 'false'); });
        var panel = null;
        $$('.pl-explore', root).forEach(function (p) { var on = p.getAttribute('data-cc') === cc; p.hidden = !on; if (on) panel = p; });
        frame(panel && (cc ? panel.getAttribute('data-vb') : svg.getAttribute('data-full'))); mark(cc);
        setParam({ country: cc });
      });
    });
    if (!('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var s = en.target;
        $$('.step', root).forEach(function (x) { x.classList.toggle('on', x === s); });
        if (s.hasAttribute('data-ch')) { frame(s.getAttribute('data-vb')); mark('', s.getAttribute('data-cities').split('|')); }
        else { var p = root.querySelector('.pl-explore:not([hidden])'); frame(p && p.getAttribute('data-cc') ? p.getAttribute('data-vb') : svg.getAttribute('data-full')); mark(p ? p.getAttribute('data-cc') : ''); }
      });
    }, { rootMargin: '-45% 0px -45% 0px' });
    $$('.step', root).forEach(function (s) { io.observe(s); });
  }

  function boot() {
    recheck();
    $$('[data-pl="upcoming"]').forEach(upcoming);
    $$('[data-pl="calendar"]').forEach(calendar);
    $$('[data-pl="story"]').forEach(story);
    window.PLEvents = { recheck: recheck };
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
