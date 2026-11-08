/* PL Help (Step 6.2b): search on the Help page, "?" side panel, print the cheat sheet.
   No-JS: the Help page shows every task (details/summary), so nothing depends on this file. */
(function () {
  'use strict';
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* search: matches title/steps/tips; opens matches only when the user is searching */
  var q = document.getElementById('plh-q');
  if (q) {
    var tasks = [].slice.call(document.querySelectorAll('.plh-chapter .plh-task'));
    var count = document.getElementById('plh-count');
    var norm = function (s) { return (s || '').toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي'); };
    q.addEventListener('input', function () {
      var v = norm(q.value.trim()), n = 0;
      tasks.forEach(function (t) {
        var hit = !v || norm(t.getAttribute('data-q') + ' ' + t.querySelector('summary').textContent).indexOf(v) > -1;
        t.hidden = !hit; if (hit) n++;
      });
      document.querySelectorAll('.plh-chapter').forEach(function (s) { s.hidden = !!v && !s.querySelector('.plh-task:not([hidden])') && !s.querySelector('#plh-cheatsheet'); });
      count.textContent = v ? (n ? n + ' نتيجة' : 'مفيش نتايج. جرّبوا كلمة تانية، أو بالإنجليزي زي ما على الزرار.') : '';
    });
    var hash = location.hash && document.querySelector(location.hash);
    if (hash && hash.tagName === 'DETAILS') { hash.open = true; hash.scrollIntoView({ block: 'start' }); }
  }

  /* video posters load only when their task is opened (a closed task loads nothing) */
  function posters(root) { (root || document).querySelectorAll('video[data-poster]').forEach(function (v) { if (v.closest('details:not([open])')) return; v.poster = v.dataset.poster; v.removeAttribute('data-poster'); }); }
  document.addEventListener('toggle', function (e) { if (e.target.open) posters(e.target); }, true);
  posters();

  /* pause other videos when one plays */
  document.addEventListener('play', function (e) {
    document.querySelectorAll('.plh video').forEach(function (v) { if (v !== e.target) v.pause(); });
  }, true);

  /* print the cheat sheet only */
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('.plh-print');
    if (!b) return;
    printCheat();
    window.print();
  });
  function printCheat() { // a copy of the cheat sheet, alone under <body>, so the page holds nothing else
    var src = document.getElementById('plh-cheatsheet'); if (!src) return;
    var old = document.getElementById('plh-print-root'); if (old) old.remove();
    var root = document.createElement('div'); root.id = 'plh-print-root'; root.className = 'plh'; root.setAttribute('dir', 'rtl'); root.setAttribute('lang', 'ar');
    root.appendChild(src.cloneNode(true)); root.firstChild.id = 'plh-cheat-print';
    document.body.appendChild(root); document.body.classList.add('plh-printing');
  }
  function endPrint() { document.body.classList.remove('plh-printing'); var r = document.getElementById('plh-print-root'); if (r) r.remove(); }
  window.addEventListener('afterprint', endPrint);
  window.PLHelpPrint = printCheat;

  /* "?" side panel: non-modal, Esc closes, focus moves in and back */
  var fab = document.getElementById('plh-fab'), panel = document.getElementById('plh-panel');
  if (!fab || !panel) return;
  function open() {
    var tpl = document.getElementById('plh-panel-tpl'), body = panel.querySelector('.plh-panel-body');
    if (tpl && !body.childElementCount) { body.appendChild(tpl.content.cloneNode(true)); posters(body); }
    panel.hidden = false; fab.setAttribute('aria-expanded', 'true');
    if (!reduce) { panel.classList.add('is-entering'); requestAnimationFrame(function () { requestAnimationFrame(function () { panel.classList.remove('is-entering'); }); }); }
    panel.focus();
  }
  function close() {
    panel.querySelectorAll('video').forEach(function (v) { v.pause(); });
    panel.hidden = true; fab.setAttribute('aria-expanded', 'false'); fab.focus();
  }
  fab.addEventListener('click', function () { panel.hidden ? open() : close(); });
  panel.querySelector('.plh-close').addEventListener('click', close);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !panel.hidden) { e.preventDefault(); close(); }
    if (e.key === '?' && e.shiftKey === true && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || '') && !(document.activeElement || {}).isContentEditable) { e.preventDefault(); panel.hidden ? open() : close(); }
  });
})();
