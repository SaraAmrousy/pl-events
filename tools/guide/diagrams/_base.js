// Shared look for the guide drawings: PL design system (paper / ink / #2C4894, Montserrat + IBM Plex Sans Arabic).
(function () {
  var F = "";
  var s = document.createElement('style');
  s.textContent = '' +
  'html{background:#FAFAF7}body{margin:0;width:1440px;min-height:810px;box-sizing:border-box;padding:64px 80px;font-family:M,A,sans-serif;color:#0A0D18;direction:rtl;position:relative}' +
  '.vf{position:absolute;inset:28px;pointer-events:none}.vf i{position:absolute;width:40px;height:40px;border:0 solid #2C4894}.vf i:nth-child(1){top:0;left:0;border-top-width:3px;border-left-width:3px}.vf i:nth-child(2){top:0;right:0;border-top-width:3px;border-right-width:3px}.vf i:nth-child(3){bottom:0;left:0;border-bottom-width:3px;border-left-width:3px}.vf i:nth-child(4){bottom:0;right:0;border-bottom-width:3px;border-right-width:3px}' +
  '.k{font:700 14px/1 M;letter-spacing:.14em;text-transform:uppercase;color:#2C4894;direction:ltr;text-align:right;margin:0 0 14px}' +
  'h1{font:700 46px/1.25 A,M;margin:0 0 10px}h1 em{font-style:normal;color:#2C4894}.sub{font:500 22px/1.6 A,M;color:#57534C;margin:0 0 40px;max-width:60ch}' +
  'b,.en{font-family:M;font-weight:700;unicode-bidi:isolate}' +
  '.row{display:flex;align-items:stretch;gap:0}.box{flex:1;background:#fff;border:1.5px solid #DAD6CC;border-radius:14px;padding:24px 22px;display:flex;flex-direction:column;gap:10px}' +
  '.box.blue{background:#2C4894;border-color:#2C4894;color:#fff}.box.ink{background:#0A0D18;border-color:#0A0D18;color:#fff}.box.sand{background:#EFECE5}' +
  '.box .t{font:700 24px/1.35 A,M}.box .d{font:500 18px/1.6 A,M;opacity:.85}.box .n{font:800 15px/30px M;width:30px;height:30px;border-radius:50%;background:#2C4894;color:#fff;text-align:center}.box.blue .n,.box.ink .n{background:#fff;color:#2C4894}' +
  '.ar{flex:0 0 64px;display:flex;align-items:center;justify-content:center;font:800 34px/1 M;color:#2C4894}' +
  '.chip{display:inline-block;font:700 15px/1 M;direction:ltr;background:#EEF1F9;color:#142040;border:1px solid #C9D3EE;border-radius:99px;padding:8px 12px;margin:3px}' +
  '.box.blue .chip,.box.ink .chip{background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.3)}' +
  '.foot{position:absolute;left:80px;bottom:52px;font:800 15px/1 M;letter-spacing:.2em;direction:ltr}.note{margin-top:30px;font:500 19px/1.6 A,M;color:#57534C;background:#fff;border-right:4px solid #2C4894;padding:14px 18px;border-radius:8px}';
  document.head.appendChild(s);
  document.addEventListener('DOMContentLoaded', function () { var v = document.createElement('div'); v.className = 'vf'; v.innerHTML = '<i></i><i></i><i></i><i></i>'; document.body.appendChild(v); var f = document.createElement('div'); f.className = 'foot'; f.textContent = 'PL EVENTS · HELP'; document.body.appendChild(f); });
})();
