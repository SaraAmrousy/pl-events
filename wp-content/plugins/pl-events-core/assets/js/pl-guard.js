/* PL guardrails in the browser (Step 6.2b): live hints while typing. The same rules run on the
   server (includes/class-pl-guardrails.php), so nothing depends on this file. */
(function ($) {
  'use strict';
  var G = window.PL_GUARD || { codes: [], lengths: {}, today: '' };

  function hint($field, cls, html) {
    var $box = $field.children('.acf-input').first();
    var $h = $box.children('.pl-hint.' + cls);
    if (!html) { $h.remove(); return; }
    if (!$h.length) { $h = $('<p class="pl-hint ' + cls + '" role="status"></p>').appendTo($box); }
    $h.html(html);
  }
  function css() {
    if (document.getElementById('pl-guard-css')) return;
    var s = document.createElement('style'); s.id = 'pl-guard-css';
    s.textContent = '.pl-hint{margin:6px 0 0;padding:6px 10px;border-radius:6px;font-size:13px;line-height:1.45}' +
      '.pl-hint.warn{background:#fcf3dc;border-left:3px solid #8A5A00;color:#3d2a00}' +
      '.pl-hint.ok{background:#eaf3ee;border-left:3px solid #1B7A4A;color:#0d3b24}' +
      '.pl-hint.err{background:#fbeaea;border-left:3px solid #C62828;color:#5a1010}' +
      '.pl-hint.info{background:#eef1f9;border-left:3px solid #2C4894;color:#142040}';
    document.head.appendChild(s);
  }

  /* ---------- links: add https:// when it's missing ---------- */
  function normUrl(v) {
    v = $.trim(v || '');
    if (!v) return '';
    if (v.indexOf('//') === 0) return 'https:' + v;
    if (!/^[a-z][a-z0-9+.-]*:/i.test(v) && /^[^\s\/]+\.[a-z]{2,}([\/?#:].*)?$/i.test(v)) return 'https://' + v;
    return v;
  }
  function checkUrl(input) {
    var $f = $(input).closest('.acf-field');
    var before = input.value, after = normUrl(before);
    if (after !== before) { input.value = after; $(input).trigger('change'); }
    if (after && after !== $.trim(before) && /^https:\/\//.test(after)) {
      hint($f, 'info', 'Added <strong>https://</strong> at the start, so the link opens.');
    } else if (/^http:\/\//i.test(after)) {
      hint($f, 'warn', 'This link starts with <strong>http://</strong> (not secure). If the site opens with <strong>https://</strong>, use that instead.');
    } else if (after && !/^https?:\/\/[^\s\/]+\.[a-z]{2,}/i.test(after)) {
      hint($f, 'err', 'This doesn’t look like a web address. Paste the full link from the browser’s address bar, e.g. https://www.example.org');
    } else {
      hint($f, 'info', ''); hint($f, 'warn', ''); hint($f, 'err', '');
    }
  }

  /* ---------- phones: clean, move a typed code, preview ---------- */
  function group(d) {
    if (d.length <= 4) return d;
    var last = d.slice(-4), rest = d.slice(0, -4), mid = rest.slice(-3), head = rest.slice(0, -3);
    return (head ? head + ' ' : '') + mid + ' ' + last;
  }
  function checkPhone(row) {
    var $row = $(row);
    var $num = $row.find('[data-name="number"] input'), $code = $row.find('[data-name="code"] select');
    var $wa = $row.find('[data-name="whatsapp"] input[type="checkbox"]');
    if (!$num.length || !$code.length) return;
    var $f = $row.find('[data-name="number"]').first();
    var raw = $.trim($num.val() || ''), code = String($code.val() || '+20').replace(/\D/g, '');
    var moved = '';
    var digits = raw.replace(/\D/g, '');
    if (/^(\+|00)/.test(raw)) {
      digits = digits.replace(/^00/, '');
      for (var i = 0; i < G.codes.length; i++) {
        if (digits.indexOf(G.codes[i]) === 0) {
          if (G.codes[i] !== code && $code.find('option[value="+' + G.codes[i] + '"]').length) {
            $code.val('+' + G.codes[i]).trigger('change'); moved = '+' + G.codes[i];
          }
          code = G.codes[i]; digits = digits.slice(G.codes[i].length); break;
        }
      }
    }
    digits = digits.replace(/^0+/, '');
    if (raw && digits !== raw) { $num.val(digits); }
    if (!digits) { hint($f, 'ok', ''); hint($f, 'warn', ''); return; }
    var len = (G.lengths[code] || [6, 12]), n = digits.length;
    var msg = moved ? 'Moved <strong>' + moved + '</strong> to “Country code”. ' : '';
    var shown = '+' + code + ' ' + group(digits);
    hint($f, 'ok', msg + 'On the site: <strong dir="ltr">' + shown + '</strong>' + ($wa.is(':checked') ? ' · WhatsApp: <span dir="ltr">wa.me/' + code + digits + '</span>' : ''));
    if (n < len[0]) hint($f, 'warn', 'Looks short for +' + code + ' (' + n + ' digits after the code). Check the number, or choose the right country code.');
    else if (n > len[1]) hint($f, 'warn', 'Looks long for +' + code + ' (' + n + ' digits after the code). Is it from another country? Choose its code in “Country code”.');
    else hint($f, 'warn', '');
  }

  /* ---------- event: dates + registration ---------- */
  function ymd(name) { var v = $('[data-name="' + name + '"] input[type="hidden"]').val() || ''; return v.replace(/\D/g, ''); }
  function checkEvent() {
    var $reg = $('[data-name="pl_registration"]'); if (!$reg.length) return;
    var start = ymd('pl_start'), end = ymd('pl_end') || start, today = (G.today || '').replace(/\D/g, '');
    var open = $reg.find('select').val() === 'open';
    var link = $.trim($('[data-name="pl_registration_url"] input').val() || '');
    hint($('[data-name="pl_end"]'), 'err', start && end && end < start ? 'The end date is before the start date. Pick an end date on or after the start (the same day for a one-day event).' : '');
    var msg = '';
    if (open && !link) msg = '<strong>Registration is “Open”</strong>, so the site needs the <strong>Registration link</strong>. Paste it, or choose “Not open yet”. (Publish is blocked until then.)';
    hint($reg, 'err', msg);
    hint($reg, 'warn', open && end && today && end < today ? 'This event has already ended. Registration should be <strong>Closed</strong>.' : '');
  }

  function bind() {
    css();
    $(document).on('change blur', '.acf-field-url input[type="url"], .acf-field-url input[type="text"]', function () { checkUrl(this); });
    $(document).on('change blur', '[data-name="number"] input', function () { checkPhone($(this).closest('.acf-row')); });
    $(document).on('change', '[data-name="code"] select, [data-name="whatsapp"] input', function () { checkPhone($(this).closest('.acf-row')); });
    $(document).on('change input', '[data-name="pl_registration"] select, [data-name="pl_registration_url"] input, [data-name="pl_start"] input, [data-name="pl_end"] input', checkEvent);
    // Before the browser's own URL check runs on submit: fix "www…" links first.
    document.addEventListener('submit', function () { $('.acf-field-url input').each(function () { this.value = normUrl(this.value); }); }, true);
    document.addEventListener('click', function (e) {
      if (e.target && (e.target.id === 'publish' || e.target.id === 'save-post')) { $('.acf-field-url input').each(function () { this.value = normUrl(this.value); }); }
    }, true);
    $('.acf-row:not(.acf-clone)').each(function () { if ($(this).find('[data-name="number"]').length) checkPhone(this); });
    $('[data-name="pl_end"] .hasDatepicker, [data-name="pl_start"] .hasDatepicker').on('change', function () { setTimeout(checkEvent, 0); });
    checkEvent();
  }
  if (window.acf && acf.addAction) acf.addAction('ready', bind); else $(bind);
})(jQuery);
