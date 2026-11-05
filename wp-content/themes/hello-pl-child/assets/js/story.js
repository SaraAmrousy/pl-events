/*!
 * PL motion — About story map (3.3 M3 as amended by 3.4b P3, P8): CSS-sticky map, chapters
 * scroll natively (no pin). Takes over the plugin's `pl:story` events and animates:
 * camera move per chapter / country (viewBox tween, labels keep their on-screen size),
 * arcs draw for cities new on the map, rings ripple, other countries dim in the explorer.
 * Full = 1.2 s camera; Light = 0.4 s, no draw; Reduced = the plugin's instant jump.
 */
(function () {
  'use strict';
  if (!window.PLM) return;
  window.PLM.add(function (C) {
    if (C === 'reduced') return;
    var root = document.querySelector('[data-pl="story"]'); if (!root) return;
    var svg = root.querySelector('.map svg'); if (!svg) return;
    var gsap = window.gsap, full = C === 'full', origin = svg.getAttribute('data-origin') || '';
    var arcs = Array.prototype.slice.call(svg.querySelectorAll('.arc'));
    var pins = Array.prototype.slice.call(svg.querySelectorAll('.pin'));
    var labels = svg.querySelectorAll('.ml'), subs = svg.querySelectorAll('.ms');
    arcs.forEach(function (p) { var L = p.getTotalLength(); p.style.strokeDasharray = L; p.style.strokeDashoffset = L; p.dataset.len = L; });

    function camera(vb) {
      if (!vb) return;
      var w = +vb.split(' ')[2], k = w / Math.max(1, svg.clientWidth);
      var fl = (13 * k).toFixed(1), fs = (9.5 * k).toFixed(1), d = full ? 1.2 : 0.4;
      gsap.to(svg, { attr: { viewBox: vb }, duration: d, ease: 'power2.inOut', overwrite: true });
      gsap.to(labels, { attr: { 'font-size': fl }, duration: d, ease: 'power2.inOut', overwrite: true });
      gsap.to(subs, { attr: { 'font-size': fs }, duration: d, ease: 'power2.inOut', overwrite: true });
    }

    root.addEventListener('pl:story', function (ev) {
      ev.preventDefault();
      var d = ev.detail || {};
      camera(d.viewBox);
      arcs.forEach(function (p) {
        var c = p.getAttribute('data-city'), on = !d.cities || d.cities.indexOf(c) >= 0, L = +p.dataset.len;
        var dim = d.mode === 'explore' && d.cc && p.getAttribute('data-cc') !== d.cc;
        if (on && p.dataset.drawn !== '1') {
          if (full) gsap.fromTo(p, { strokeDashoffset: L }, { strokeDashoffset: 0, duration: 0.9, ease: 'power2.out' }); else p.style.strokeDashoffset = 0;
        } else if (!on) { gsap.killTweensOf(p); p.style.strokeDashoffset = L; }
        p.dataset.drawn = on ? '1' : '0';
        gsap.to(p, { opacity: dim ? 0.2 : 0.95, duration: 0.4 });
      });
      pins.forEach(function (g) {
        var c = g.getAttribute('data-city'), on = !d.cities || d.cities.indexOf(c) >= 0;
        var dim = d.mode === 'explore' && d.cc && g.getAttribute('data-cc') !== d.cc && c !== origin;
        gsap.to(g, { opacity: !on ? 0 : dim ? 0.3 : 1, duration: 0.4 });
        if (full && d.fresh && d.fresh.indexOf(c) >= 0) {
          gsap.fromTo(g.querySelector('.ring'), { attr: { r: 4 }, opacity: 0.9 }, { attr: { r: 16 }, opacity: 0, duration: 0.8, ease: 'power1.out' });
        }
      });
    });
    /* if the plugin fired its first state before this module ran, re-frame now */
    var first = root.querySelector('.step[data-ch]');
    if (first && first.offsetParent !== null) {
      var cities = [origin].concat((first.getAttribute('data-cities') || '').split('|').filter(Boolean));
      root.dispatchEvent(new CustomEvent('pl:story', { cancelable: true, detail: { mode: 'chapter', index: 0, cities: cities, fresh: cities, cc: '', viewBox: first.getAttribute('data-vb') } }));
    }
  });
})();
