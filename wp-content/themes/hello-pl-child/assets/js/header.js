/*!
 * PL header — mobile menu (dialog): opens from the menu button, traps focus, closes on
 * Esc / the close button / a link, and gives focus back to the button. Not motion: loads everywhere.
 */
(function () {
  'use strict';
  function boot() {
    var menu = document.getElementById('mmenu'), btn = document.querySelector('[data-act="menu"]');
    if (!menu || !btn) return;
    function focusables() { return Array.prototype.slice.call(menu.querySelectorAll('a[href], button:not([disabled])')); }
    function open() {
      menu.hidden = false; btn.setAttribute('aria-expanded', 'true');
      document.documentElement.style.overflow = 'hidden';
      if (window.PLM && window.PLM.lenis) window.PLM.lenis.stop();
      var f = focusables(); if (f.length) f[0].focus();
      document.addEventListener('keydown', key);
    }
    function close(back) {
      if (menu.hidden) return;
      menu.hidden = true; btn.setAttribute('aria-expanded', 'false');
      document.documentElement.style.overflow = '';
      if (window.PLM && window.PLM.lenis) window.PLM.lenis.start();
      document.removeEventListener('keydown', key);
      if (back !== false) btn.focus();
    }
    function key(ev) {
      if (ev.key === 'Escape') { ev.preventDefault(); close(); return; }
      if (ev.key !== 'Tab') return;
      var f = focusables(), first = f[0], last = f[f.length - 1];
      if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
      else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
    }
    btn.addEventListener('click', open);
    menu.addEventListener('click', function (ev) {
      if (ev.target.closest('[data-act="menu-close"]')) close();
      else if (ev.target.closest('a[href]')) close(false);
    });
    window.addEventListener('resize', function () { if (window.innerWidth >= 1024) close(false); }, { passive: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
