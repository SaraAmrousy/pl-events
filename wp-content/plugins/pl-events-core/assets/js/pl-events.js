/*!
 * pl-events-core front-end — progressive enhancement only. Every shortcode is a finished,
 * server-rendered section without this file. This adds:
 *  1. status re-check in each event country's local time (cached pages stay honest, D9)
 *  2. Home tabs without reload ([pl_upcoming]) + the manual row (← →, never autoplays)
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
      if (rail) { rail.scrollTo({ left: 0, behavior: 'instant' }); railState(); } // a new tab starts at the first card, no glide
      root.dispatchEvent(new CustomEvent('pl:swap', { bubbles: true, detail: { tab: tab, el: root.querySelector('#homeGrid') } }));
    }
    /* manual row: buttons scroll by one card; disabled at either end; no autoplay, ever */
    var rail = root.querySelector('.rail'), prev = root.querySelector('[data-rail="prev"]'), next = root.querySelector('[data-rail="next"]');
    function reduced() { return document.documentElement.getAttribute('data-motion') === 'reduced' || (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches); }
    function railState() {
      if (!rail || !prev) return;
      var max = rail.scrollWidth - rail.clientWidth;
      prev.disabled = rail.scrollLeft <= 2;
      next.disabled = rail.scrollLeft >= max - 2;
    }
    function step() {
      var c = rail.querySelector('.card:not([hidden])'); if (!c) return rail.clientWidth;
      return c.getBoundingClientRect().width + (parseFloat(getComputedStyle(rail).columnGap) || 0);
    }
    if (rail && prev) {
      var go = function (dir) { rail.scrollBy({ left: dir * step(), behavior: reduced() ? 'auto' : 'smooth' }); };
      prev.addEventListener('click', function () { go(-1); });
      next.addEventListener('click', function () { go(1); });
      var raf = 0;
      rail.addEventListener('scroll', function () { if (!raf) raf = requestAnimationFrame(function () { raf = 0; railState(); }); }, { passive: true });
      window.addEventListener('resize', railState, { passive: true });
      rail.addEventListener('focusin', function (ev) { // Tab through the cards: the row follows
        var c = ev.target.closest('.card'); if (!c) return;
        var r = c.getBoundingClientRect(), b = rail.getBoundingClientRect();
        if (r.left < b.left || r.right > b.right) rail.scrollBy({ left: r.left < b.left ? r.left - b.left : r.right - b.right, behavior: reduced() ? 'auto' : 'smooth' });
      });
      railState();
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
    function optLabel(k, v) { var s = root.querySelector('select[data-f="' + k + '"]'); /* root, not form: on phones the fields live in the bottom sheet */ var o = s && s.querySelector('option[value="' + v.replace(/"/g, '') + '"]'); return o ? o.textContent : v; }
    function render() {
      var n = 0;
      items.forEach(function (c) { var m = match(c); c.hidden = !m; if (m && c.closest('.pl-view-grid')) n++; });
      grid.hidden = st.view !== 'grid'; list.hidden = st.view !== 'list';
      var countText = n + ' ' + (n === 1 ? 'event' : 'events');
      root.querySelector('#count').textContent = countText;
      $$('.pl-count-m', root).forEach(function (c) { c.textContent = countText; }); // phone bar
      $$('[data-tab]', form).forEach(function (t) { var on = t.getAttribute('data-tab') === st.tab; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
      $$('button[data-view]', root).forEach(function (t) { var on = t.getAttribute('data-view') === st.view; t.classList.toggle('on', on); t.setAttribute('aria-pressed', on ? 'true' : 'false'); });
      var chips = root.querySelector('#chips'); chips.innerHTML = '';
      var any = false;
      Object.keys(labels).forEach(function (k) { if (st.f[k]) { any = true; chips.appendChild(chip(k, labels[k] + ': ' + optLabel(k, st.f[k]))); } });
      if (st.q.trim()) { any = true; chips.appendChild(chip('q', '“' + st.q.trim() + '”')); }
      if (any) { var c = document.createElement('button'); c.type = 'button'; c.className = 'btn bg sm'; c.textContent = 'Clear all'; c.setAttribute('data-act', 'clear'); chips.appendChild(c); }
      var nf = Object.keys(labels).filter(function (k) { return st.f[k]; }).length;
      $$('.pl-fcount', root).forEach(function (fc) { fc.textContent = nf; });
      $$('.pl-fwrap', root).forEach(function (w) { w.hidden = !nf; }); // phone bar: "Filters (2)", never "(0)"
      root.querySelector('.pl-empty-filtered').hidden = !(n === 0 && any);
      var et = root.querySelector('.pl-empty-tab'); et.hidden = !(n === 0 && !any);
      var tt = et.querySelector('.pl-empty-title'); if (tt) tt.textContent = st.tab === 'upcoming' ? 'No upcoming congresses announced yet.' : 'Nothing here yet.';
      setParam({ tab: st.tab === def ? '' : st.tab, view: st.view === 'grid' ? '' : st.view, q: st.q.trim(), specialty: st.f.specialty, country: st.f.country, yr: st.f.yr, type: st.f.type });
      root.dispatchEvent(new CustomEvent('pl:swap', { bubbles: true, detail: { state: st, el: root.querySelector('#results') } }));
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
      // Controls only: #results also carries data-view (grid/list state), and matching it cancelled every link
      // click inside the results (6.1b Blocker: card titles, Register now and Website did not open).
      var t = ev.target.closest('[data-tab],button[data-view],[data-unchip],[data-act]'); if (!t || !root.contains(t)) return;
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
      sheet.hidden = false; requestAnimationFrame(function () { sheet.classList.add('sheet-open'); }); document.documentElement.classList.add('pl-sheet-open');
      var first = sheet.querySelector('select, button'); if (first) requestAnimationFrame(function () { requestAnimationFrame(function () { first.focus(); }); }); // after the sheet is laid out
      document.addEventListener('keydown', esc);
    }
    function closeSheet() {
      var anchor = frow.querySelector('.pl-nojs'); moved.forEach(function (l) { frow.insertBefore(l, anchor); }); moved = [];
      sheet.classList.remove('sheet-open'); sheet.hidden = true; document.documentElement.classList.remove('pl-sheet-open');
      document.removeEventListener('keydown', esc); if (opener) opener.focus();
    }
    function esc(ev) { if (ev.key === 'Escape') closeSheet(); }

    render();
  }

  /* 4 — About story + explorer ------------------------------------------------------
     Each change fires a cancelable `pl:story` event { mode, index, cities, fresh, cc, viewBox }.
     The theme's story.js cancels it and animates (camera, arcs, ripples); otherwise the map
     simply jumps to the new framing here. */
  function story(root) {
    var svg = root.querySelector('.map svg'); if (!svg) return;
    var origin = svg.getAttribute('data-origin') || '';
    var chs = $$('.step[data-ch]', root);
    var narrow = window.matchMedia && matchMedia('(max-width: 1023px)').matches && svg.hasAttribute('data-full-narrow');
    var full = narrow ? svg.getAttribute('data-full-narrow') : svg.getAttribute('data-full');
    if (narrow) { svg.setAttribute('viewBox', full); svg.setAttribute('data-aspect', svg.getAttribute('data-aspect-narrow')); }
    function vbOf(el) { return (narrow && el.getAttribute('data-vb-narrow')) || el.getAttribute('data-vb'); }
    function state(d) {
      var ev = new CustomEvent('pl:story', { bubbles: true, cancelable: true, detail: d });
      if (!root.dispatchEvent(ev)) return;
      if (d.viewBox) svg.setAttribute('viewBox', d.viewBox);
      $$('.pin, .arc', svg).forEach(function (p) {
        var c = p.getAttribute('data-city'), on = d.cities ? d.cities.indexOf(c) >= 0 : true;
        var dim = d.mode === 'explore' && d.cc && p.getAttribute('data-cc') !== d.cc && c !== origin;
        p.style.opacity = !on ? 0 : dim ? 0.3 : '';
      });
    }
    function chapter(i) {
      var cities = [origin], fresh = [];
      chs.forEach(function (s, j) {
        if (j > i) return;
        s.getAttribute('data-cities').split('|').forEach(function (c) {
          if (!c) return;
          if (cities.indexOf(c) < 0) { cities.push(c); if (j === i) fresh.push(c); }
        });
      });
      state({ mode: 'chapter', index: i, cities: cities, fresh: fresh, cc: '', viewBox: chs[i].getAttribute('data-vb') });
    }
    function explore() {
      var p = root.querySelector('.pl-explore:not([hidden])'), cc = p ? p.getAttribute('data-cc') : '';
      state({ mode: 'explore', index: -1, cities: null, fresh: [], cc: cc, viewBox: cc ? vbOf(p) : full });
    }
    $$('#cchips [data-cc]', root).forEach(function (chip) {
      chip.addEventListener('click', function (ev) {
        ev.preventDefault();
        var cc = chip.getAttribute('data-cc');
        $$('#cchips [data-cc]', root).forEach(function (c) { var on = c === chip; c.classList.toggle('on', on); if (on) c.setAttribute('aria-current', 'true'); else c.removeAttribute('aria-current'); });
        $$('.pl-explore', root).forEach(function (p) { p.hidden = p.getAttribute('data-cc') !== cc; });
        root.dispatchEvent(new CustomEvent('pl:swap', { bubbles: true, detail: { el: root.querySelector('#side') } }));
        explore();
        setParam({ country: cc });
      });
    });
    var steps = $$('.step', root).filter(function (s) { return s.offsetParent !== null; });
    if (!chs.length || chs[0].offsetParent === null || !('IntersectionObserver' in window)) { explore(); return; }
    chapter(0);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var s = en.target;
        steps.forEach(function (x) { x.classList.toggle('on', x === s); });
        if (s.hasAttribute('data-ch')) chapter(+s.getAttribute('data-ch')); else explore();
      });
    }, { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach(function (s) { io.observe(s); });
  }

  /* 5 — "Add to calendar" menus. They are <details>, so they open without JS; this adds Esc,
     click-outside, one-at-a-time and arrow keys. -------------------------------------------- */
  function calMenus() {
    function closeAll(except) { $$('details.calm[open]').forEach(function (d) { if (d !== except) d.open = false; }); }
    document.addEventListener('toggle', function (ev) {
      var d = ev.target; if (!d.matches || !d.matches('details.calm')) return;
      if (d.open) { closeAll(d); var f = d.querySelector('.cal-menu a'); if (f && d.getAttribute('data-kb') === '1') f.focus(); }
    }, true);
    document.addEventListener('keydown', function (ev) {
      var d = ev.target.closest && ev.target.closest('details.calm');
      if (ev.key === 'Escape') {
        var open = d && d.open ? d : document.querySelector('details.calm[open]');
        if (open) { open.open = false; open.querySelector('summary').focus(); }
        return;
      }
      if (!d) return;
      if (ev.target.tagName === 'SUMMARY' && (ev.key === 'Enter' || ev.key === ' ' || ev.key === 'ArrowDown')) {
        d.setAttribute('data-kb', '1');
        if (ev.key === 'ArrowDown') { ev.preventDefault(); d.open = true; }
        return;
      }
      if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
        var links = $$('.cal-menu a', d), i = links.indexOf(document.activeElement); if (i < 0) return;
        ev.preventDefault(); links[(i + (ev.key === 'ArrowDown' ? 1 : links.length - 1)) % links.length].focus();
      }
    });
    document.addEventListener('pointerdown', function (ev) {
      closeAll(ev.target.closest && ev.target.closest('details.calm'));
      $$('details.calm').forEach(function (x) { x.removeAttribute('data-kb'); });
    });
    document.addEventListener('focusin', function (ev) { closeAll(ev.target.closest && ev.target.closest('details.calm')); });
    document.addEventListener('click', function (ev) {
      var a = ev.target.closest && ev.target.closest('details.calm .cal-menu a');
      if (a) { var d = a.closest('details'); setTimeout(function () { d.open = false; }, 0); }
    });
  }

  /* 6 — Equal-height cards (DS7): a card title is at most 3 lines. Past that, the card shows the
     series short label ("MOCAT 2027"); the full name stays in the link's accessible name (it starts
     with the visible text — WCAG 2.5.3), on the event page and in the list. Never an ellipsis.
     The venue is dropped from the card when "City, Country · Venue" runs past 2 lines. -------- */
  function lines(el) {
    var cs = getComputedStyle(el), lh = parseFloat(cs.lineHeight);
    if (!lh) lh = parseFloat(cs.fontSize) * 1.25;
    return Math.round(el.getBoundingClientRect().height / lh);
  }
  function fitCards(root) {
    $$('.card.pl-card', root).forEach(function (c) {
      if (!c.offsetParent) return; // hidden (other tab / filtered out): fitted when shown
      var ct = c.querySelector('.ct[data-short]');
      if (ct) {
        var a = ct.querySelector('a'), full = ct.querySelector('.ct-full'), sh = ct.querySelector('.ct-short');
        full.hidden = false; sh.hidden = true; a.removeAttribute('aria-label');
        if (lines(ct) > 3) { full.hidden = true; sh.hidden = false; a.setAttribute('aria-label', sh.textContent + ' — ' + a.getAttribute('data-full')); }
      }
      var meta = c.querySelector('.cm > span:last-child'), v = meta && meta.querySelector('.cm-venue');
      if (v) { v.hidden = false; if (lines(meta) > 2) v.hidden = true; }
    });
  }
  function cardFit() {
    var raf = 0, w = window.innerWidth;
    var run = function () { raf = 0; fitCards(document); };
    run();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(run);
    window.addEventListener('resize', function () { if (window.innerWidth === w || raf) return; w = window.innerWidth; raf = requestAnimationFrame(run); }, { passive: true });
    document.addEventListener('pl:swap', function () { requestAnimationFrame(run); });
  }

  /* 7 — Newsletter sign-up (Step 4.6). Without JS the form posts and the page shows the result; with JS it
     stays on the page: loading → success ("check your inbox") or a blame-free error with retry. ------------- */
  var NL = {
    ok: 'Almost done — check your inbox and confirm your email.',
    queued: 'Almost done — check your inbox and confirm your email. It can take a few minutes.',
    invalid: 'That email address doesn’t look complete. Please check it and try again.',
    limited: 'Too many tries from this connection. Please try again in an hour.',
    error: 'We couldn’t send that just now — nothing is wrong on your side. Please try again.'
  };
  function newsletter(form) {
    var email = form.querySelector('input[type=email]'), btn = form.querySelector('button[type=submit]'), msg = form.querySelector('.pl-nl-msg');
    var label = btn.textContent;
    function say(state) {
      msg.hidden = false; msg.setAttribute('data-state', state); msg.textContent = NL[state] || NL.error;
      email.setAttribute('aria-invalid', state === 'invalid' ? 'true' : 'false');
    }
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      if (!email.value.trim() || !email.checkValidity()) { say('invalid'); email.focus(); return; }
      var fd = new FormData(form); fd.set('page', location.href.split('#')[0]);
      btn.disabled = true; btn.textContent = 'Subscribing…'; form.setAttribute('aria-busy', 'true');
      fetch(form.getAttribute('data-rest'), { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (j) {
          var st = j && j.state; say(st === 'ok' || st === 'queued' ? st : (st || 'error'));
          if (st === 'ok' || st === 'queued') form.reset();
        })
        .catch(function () { say('error'); })
        .then(function () { btn.disabled = false; btn.textContent = label; form.removeAttribute('aria-busy'); });
    });
  }

  function boot() {
    $$('form[data-pl="newsletter"]').forEach(newsletter);
    calMenus();
    cardFit();
    recheck();
    $$('[data-pl="upcoming"]').forEach(upcoming);
    $$('[data-pl="calendar"]').forEach(calendar);
    $$('[data-pl="story"]').forEach(story);
    window.PLEvents = { recheck: recheck };
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
