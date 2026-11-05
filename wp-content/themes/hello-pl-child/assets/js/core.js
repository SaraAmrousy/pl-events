/*!
 * PL motion — core (Step 3.3 "In Focus", amended by 3.4b). Decides the context once:
 *   full    ≥1024px, fine pointer, no reduced motion  → everything
 *   light   mobile / touch                             → opacity only, no blur, no Lenis
 *   reduced prefers-reduced-motion or Save-Data        → final states, no motion
 * transform + opacity only; blur only for focus pull; ScrollTrigger as a trigger, never pin.
 * Modules register with PLM.add(fn); they run in order once the DOM is ready.
 */
(function () {
  'use strict';
  var html = document.documentElement;
  var mods = [];
  var PLM = window.PLM = {
    ctx: html.getAttribute('data-motion') || 'reduced',
    lenis: null,
    add: function (fn) { mods.push(fn); },
    on: function (type, fn) { document.addEventListener(type, fn); },
    reduced: function () { return PLM.ctx === 'reduced' || !window.gsap; },
    /* sticky offset for the Events filter bar = header height */
    setTop: function () {
      var t = document.querySelector('.topbar'), admin = document.getElementById('wpadminbar');
      var h = (t && !t.classList.contains('fx') ? t.offsetHeight : 0) + (admin && getComputedStyle(admin).position === 'fixed' ? admin.offsetHeight : 0);
      html.style.setProperty('--top', h + 'px');
    },
    refresh: function () { if (window.ScrollTrigger) window.ScrollTrigger.refresh(); }
  };

  function reveal() { html.classList.remove('pl-mp', 'pl-iris'); }

  /* P11 — header transparent over the Home hero, solid once the hero has gone */
  function headerState() {
    var hero = document.getElementById('hero'), hdr = document.getElementById('hdr');
    if (!hero || !hdr) return;
    hdr.classList.add('over');
    var top = hdr.closest('.topbar'); if (top) top.classList.add('fx');
    PLM.setTop();
    window.ScrollTrigger.create({
      trigger: hero, start: function () { return 'bottom ' + (hdr.offsetHeight + 1); },
      onEnter: function () { hdr.classList.remove('over'); }, onLeaveBack: function () { hdr.classList.add('over'); }
    });
  }

  function lenis() {
    if (PLM.ctx !== 'full' || !window.Lenis || !document.body.classList.contains('home')) return;
    if (document.documentElement.scrollHeight - window.innerHeight < 200) return;
    PLM.lenis = new window.Lenis({ lerp: 0.1, wheelMultiplier: 1 });
    PLM.lenis.on('scroll', window.ScrollTrigger.update);
    window.gsap.ticker.add(function (t) { if (PLM.lenis) PLM.lenis.raf(t * 1000); });
    window.gsap.ticker.lagSmoothing(0);
    /* in-page anchors (Browse events) go through Lenis so the scroll stays smooth */
    document.addEventListener('click', function (ev) {
      var a = ev.target.closest('a[href^="#"]'); if (!a || a.getAttribute('href').length < 2) return;
      var t = document.querySelector(a.getAttribute('href')); if (!t) return;
      ev.preventDefault(); PLM.lenis.scrollTo(t, { offset: -(document.getElementById('hdr') || { offsetHeight: 0 }).offsetHeight });
    });
  }

  function boot() {
    PLM.setTop();
    window.addEventListener('resize', PLM.setTop, { passive: true });
    if (!window.gsap || !window.ScrollTrigger) { reveal(); return; }
    window.gsap.registerPlugin(window.ScrollTrigger);
    headerState();
    mods.forEach(function (fn) { try { fn(PLM.ctx, PLM); } catch (e) { if (window.console) console.error(e); } });
    reveal();
    lenis();
    requestAnimationFrame(PLM.refresh);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(PLM.refresh);
    window.addEventListener('load', PLM.refresh, { once: true });
  }
  /* Deferred scripts run while readyState is 'interactive' — before DOMContentLoaded and before
     the module files that follow this one. So wait for DOMContentLoaded (or load, if we came late). */
  var booted = false;
  function once() { if (!booted) { booted = true; boot(); } }
  if (document.readyState === 'complete') once();
  else { document.addEventListener('DOMContentLoaded', once); window.addEventListener('load', once); }
})();
