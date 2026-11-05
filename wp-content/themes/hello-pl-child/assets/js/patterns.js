/*!
 * PL motion — patterns (3.3 P1–P4, 3.4b P5/P6/P8). Reading text never moves; entrances once.
 *  - display headings (.m-title, .h1): blur-to-sharp focus pull (Full) / fade (Light)
 *  - cards + tiles rise and fade in batches (stagger .06), panels (stagger .08)
 *  - focus sets: blue number pills pop in one after another, then the brackets close
 *  - rack focus on photos (images only), event-page arc draws, "PL" outline draws
 *  - content swaps (tabs, filters, explorer) crossfade 0.2 s, never slide
 */
(function () {
  'use strict';
  if (!window.PLM) return;
  window.PLM.add(function (C) {
    if (C === 'reduced') return;
    var gsap = window.gsap, ST = window.ScrollTrigger, full = C === 'full';
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var inCalendar = function (el) { return !!el.closest('[data-pl="calendar"] .results'); };

    $$('.m-title, .h1').forEach(function (el) {
      if (el.id === 'heroH1' || el.closest('.hdr, .mmenu, footer')) return;
      if (!full && el.classList.contains('h1')) return; /* Light: the page H1 is the LCP — never delay it */
      gsap.from(el, full
        ? { filter: 'blur(6px)', opacity: 0, duration: 0.6, ease: 'power2.out', clearProps: 'filter,opacity', scrollTrigger: { trigger: el, start: 'top 85%', once: true } }
        : { opacity: 0, duration: 0.3, clearProps: 'opacity', scrollTrigger: { trigger: el, start: 'top 90%', once: true } });
    });

    function batch(items, y, dur, stag) {
      if (!items.length) return;
      gsap.set(items, { opacity: 0, y: full ? y : 0 });
      ST.batch(items, { start: 'top 92%', once: true, onEnter: function (b) {
        gsap.to(b, { opacity: 1, y: 0, duration: full ? dur : 0.3, stagger: full ? stag : 0, ease: 'power2.out', overwrite: true, clearProps: 'transform' });
      } });
    }
    batch($$('.pl-card, .tile').filter(function (el) { return !inCalendar(el) && !el.closest('.hdr, footer'); }), 16, 0.5, 0.06);
    batch($$('.pl-panel'), 24, 0.6, 0.08);

    $$('.focus-set').forEach(function (set) {
      var pills = set.querySelectorAll('.hl-num'); if (!pills.length) return;
      set.classList.add('pre');
      gsap.set(pills, { scale: full ? 0.4 : 1, opacity: 0, transformOrigin: '50% 60%' });
      ST.create({ trigger: set, start: 'top 82%', once: true, onEnter: function () {
        gsap.to(pills, { scale: 1, opacity: 1, duration: full ? 0.6 : 0.3, stagger: full ? 0.12 : 0, ease: full ? 'back.out(1.7)' : 'power2.out' });
        setTimeout(function () { set.classList.remove('pre'); }, full ? 150 + pills.length * 120 : 0);
      } });
    });

    $$('img.rack').forEach(function (im) {
      gsap.from(im, full
        ? { filter: 'blur(10px)', scale: 1.04, duration: 0.9, ease: 'power2.out', clearProps: 'filter,transform', scrollTrigger: { trigger: im, start: 'top 85%', once: true } }
        : { opacity: 0, duration: 0.3, clearProps: 'opacity', scrollTrigger: { trigger: im, start: 'top 90%', once: true } });
    });

    var arc = document.querySelector('.evarc');
    if (arc && full) {
      var L = arc.getTotalLength();
      gsap.fromTo(arc, { strokeDasharray: L, strokeDashoffset: L }, { strokeDashoffset: 0, duration: 0.9, delay: 0.3, ease: 'power2.out' });
      gsap.fromTo('.evring', { attr: { r: 5 }, opacity: 0.9 }, { attr: { r: 18 }, opacity: 0, duration: 1.2, repeat: 2, delay: 1.1, ease: 'power1.out' });
    }
    $$('#plOutline, .pl-outline-draw').forEach(function (ol) {
      if (!full) return;
      gsap.fromTo(ol, { strokeDasharray: 2400, strokeDashoffset: 2400 }, { strokeDashoffset: 0, duration: 1.2, ease: 'power2.out', scrollTrigger: { trigger: ol, start: 'top 80%', once: true } });
    });

    /* swaps from the plugin (tabs, filters, explorer): crossfade the container; shown cards are final */
    document.addEventListener('pl:swap', function (ev) {
      var el = ev.detail && ev.detail.el; if (!el) return;
      gsap.set(el.querySelectorAll('.pl-card:not([hidden])'), { opacity: 1, y: 0 });
      gsap.fromTo(el, { opacity: 0 }, { opacity: 1, duration: 0.2, ease: 'power1.out', overwrite: true });
      requestAnimationFrame(function () { ST.refresh(); });
    });
  });
})();
