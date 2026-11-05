/*!
 * PL motion — iris hero (3.3 M1, 3.4b P8). Home only, Full context, once per session:
 * the showreel starts inside the aperture and opens to full-bleed. Transform-only: the
 * wrapper scales about the aperture point while the media counter-scales, so the picture
 * never zooms. Any input skips to the end. Light = fade; Reduced = final state.
 * Also: stills crossfade + Ken Burns (Full), MP4 showreel on desktop only (muted, ≤ 2.5 MB,
 * poster first), Pause button, pauses off-screen and in background tabs.
 */
(function () {
  'use strict';
  if (!window.PLM) return;
  window.PLM.add(function (C) {
    var hero = document.getElementById('hero'); if (!hero) return;
    var gsap = window.gsap, ST = window.ScrollTrigger;
    var iris = document.getElementById('iris'), inn = document.getElementById('irisIn');
    var stills = Array.prototype.slice.call(inn.querySelectorAll('img.still'));
    var reel = inn.querySelector('video.reel');
    var btn = hero.querySelector('[data-act="pause"]');
    var paused = false, timer = null, idx = 0, R = 1, kb = null;

    /* aperture geometry: circle centred at 74% / 46% that covers the hero when open */
    function layout() {
      var W = hero.clientWidth, H = hero.clientHeight, px = 0.74 * W, py = 0.46 * H;
      R = Math.ceil(Math.max(Math.hypot(px, py), Math.hypot(W - px, py), Math.hypot(px, H - py), Math.hypot(W - px, H - py)));
      Object.assign(iris.style, { inset: 'auto', left: px - R + 'px', top: py - R + 'px', width: 2 * R + 'px', height: 2 * R + 'px', borderRadius: '50%' });
      Object.assign(inn.style, { inset: 'auto', left: -(px - R) + 'px', top: -(py - R) + 'px', width: W + 'px', height: H + 'px', transformOrigin: px + 'px ' + py + 'px' });
    }

    /* showreel: real video on desktop (Full) when PL uploads one; else stills */
    var useVideo = !!reel && C === 'full';
    function kenBurns(el) {
      if (C !== 'full' || paused) return;
      if (kb) kb.kill();
      kb = gsap.fromTo(el, { scale: 1.0 }, { scale: 1.06, duration: 6, ease: 'none' });
    }
    function show(i) {
      stills.forEach(function (s, j) { s.classList.toggle('on', j === i); });
      kenBurns(stills[i]);
    }
    function start() {
      if (paused || document.hidden) return;
      if (useVideo) { reel.play && reel.play().catch(function () {}); return; }
      if (C !== 'full' || stills.length < 2 || timer) return;
      timer = setInterval(function () { idx = (idx + 1) % stills.length; show(idx); }, 5000);
    }
    function stop() {
      if (timer) { clearInterval(timer); timer = null; }
      if (kb) kb.pause();
      if (reel && !reel.paused) reel.pause();
    }
    if (useVideo) {
      reel.src = reel.getAttribute('data-src');
      reel.addEventListener('playing', function () { reel.classList.add('on'); }, { once: true });
    }
    if (btn) {
      btn.addEventListener('click', function () {
        paused = !paused;
        hero.classList.toggle('paused', paused);
        btn.textContent = paused ? '▶' : '❚❚';
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        btn.setAttribute('aria-label', paused ? 'Play background video' : 'Pause background video');
        if (paused) stop(); else { if (kb) kb.resume(); start(); }
      });
    }
    document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
    if (ST) ST.create({ trigger: hero, start: 'top top', end: 'bottom top', onLeave: stop, onEnterBack: start });

    if (C === 'reduced') { return; }
    if (C === 'light') {
      gsap.from('#heroFrame > *, .figbar', { opacity: 0, duration: 0.3 });
      return;
    }

    /* Full */
    layout();
    window.addEventListener('resize', layout, { passive: true });
    show(0);
    start();
    var seen = false; try { seen = sessionStorage.getItem('plIris') === '1'; } catch (e) {}
    if (seen || window.scrollY > 10) {
      gsap.from('#heroH1 .w', { opacity: 0, filter: 'blur(8px)', duration: 0.7, stagger: 0.09, ease: 'power2.out', clearProps: 'filter' });
      return;
    }
    try { sessionStorage.setItem('plIris', '1'); } catch (e) {}
    var p = { s: 250 / R };
    var set = function () { gsap.set(iris, { scale: p.s }); gsap.set(inn, { scale: 1 / p.s }); };
    var ap = document.getElementById('apLogo');
    set();
    var tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
    tl.set('#hdr', { opacity: 0 }, 0).set('#shade', { opacity: 0 }, 0).set('.hfade', { opacity: 0 }, 0)
      .set('#heroH1 .w', { opacity: 0, filter: 'blur(8px)' }, 0).set('#heroFrame .brk', { opacity: 0, scale: 1.06 }, 0);
    if (ap) tl.set(ap, { opacity: 1 }, 0);
    tl.to(p, { s: 1, duration: 1.2, ease: 'power3.inOut', onUpdate: set }, 0.4)
      .to('#shade', { opacity: 1, duration: 1.2, ease: 'power3.inOut' }, 0.4);
    if (ap) tl.to(ap, { opacity: 0, duration: 0.3 }, 1.2);
    tl.to('#hdr', { opacity: 1, duration: 0.3 }, 1.4)
      .to('#heroFrame .brk', { opacity: 1, scale: 1, duration: 0.6, ease: 'power3.out' }, 1.5)
      .to('#heroH1 .w', { opacity: 1, filter: 'blur(0px)', duration: 0.7, stagger: 0.09 }, 1.7)
      .to('.hfade', { opacity: 1, duration: 0.3 }, 2.0)
      .set('#heroH1 .w, #hdr, #shade, .hfade', { clearProps: 'filter,opacity' })
      .set(iris, { clearProps: 'transform' }).set(inn, { clearProps: 'transform' });
    var evs = ['wheel', 'keydown', 'pointerdown', 'touchstart'];
    var off = function () { evs.forEach(function (e) { window.removeEventListener(e, skip, true); }); };
    var skip = function () { tl.progress(1); off(); };
    evs.forEach(function (e) { window.addEventListener(e, skip, { capture: true, passive: true, once: true }); });
    tl.eventCallback('onComplete', off);
  });
})();
