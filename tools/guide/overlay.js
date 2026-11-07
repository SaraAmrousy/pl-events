/* PL guide overlay — injected into every page the recorder opens (addInitScript).
   Draws: a visible mouse cursor, a blue circle on every click, Egyptian-Arabic captions (HTML, so
   Arabic shapes and RTL render correctly), numbered callouts for screenshots, and blur on any email,
   password, key or token. State (caption, cursor position) survives page loads via sessionStorage.
   Nothing here is part of the site. */
(() => {
  if (window.__pl) return;
  const FONT = window.__PL_FONT_BASE || '';
  const BLUE = '#2C4894';
  const ss = { get: k => { try { return JSON.parse(sessionStorage.getItem('__pl_' + k)); } catch (e) { return null; } }, set: (k, v) => { try { sessionStorage.setItem('__pl_' + k, JSON.stringify(v)); } catch (e) {} } };
  const css = `
@font-face{font-family:"PL Arabic";src:url(${FONT}plex-arabic-500.woff2) format("woff2");font-weight:500}
@font-face{font-family:"PL Arabic";src:url(${FONT}plex-arabic-700.woff2) format("woff2");font-weight:700}
@font-face{font-family:"PL Latin";src:url(${FONT}montserrat-normal-latin.woff2) format("woff2");font-weight:400 800}
#__pl{position:fixed;inset:0;pointer-events:none;z-index:2147483646;font-family:"PL Latin","PL Arabic",sans-serif}
#__pl .cur{position:fixed;left:0;top:0;width:26px;height:26px;transform:translate(-3px,-2px);transition:none;filter:drop-shadow(0 2px 3px rgba(0,0,0,.35));display:none}
#__pl .rip{position:fixed;width:46px;height:46px;margin:-23px 0 0 -23px;border-radius:50%;border:3px solid ${BLUE};background:rgba(44,72,148,.18);animation:__plr .7s ease-out forwards}
@keyframes __plr{from{transform:scale(.35);opacity:1}to{transform:scale(1.25);opacity:0}}
#__pl .cap{position:fixed;left:50%;bottom:34px;transform:translateX(-50%);max-width:78%;direction:rtl;text-align:center;
  background:rgba(10,13,24,.9);color:#fff;font:500 25px/1.6 "PL Arabic","PL Latin",sans-serif;padding:14px 28px 16px;border-radius:16px;
  box-shadow:0 10px 30px rgba(10,13,24,.35);border:1px solid rgba(255,255,255,.12);opacity:0;transition:opacity .25s}
#__pl .cap.on{opacity:1}
#__pl .cap b{font-family:"PL Latin","PL Arabic",sans-serif;font-weight:700;color:#BFD0FF;unicode-bidi:isolate;direction:ltr;display:inline-block}
#__pl .cap .n{display:inline-block;min-width:34px;height:34px;line-height:34px;border-radius:50%;background:${BLUE};color:#fff;font:700 18px/34px "PL Latin";margin-inline-end:12px;vertical-align:2px}
#__pl .plcard{position:fixed;inset:0;max-width:none;margin:0;min-width:0;border:0;box-shadow:none;background:#FAFAF7;display:flex;flex-direction:column;align-items:center;justify-content:center;direction:rtl;text-align:center;opacity:0;transition:opacity .35s;padding:0 12%}
#__pl .plcard.on{opacity:1}
#__pl .plcard .k{font:700 15px/1 "PL Latin";letter-spacing:.12em;text-transform:uppercase;color:${BLUE};margin-bottom:22px;direction:ltr}
#__pl .plcard h1{font:700 54px/1.3 "PL Arabic","PL Latin",sans-serif;color:#0A0D18;margin:0 0 18px;max-width:none}
#__pl .plcard p{font:500 26px/1.7 "PL Arabic","PL Latin",sans-serif;color:#57534C;margin:0;max-width:30ch}
#__pl .plcard .vf{position:absolute;inset:60px;border:0}
#__pl .plcard .vf i{position:absolute;width:44px;height:44px;border-color:${BLUE};border-style:solid;border-width:0}
#__pl .plcard .vf i:nth-child(1){top:0;left:0;border-top-width:4px;border-left-width:4px}
#__pl .plcard .vf i:nth-child(2){top:0;right:0;border-top-width:4px;border-right-width:4px}
#__pl .plcard .vf i:nth-child(3){bottom:0;left:0;border-bottom-width:4px;border-left-width:4px}
#__pl .plcard .vf i:nth-child(4){bottom:0;right:0;border-bottom-width:4px;border-right-width:4px}
#__pl .plcard .mark{position:absolute;bottom:84px;font:800 18px/1 "PL Latin";letter-spacing:.2em;color:#0A0D18;direction:ltr}
#__pl .ring{position:fixed;border:3px solid ${BLUE};border-radius:10px;box-shadow:0 0 0 3px rgba(255,255,255,.9),0 0 0 9px rgba(44,72,148,.16)}
#__pl .ring.pulse{animation:__plp 1.1s ease-out 2}
@keyframes __plp{0%{box-shadow:0 0 0 3px rgba(255,255,255,.9),0 0 0 0 rgba(44,72,148,.45)}100%{box-shadow:0 0 0 3px rgba(255,255,255,.9),0 0 0 16px rgba(44,72,148,0)}}
#__pl .num{position:fixed;width:34px;height:34px;margin:-17px 0 0 -17px;border-radius:50%;background:${BLUE};color:#fff;font:700 17px/34px "PL Latin";text-align:center;
  border:2.5px solid #fff;box-shadow:0 3px 10px rgba(10,13,24,.35)}
.__pl-blur{filter:blur(6px)!important}
`;
  let root, cur, capEl;
  function mount() {
    if (root && document.documentElement.contains(root)) return;
    const st = document.createElement('style'); st.textContent = css; document.documentElement.appendChild(st);
    root = document.createElement('div'); root.id = '__pl'; root.setAttribute('aria-hidden', 'true');
    cur = document.createElement('div'); cur.className = 'cur';
    cur.innerHTML = '<svg viewBox="0 0 26 26" width="26" height="26"><path d="M3 2 L3 21 L8.2 16.4 L11.6 24 L15 22.5 L11.7 15 L18.5 15 Z" fill="#0A0D18" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>';
    capEl = document.createElement('div'); capEl.className = 'cap';
    root.append(cur, capEl);
    document.documentElement.appendChild(root);
    const p = ss.get('cur'); if (p && window.__PL_VIDEO) { cur.style.display = 'block'; cur.style.left = p[0] + 'px'; cur.style.top = p[1] + 'px'; }
    const c = ss.get('cap'); if (c) cap(c, true);
    blur();
  }
  function md(t) { // **Button** → <b>Button</b>; [3] → step number
    const esc = String(t).replace(/[&<>]/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[s]));
    return esc.replace(/^\[(\d+)\]\s*/, '<span class="n">$1</span>').replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
  }
  function cap(t, instant) {
    mount(); ss.set('cap', t || null);
    if (!t) { capEl.classList.remove('on'); return; }
    capEl.innerHTML = md(t);
    if (instant) capEl.classList.add('on'); else { capEl.classList.remove('on'); requestAnimationFrame(() => requestAnimationFrame(() => capEl.classList.add('on'))); }
  }
  function card(kicker, title, sub) {
    mount();
    const c = document.createElement('div'); c.className = "plcard";
    c.innerHTML = '<div class="vf"><i></i><i></i><i></i><i></i></div><div class="k"></div><h1></h1><p></p><div class="mark">PL EVENTS · HELP</div>';
    c.querySelector('.k').textContent = kicker; c.querySelector('h1').innerHTML = md(title); c.querySelector('p').innerHTML = md(sub || '');
    root.appendChild(c); requestAnimationFrame(() => requestAnimationFrame(() => c.classList.add('on')));
    return c;
  }
  function uncard() { root && root.querySelectorAll(".plcard").forEach(c => { c.classList.remove('on'); setTimeout(() => c.remove(), 400); }); }
  function rect(el) { const r = el.getBoundingClientRect(); return { x: r.left, y: r.top, w: r.width, h: r.height }; }
  function ring(sel, pulse) {
    mount(); unring();
    const el = typeof sel === 'string' ? document.querySelector(sel) : sel; if (!el) return false;
    const r = rect(el), d = document.createElement('div'); d.className = 'ring' + (pulse ? ' pulse' : '');
    Object.assign(d.style, { left: r.x - 6 + 'px', top: r.y - 6 + 'px', width: r.w + 12 + 'px', height: r.h + 12 + 'px' });
    root.appendChild(d); return true;
  }
  function unring() { root && root.querySelectorAll('.ring,.num').forEach(n => n.remove()); }
  /* Screenshot callouts: [{n, box:[x,y,w,h]}] (boxes measured by the recorder) → ring + number. */
  function callouts(list) {
    mount(); unring();
    const W = innerWidth, H = innerHeight;
    list.forEach(c => {
      const [x, y, w, h] = c.box;
      const d = document.createElement('div'); d.className = 'ring';
      Object.assign(d.style, { left: x - 5 + 'px', top: y - 5 + 'px', width: w + 10 + 'px', height: h + 10 + 'px' });
      const n = document.createElement('div'); n.className = 'num'; n.textContent = c.n;
      const nx = Math.min(Math.max(x - 5, 20), W - 20), ny = Math.min(Math.max(y - 5, 20), H - 20);
      Object.assign(n.style, { left: nx + 'px', top: ny + 'px' });
      root.append(d, n);
    });
  }
  function ringBox(b, pulse) {
    mount(); unring();
    const d = document.createElement('div'); d.className = 'ring' + (pulse ? ' pulse' : '');
    Object.assign(d.style, { left: b[0] - 6 + 'px', top: b[1] - 6 + 'px', width: b[2] + 12 + 'px', height: b[3] + 12 + 'px' });
    root.appendChild(d);
  }
  const EMAIL = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i;
  function blur() {
    document.querySelectorAll('input[type=email],input[type=password],input[name*=key i],input[name*=secret i],input[name*=token i],input[id*=api i]').forEach(i => i.classList.add('__pl-blur'));
    document.querySelectorAll('input[type=text],input[type=url],input:not([type]),textarea').forEach(i => { if (EMAIL.test(i.value)) i.classList.add('__pl-blur'); });
    const w = document.createTreeWalker(document.body || document.documentElement, NodeFilter.SHOW_TEXT, { acceptNode: n => EMAIL.test(n.nodeValue) && !(n.parentElement && n.parentElement.closest('#__pl,script,style,.__pl-blur')) ? 1 : 3 });
    const hits = []; while (w.nextNode()) hits.push(w.currentNode);
    hits.forEach(n => {
      const parts = n.nodeValue.split(new RegExp('(' + EMAIL.source + ')', 'i'));
      const f = document.createDocumentFragment();
      parts.forEach((p, i) => { if (i % 2) { const s = document.createElement('span'); s.className = '__pl-blur'; s.textContent = p; f.appendChild(s); } else if (p) f.appendChild(document.createTextNode(p)); });
      n.parentNode.replaceChild(f, n);
    });
    document.querySelectorAll('a[href^="mailto:"]').forEach(a => a.classList.add('__pl-blur'));
    (window.__PL_BLUR || []).forEach(s => document.querySelectorAll(s).forEach(e => e.classList.add('__pl-blur')));
  }
  if (window.__PL_VIDEO) {
    addEventListener('mousemove', e => { mount(); cur.style.display = 'block'; cur.style.left = e.clientX + 'px'; cur.style.top = e.clientY + 'px'; ss.set('cur', [e.clientX, e.clientY]); }, true);
    addEventListener('mousedown', e => { mount(); const r = document.createElement('div'); r.className = 'rip'; r.style.left = e.clientX + 'px'; r.style.top = e.clientY + 'px'; root.appendChild(r); setTimeout(() => r.remove(), 800); }, true);
  }
  window.__pl = { mount, cap, card, uncard, ring, ringBox, unring, callouts, blur };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
  setInterval(() => { try { blur(); } catch (e) {} }, 700);
})();
