/* ===== Motion — Step 3.3 "In Focus". Full / Light / Reduced decided once per render. transform + opacity only;
   blur only for the display-heading focus pull; stroke-dashoffset only for map arcs and the PL outline. ===== */
const Motion = {
  ctx: null, lenis: null, raf: null, io: null, cleanups: [],
  detected() {
    const rm = matchMedia('(prefers-reduced-motion: reduce)').matches || (navigator.connection && navigator.connection.saveData);
    if (rm) return 'reduced';
    return matchMedia('(min-width: 1024px) and (pointer: fine)').matches ? 'full' : 'light';
  },
  ctxName() { return PL.motionOverride && PL.motionOverride !== 'auto' ? PL.motionOverride : this.detected(); },
  kill() {
    this.cleanups.forEach((f) => { try { f(); } catch (e) {} }); this.cleanups = [];
    if (this.ctx) { this.ctx.revert(); this.ctx = null; }
    if (window.ScrollTrigger) ScrollTrigger.getAll().forEach((t) => t.kill());
    if (this.lenis) { this.lenis.destroy(); this.lenis = null; } if (this.raf) { gsap.ticker.remove(this.raf); this.raf = null; }
    if (this.io) { this.io.disconnect(); this.io = null; }
    if (this.io2) { this.io2.disconnect(); this.io2 = null; }
    if (this.io3) { this.io3.disconnect(); this.io3 = null; }
  },
  setTop() { const t = document.querySelector('.topbar'); if (t) document.documentElement.style.setProperty('--top', (t.classList.contains('fx') ? 0 : t.offsetHeight) + 'px'); },
  init(route, partial) {
    this.kill();
    const C = this.ctxName(); document.documentElement.dataset.motion = C; this.setTop();
    if (route === 'home') this.irisLayout();
    if (route === 'about' && !partial) { const st = document.querySelector('.step[data-ch]'); PL.state.about.mode = st ? 'story' : 'explore'; PL.state.about.ch = 0; aboutPanel(); this.story(C); }
    if (!window.gsap) return;
    gsap.registerPlugin(ScrollTrigger);
    this.ctx = gsap.context(() => {
      if (!partial && C !== 'reduced' && route !== 'home') gsap.from('#main', { opacity: 0, duration: 0.25, ease: 'power1.out', clearProps: 'opacity' });
      this.headerState(route);
      if (C !== 'reduced') this.patterns(C);
      if (route === 'home' && !partial) this.hero(C);
    });
    const canScroll = document.documentElement.scrollHeight - window.innerHeight > 200; /* the frame can scroll itself */
    this.canScroll = canScroll;
    if (C === 'full' && route === 'home' && canScroll && window.Lenis) {
      this.lenis = new Lenis({ lerp: 0.1, wheelMultiplier: 1 });
      this.lenis.on('scroll', ScrollTrigger.update);
      this.raf = (t) => this.lenis && this.lenis.raf(t * 1000); gsap.ticker.add(this.raf); gsap.ticker.lagSmoothing(0);
    }
    requestAnimationFrame(() => ScrollTrigger.refresh());
    const rf = () => window.ScrollTrigger && ScrollTrigger.refresh();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(rf);
    window.addEventListener('load', rf, { once: true });
    document.querySelectorAll('main img').forEach((im) => { if (!im.complete) im.addEventListener('load', rf, { once: true }); });
  },
  /* P11 — header transparent over the hero, solid once the hero leaves */
  headerState(route) {
    const bar = document.querySelector('.topbar'), hdr = document.getElementById('hdr');
    if (route !== 'home' || !bar) return;
    ScrollTrigger.create({ trigger: '#hero', start: () => 'bottom ' + (hdr.offsetHeight + 1), onEnter: () => hdr.classList.remove('over'), onLeaveBack: () => hdr.classList.add('over') });
  },
  /* M1 — iris hero. Wrapper scales about the aperture point; the media inside counter-scales so the picture never zooms. */
  irisLayout() {
    const hero = document.getElementById('hero'), iris = document.getElementById('iris'), inn = document.getElementById('irisIn'); if (!hero) return;
    const W = hero.clientWidth, H = hero.clientHeight, px = 0.74 * W, py = 0.46 * H;
    const R = Math.ceil(Math.max(Math.hypot(px, py), Math.hypot(W - px, py), Math.hypot(px, H - py), Math.hypot(W - px, H - py)));
    Object.assign(iris.style, { left: px - R + 'px', top: py - R + 'px', width: 2 * R + 'px', height: 2 * R + 'px' });
    Object.assign(inn.style, { left: -(px - R) + 'px', top: -(py - R) + 'px', width: W + 'px', height: H + 'px', transformOrigin: px + 'px ' + py + 'px' });
    this._R = R;
  },
  hero(C) {
    const onR = () => this.irisLayout(); window.addEventListener('resize', onR); this.cleanups.push(() => window.removeEventListener('resize', onR));
    ScrollTrigger.create({ trigger: '#hero', start: 'top top', end: 'bottom top', onLeave: () => stopStills(), onEnterBack: () => startStills() });
    const vis = () => (document.hidden ? stopStills() : startStills()); document.addEventListener('visibilitychange', vis); this.cleanups.push(() => document.removeEventListener('visibilitychange', vis));
    let seen = false; try { seen = sessionStorage.getItem('plIris') === '1'; } catch (e) {}
    if (C === 'light') { gsap.from('#heroFrame > *, .figbar', { opacity: 0, duration: 0.3 }); return; }
    if (C === 'full' && (seen || window.scrollY > 10)) { gsap.from('#heroH1 .w', { opacity: 0, filter: 'blur(8px)', duration: 0.7, stagger: 0.09, ease: 'power2.out', clearProps: 'filter' }); return; }
    if (C !== 'full') return;
    try { sessionStorage.setItem('plIris', '1'); } catch (e) {}
    const iris = '#iris', inn = '#irisIn', s0 = 250 / this._R, p = { s: s0 };
    const ap = document.getElementById('apLogo');
    const set = () => { gsap.set(iris, { scale: p.s }); gsap.set(inn, { scale: 1 / p.s }); };
    set();
    const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
    tl.set('#hdr', { opacity: 0 }, 0).set('#shade', { opacity: 0 }, 0).set('.hfade', { opacity: 0 }, 0).set('#heroH1 .w', { opacity: 0, filter: 'blur(8px)' }, 0).set('#heroFrame .brk', { opacity: 0, scale: 1.06 }, 0).set(ap, { opacity: 1 }, 0)
      .to(p, { s: 1, duration: 1.2, ease: 'power3.inOut', onUpdate: set }, 0.4)
      .to('#shade', { opacity: 1, duration: 1.2, ease: 'power3.inOut' }, 0.4)
      .to(ap, { opacity: 0, duration: 0.3 }, 1.2)
      .to('#hdr', { opacity: 1, duration: 0.3 }, 1.4)
      .to('#heroFrame .brk', { opacity: 1, scale: 1, duration: 0.6, ease: 'power3.out' }, 1.5)
      .to('#heroH1 .w', { opacity: 1, filter: 'blur(0px)', duration: 0.7, stagger: 0.09 }, 1.7)
      .to('.hfade', { opacity: 1, duration: 0.3 }, 2.0)
      .set('#heroH1 .w', { clearProps: 'filter' });
    const skip = () => { tl.progress(1); off(); };
    const evs = ['wheel', 'keydown', 'pointerdown', 'touchstart'];
    const off = () => evs.forEach((e) => window.removeEventListener(e, skip, true));
    evs.forEach((e) => window.addEventListener(e, skip, { capture: true, passive: true, once: true }));
    this.cleanups.push(() => { off(); tl.kill(); });
  },
  /* P1–P4 */
  patterns(C) {
    const full = C === 'full';
    gsap.utils.toArray('.m-title, main .h1').forEach((el) => {
      if (el.id === 'heroH1') return;
      gsap.from(el, full ? { filter: 'blur(6px)', opacity: 0, duration: 0.6, clearProps: 'filter', scrollTrigger: { trigger: el, start: 'top 80%', once: true } }
        : { opacity: 0, duration: 0.3, scrollTrigger: { trigger: el, start: 'top 85%', once: true } });
    });
    const cards = gsap.utils.toArray('main .pl-card:not(#results .pl-card), main .tile');
    if (cards.length) {
      gsap.set(cards, { opacity: 0, y: full ? 16 : 0 });
      ScrollTrigger.batch(cards, { start: 'top 92%', once: true, onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, duration: full ? 0.5 : 0.3, stagger: full ? 0.06 : 0, ease: 'power2.out', overwrite: true }) });
    }
    const results = gsap.utils.toArray('#results');
    results.forEach((r) => gsap.from(r, { opacity: 0, duration: 0.3 }));
    const panels = gsap.utils.toArray('main .pl-panel');
    if (panels.length) {
      gsap.set(panels, { opacity: 0, y: full ? 24 : 0 });
      ScrollTrigger.batch(panels, { start: 'top 90%', once: true, onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, duration: full ? 0.6 : 0.3, stagger: full ? 0.08 : 0, ease: 'power2.out', overwrite: true }) });
    }
    /* focus set (P5/P6 v1.5): pills pop in one after another, then the brackets close on the word — display text only, once */
    gsap.utils.toArray('.focus-set').forEach((set) => {
      const pills = set.querySelectorAll('.hl-num');
      set.classList.add('pre');
      gsap.set(pills, { scale: full ? 0.4 : 1, opacity: 0, transformOrigin: '50% 60%' });
      ScrollTrigger.create({ trigger: set, start: 'top 82%', once: true, onEnter: () => {
        gsap.to(pills, { scale: 1, opacity: 1, duration: full ? 0.6 : 0.3, stagger: full ? 0.12 : 0, ease: full ? 'back.out(1.7)' : 'power2.out' });
        setTimeout(() => set.classList.remove('pre'), full ? 150 + pills.length * 120 : 0);
      } });
    });
    /* rack focus: photos sharpen like a lens finding focus (images only, never text) */
    gsap.utils.toArray('main img.rack').forEach((im) => gsap.from(im, full ? { filter: 'blur(10px)', scale: 1.04, duration: 0.9, ease: 'power2.out', clearProps: 'filter,transform', scrollTrigger: { trigger: im, start: 'top 85%', once: true } } : { opacity: 0, duration: 0.3, scrollTrigger: { trigger: im, start: 'top 90%', once: true } }));
    const arc = document.querySelector('.evarc');
    if (arc && full) { const L = arc.getTotalLength(); gsap.fromTo(arc, { strokeDasharray: L, strokeDashoffset: L }, { strokeDashoffset: 0, duration: 0.9, delay: 0.3, ease: 'power2.out' }); gsap.fromTo('.evring', { attr: { r: 5 }, opacity: 0.9 }, { attr: { r: 18 }, opacity: 0, duration: 1.2, repeat: 2, delay: 1.1, ease: 'power1.out' }); }
    const ol = document.getElementById('plOutline');
    if (ol && full) { gsap.fromTo(ol, { strokeDasharray: 2400, strokeDashoffset: 2400 }, { strokeDashoffset: 0, duration: 1.2, ease: 'power2.out', scrollTrigger: { trigger: ol, start: 'top 80%', once: true } }); }
  },
  /* Services: sticky photo stack — the photo for the service in focus crossfades in */
  services(C) {
    const steps = Array.from(document.querySelectorAll('.svc-step')); if (!steps.length) return;
    const phs = Array.from(document.querySelectorAll('.svc-ph')), cnt = document.getElementById('svcCount'), n = steps.length;
    const show = (i) => {
      phs.forEach((p, j) => { const on = j === i; p.classList.toggle('on', on); if (on && window.gsap && C === 'full') gsap.fromTo(p, { scale: 1.06, filter: 'blur(6px)' }, { scale: 1, filter: 'blur(0px)', duration: 0.9, ease: 'power2.out', clearProps: 'filter' }); });
      steps.forEach((s, j) => s.classList.toggle('on', j === i)); if (cnt) cnt.textContent = '0' + (i + 1) + ' / 0' + n;
    };
    this.io3 = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) show(+e.target.dataset.i); }), { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach((s) => this.io3.observe(s));
  },
  /* P5/P6/P8 — content swaps crossfade, never slide */
  swap(el) { if (!el || !window.gsap || this.ctxName() === 'reduced') return; gsap.fromTo(el, { opacity: 0 }, { opacity: 1, duration: 0.2, ease: 'power1.out', overwrite: true }); },
  /* M2 — one bracket set glides to the card nearest the viewport centre; cards never move */
  viewfinder() {
    if (this.io) { this.io.disconnect(); this.io = null; }
    const vf = document.getElementById('vfFloat'), C = this.ctxName(); if (!vf || C === 'reduced') return;
    const move = (card) => {
      if (!card) return; vf.style.width = card.offsetWidth + 'px'; vf.style.height = card.offsetHeight + 'px';
      if (window.gsap) gsap.to(vf, { x: card.offsetLeft, y: card.offsetTop, duration: C === 'full' ? 0.4 : 0, ease: 'power3.out', overwrite: true }); else vf.style.transform = 'translate(' + card.offsetLeft + 'px,' + card.offsetTop + 'px)';
    };
    const cards = Array.from(document.querySelectorAll('#results .card')); move(cards[0]);
    this.io = new IntersectionObserver((es) => { es.forEach((e) => { if (e.isIntersecting) move(e.target); }); }, { rootMargin: '-45% 0px -45% 0px' });
    cards.forEach((c) => { this.io.observe(c); c.addEventListener('focusin', () => move(c)); });
  },
  /* M3 — story map: pinned chapters (Full), then the explorer */
  /* M3 (v1.3) — no JS pin: the map is CSS-sticky, chapters scroll natively; an observer picks the step in focus */
  story(C) {
    const steps = Array.from(document.querySelectorAll('.step')); if (!steps.length) return;
    const chs = chapters(D), A = PL.state.about;
    if (!document.querySelector('.step[data-ch]')) { A.mode = 'explore'; this.mapState({ mode: 'explore', cc: A.cc }); return; }
    this.mapState({ mode: 'story', ch: chs[0], chs });
    this.io2 = new IntersectionObserver((es) => es.forEach((e) => {
      if (!e.isIntersecting) return;
      const el = e.target;
      if (el.dataset.explore) { if (A.mode !== 'explore') { A.mode = 'explore'; this.mapState({ mode: 'explore', cc: A.cc }); } }
      else { const i = +el.dataset.ch; if (A.mode !== 'story' || A.ch !== i) { A.mode = 'story'; A.ch = i; this.mapState({ mode: 'story', ch: chs[i], chs }); } }
      steps.forEach((s) => s.classList.toggle('on', s === el));
    }), { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach((s) => this.io2.observe(s));
  },
  mapState(st) {
    const C = this.ctxName(), anim = window.gsap && C !== 'reduced';
    const svg = document.querySelector('#mapWrap svg'); if (!svg) return;
    let active = null, fresh = [];
    if (st.mode === 'story') {
      active = D.evs.filter((e) => e.year <= st.ch.to).map((e) => e.city);
      const before = D.evs.filter((e) => e.year < st.ch.from).map((e) => e.city); fresh = uniq(active).filter((c) => before.indexOf(c) < 0);
      active.push('Alexandria');
    }
    const hq = PL.raw.geo.cities.Alexandria, ll = (cities) => uniq(cities).map((c) => (D.evs.find((e) => e.city === c) || { lonlat: hq }).lonlat).concat([hq]);
    const asp = +svg.dataset.aspect || 2.1;
    const vb = st.mode === 'story' ? viewBoxFor(ll(active), 330, asp) : st.cc ? viewBoxFor(ll(D.evs.filter((e) => e.country === st.cc).map((e) => e.city)), 330, asp) : svg.dataset.full;
    /* keep city labels the same size on screen while the camera zooms */
    const k = vb ? (+vb.split(' ')[2]) / Math.max(1, svg.clientWidth) : 1, fl = (13 * k).toFixed(1), fs = (9.5 * k).toFixed(1);
    const lab = svg.querySelectorAll('.ml'), sub = svg.querySelectorAll('.ms');
    if (vb && svg.getAttribute('viewBox') !== vb) {
      if (anim) { const d = C === 'full' ? 1.2 : 0.4; gsap.to(svg, { attr: { viewBox: vb }, duration: d, ease: 'power2.inOut', overwrite: true }); gsap.to(lab, { attr: { 'font-size': fl }, duration: d, ease: 'power2.inOut' }); gsap.to(sub, { attr: { 'font-size': fs }, duration: d, ease: 'power2.inOut' }); }
      else { svg.setAttribute('viewBox', vb); lab.forEach((t) => t.setAttribute('font-size', fl)); sub.forEach((t) => t.setAttribute('font-size', fs)); }
    } else if (vb) { lab.forEach((t) => t.setAttribute('font-size', fl)); sub.forEach((t) => t.setAttribute('font-size', fs)); }
    svg.querySelectorAll('.arc').forEach((p) => {
      const len = p.getTotalLength(); p.style.strokeDasharray = len;
      const on = !active || active.indexOf(p.dataset.city) >= 0, dim = st.mode === 'explore' && st.cc && p.dataset.cc !== st.cc;
      const target = on ? 0 : len, prev = p.dataset.drawn === '1';
      if (anim && C === 'full' && on && !prev && st.mode === 'story') { gsap.fromTo(p, { strokeDashoffset: len }, { strokeDashoffset: 0, duration: 0.9, ease: 'power2.out' }); }
      else p.style.strokeDashoffset = target;
      p.dataset.drawn = on ? '1' : '0';
      if (anim) gsap.to(p, { opacity: dim ? 0.2 : 0.95, duration: 0.4 }); else p.style.opacity = dim ? 0.2 : 0.95;
    });
    svg.querySelectorAll('.pin').forEach((g) => {
      const on = !active || active.indexOf(g.dataset.city) >= 0, dim = st.mode === 'explore' && st.cc && g.dataset.cc !== st.cc && g.dataset.city !== 'Alexandria';
      const op = !on ? 0 : dim ? 0.3 : 1;
      if (anim) gsap.to(g, { opacity: op, duration: 0.4 }); else g.style.opacity = op;
      const ring = g.querySelector('.ring');
      if (anim && C === 'full' && fresh.indexOf(g.dataset.city) >= 0) gsap.fromTo(ring, { attr: { r: 4 }, opacity: 0.9 }, { attr: { r: 16 }, opacity: 0, duration: 0.8, ease: 'power1.out' });
    });
  },
  /* P7 — mobile filters sheet */
  sheet(open) {
    const s = document.getElementById('sheet'); if (!s) return;
    if (open) { s.classList.add('sheet-open'); document.body.style.overflow = 'hidden'; const f = s.querySelector('select'); f && setTimeout(() => f.focus(), 50); this._sheetOpener = document.activeElement; }
    else if (s.classList.contains('sheet-open')) { s.classList.remove('sheet-open'); document.body.style.overflow = ''; const b = document.querySelector('[data-act="sheet"]'); b && b.focus(); }
  },
  /* P13 — film modal */
  film(open, opener) {
    const m = document.getElementById('film'); if (!m) return;
    if (open) { this._filmOpener = opener; m.hidden = false; if (this.lenis) this.lenis.stop(); const box = m.querySelector('.box'); if (window.gsap && this.ctxName() !== 'reduced') gsap.fromTo(box, { opacity: 0, scale: 0.98 }, { opacity: 1, scale: 1, duration: 0.25 }); m.querySelector('button').focus(); }
    else if (!m.hidden) { m.hidden = true; if (this.lenis) this.lenis.start(); this._filmOpener && this._filmOpener.focus(); }
  }
};
window.addEventListener('resize', () => { Motion.setTop(); if (route === 'events') Motion.viewfinder(); });
