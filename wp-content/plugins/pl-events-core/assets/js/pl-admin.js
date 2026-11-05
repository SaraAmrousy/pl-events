/* PL admin: city suggestions from cities already used + the bundled list for the chosen country (Step 2.2 D2). */
(function ($) {
  'use strict';
  function bind() {
    var $country = $('[data-name="pl_country"] select');
    var $city = $('[data-name="pl_city"] input[type="text"]');
    if (!$country.length || !$city.length) return;
    var list = document.createElement('datalist');
    list.id = 'pl-city-list';
    document.body.appendChild(list);
    $city.attr('list', 'pl-city-list').attr('autocomplete', 'off');
    function fill() {
      var cities = (window.PL_CITIES || {})[$country.val()] || [];
      list.innerHTML = '';
      cities.slice(0, 400).forEach(function (c) { var o = document.createElement('option'); o.value = c; list.appendChild(o); });
    }
    $country.on('change', fill);
    fill();
  }
  if (window.acf && acf.addAction) acf.addAction('ready', bind); else $(bind);
})(jQuery);
