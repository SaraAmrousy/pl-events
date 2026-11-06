/*!
 * PL motion — viewfinder (3.3 M2, Events signature). One bracket set glides to the card
 * nearest the viewport centre (IO rootMargin -45% 0 -45% 0) and follows keyboard focus.
 * Cards never move. Full = 0.4 s glide; Light = jump; Reduced = none.
 * Home row (Thread 8): the brackets hold the card nearest the row's centre — Full only.
 */
(function () {
  'use strict';
  if (!window.PLM) return;
  window.PLM.add(function (C) {
    if (C !== 'full') return;
    var wrap = document.querySelector('[data-pl="upcoming"] .pl-rail'), rail = wrap && wrap.querySelector('.rail');
    if (!rail) return;
    var vf = document.createElement('div'), cur = null, raf = 0;
    vf.className = 'vf-float'; vf.setAttribute('aria-hidden', 'true'); wrap.appendChild(vf);
    function place(card, instant) {
      if (!card) { vf.style.opacity = 0; return; }
      var w = wrap.getBoundingClientRect(), r = card.getBoundingClientRect();
      vf.style.width = r.width + 'px'; vf.style.height = r.height + 'px';
      window.gsap.to(vf, { x: r.left - w.left, y: r.top - w.top, opacity: 1, duration: instant ? 0 : 0.4, ease: 'power3.out', overwrite: true });
      cur = card;
    }
    function pick(instant) {
      var b = rail.getBoundingClientRect(), mid = b.left + b.width / 2, best = null, d = 1e9;
      Array.prototype.forEach.call(rail.querySelectorAll('.card:not([hidden])'), function (c) {
        var r = c.getBoundingClientRect(), dd = Math.abs(r.left + r.width / 2 - mid);
        if (r.right > b.left && r.left < b.right && dd < d) { d = dd; best = c; }
      });
      if (best !== cur || instant) place(best, instant);
    }
    rail.addEventListener('scroll', function () { if (!raf) raf = requestAnimationFrame(function () { raf = 0; pick(false); }); }, { passive: true });
    rail.addEventListener('focusin', function (ev) { var c = ev.target.closest('.card'); if (c) place(c, false); });
    document.addEventListener('pl:swap', function () { cur = null; requestAnimationFrame(function () { pick(true); }); });
    var rz = 0; window.addEventListener('resize', function () { if (!rz) rz = requestAnimationFrame(function () { rz = 0; pick(true); }); }, { passive: true });
    pick(true);
  });
  window.PLM.add(function (C) {
    if (C === 'reduced') return;
    var results = document.querySelector('[data-pl="calendar"] .results'); if (!results) return;
    var vf = document.createElement('div');
    vf.className = 'vf-float'; vf.id = 'vfFloat'; vf.setAttribute('aria-hidden', 'true');
    results.appendChild(vf);
    var io = null, current = null;
    function move(card) {
      if (!card || card.hidden || !card.offsetParent) return;
      current = card;
      var box = results.getBoundingClientRect(), r = card.getBoundingClientRect();
      var x = r.left - box.left, y = r.top - box.top;
      vf.style.width = r.width + 'px'; vf.style.height = r.height + 'px';
      window.gsap.to(vf, { x: x, y: y, opacity: 1, duration: C === 'full' ? 0.4 : 0, ease: 'power3.out', overwrite: true });
    }
    function watch() {
      if (io) io.disconnect();
      var cards = Array.prototype.slice.call(results.querySelectorAll('.pl-view-grid .card:not([hidden])'));
      var grid = results.querySelector('.pl-view-grid');
      vf.style.display = cards.length && grid && !grid.hidden ? '' : 'none';
      if (!cards.length) return;
      move(cards[0]);
      io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) move(e.target); }); }, { rootMargin: '-45% 0px -45% 0px' });
      cards.forEach(function (c) { io.observe(c); });
    }
    results.addEventListener('focusin', function (ev) { var c = ev.target.closest('.card'); if (c) move(c); });
    document.addEventListener('pl:swap', function () { requestAnimationFrame(watch); });
    var rz2 = 0; window.addEventListener('resize', function () { if (current && !rz2) rz2 = requestAnimationFrame(function () { rz2 = 0; move(current); }); }, { passive: true });
    watch();
  });
})();
