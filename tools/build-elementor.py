#!/usr/bin/env python3
"""Builds the Elementor page templates in /elementor (Step 4.4).

Each file is an Elementor Free template export ("Templates › Import" accepts it) and is also
what `plt_provision_pages()` writes into the pages on staging. Every section = one full-width
container holding one Shortcode widget: [pl_section] + the prototype copy (reference/prototype/
src/views.js) + the plugin's [pl_*] shortcodes. Layout only; all data comes from the plugin.
Run after changing a page here; then deploy the theme and re-run provisioning with force.
"""
import hashlib, json, os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'elementor')

def eid(*parts):
    return hashlib.md5('|'.join(parts).encode()).hexdigest()[:7]

ZERO = {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": True}

def section(page, i, code):
    return {
        "id": eid(page, str(i), 'c'), "elType": "container", "isInner": False,
        "settings": {"content_width": "full", "padding": ZERO, "margin": ZERO,
                     "flex_gap": {"unit": "px", "size": 0, "column": "0", "row": "0", "isLinked": True},
                     "css_classes": "pl-raw", "_title": code.split(']')[0].lstrip('[')[:40]},
        "elements": [{"id": eid(page, str(i), 'w'), "elType": "widget", "widgetType": "shortcode", "isInner": False,
                      "settings": {"shortcode": code.strip()}, "elements": []}],
    }

CONTACT = '[pl_url to="contact" topic="proposal"]'
COL = 'display:flex;flex-direction:column;'

PAGES = {
 'home': {"title": "Home", "front": True, "sections": [
  '[pl_hero]',
  '[pl_upcoming layout="grid" tabs="all,live,upcoming,past" default="all" limit="6"]',
  f'''[pl_section class="sec" style="border-top:1.5px solid var(--line);overflow:hidden;position:relative" wrap_style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:center"]
<div class="d-only" style="grid-column:span 5;margin-left:-120px" aria-hidden="true"><svg viewBox="0 0 520 330" width="520" height="330" style="overflow:visible"><text id="plOutline" x="0" y="320" fill="#FAFAF7" stroke="#0A0D18" stroke-width="4" style="font:800 420px Montserrat,sans-serif;letter-spacing:-.02em;paint-order:stroke fill">PL</text></svg></div>
<div style="grid-column:span 7;{COL}gap:20px" class="nm">[pl_stats layout="line"][pl_logos type="society"]<a href="[pl_url to="about" anchor="story"]" class="btn bg" style="align-self:flex-start">Explore the record on the map →</a></div>
[/pl_section]''',
  f'''[pl_section class="sand cutb" style="padding:96px 0 136px" wrap_style="{COL}gap:32px"]
<div class="sh"><h2 class="h2 m-title">Behind the <span class="em">lens.</span></h2><p class="mut" style="font-size:17px;max-width:400px">One team runs the science, the sponsors, the stage and the travel.</p></div>
[pl_services layout="numbered"]
<a href="[pl_url to="services"]" class="btn bs" style="align-self:flex-start">All services</a>
[/pl_section]''',
  f'''[pl_section class="ink" id="cta" style="padding:112px 0 120px" wrap_class="ctag" wrap_style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0"]
<div class="ctacol" style="{COL}gap:18px;padding:8px 0"><span class="lab mut">For scientific societies</span><h2 class="h2 m-title h2l">Planning a <span class="em">congress?</span></h2><p style="font-size:18px;line-height:1.55;color:var(--oim);max-width:46ch">Our Alexandria and Dubai teams plan the science, the sponsors, the stage and the travel — one team from programme to gala dinner.</p><div class="row"><a href="{CONTACT}" class="btn bw">Request a proposal</a></div></div>
<div class="ctar" style="border-left:1.5px solid #2A3149;padding-left:56px"><div class="ctacol" style="{COL}gap:18px;padding:8px 0"><span class="lab mut">For sponsors &amp; exhibitors</span><h2 class="h2 m-title h2l">Put your brand <span class="em">in focus.</span></h2>[pl_stats layout="sponsor_line"]<div class="row"><a href="[pl_url to="contact" topic="sponsor"]" class="btn bso">Sponsor a congress</a><a href="[pl_url to="services" anchor="sponsorship"]" class="btn bg">See sponsorship</a></div></div></div>
[/pl_section]''',
 ]},
 'events': {"title": "Events", "sections": [
  '[pl_calendar default="all" search="1" filters="specialty,country,year,type" views="grid,list"]',
  f'''[pl_section class="ink cutt" style="padding:120px 0 96px" wrap_class="sh"]
<h2 class="h2 m-title" style="max-width:720px">Running a congress? See how we can <span class="em">help.</span></h2><a href="{CONTACT}" class="btn bw">Request a proposal</a>
[/pl_section]''',
 ]},
 'about': {"title": "About", "sections": [
  f'''[pl_section class="" style="padding:88px 0 72px" wrap_style="{COL}gap:36px"]
<span class="lab mut">About PL Events</span><h1 class="h1 h1xl focus-set">From <span class="hl-city">Alexandria</span> to [pl_stats layout="number" field="countries"] countries.</h1>
<div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" class="ab-int"><p class="lead" style="grid-column:span 6">PL Events plans and runs medical congresses for scientific societies, from a head office in Alexandria and a regional office in Dubai. Most of our work is ophthalmology; vascular, lymphology and nephrology are growing.</p><p class="mut" style="grid-column:8 / span 5;font-size:17px">One team handles the science, the sponsors, the stage and the travel — and every congress since [pl_stats field="since_long"] is on this site, with its date, city and registration.</p></div>
<figure style="margin:0">[pl_media src="assets/hall-wide.jpg" alt="Delegates seated in a congress hall set up by PL Events" class="photo rack" frame="wide" frame_style="border-radius:10px" sizes="(max-width: 767px) 175vw, 1200px"]<figcaption class="lab mut" style="margin-top:10px">From PL’s portfolio</figcaption></figure>
[/pl_section]''',
  '[pl_story layout="sticky explore" aspect="1.3"]',
  '[pl_section class="sec"][pl_company_figures layout="big"][/pl_section]',
  f'''[pl_section class="sand sec" style="padding:88px 0" wrap_style="{COL}gap:24px"]
<h2 class="h2 m-title" style="font-size:40px">Societies that trust <span class="em">us.</span></h2>[pl_logos type="society"]
[/pl_section]''',
  '[pl_offices layout="split"]',
  f'''[pl_section class="sec" style="padding:104px 0 112px;position:relative;overflow:hidden;border-top:1.5px solid var(--line)" wrap="0"]
<div aria-hidden="true" class="out pl-out d-only" style="position:absolute;right:40px;top:50%;transform:translateY(-50%);font-size:340px;line-height:.8;-webkit-text-stroke-color:#D9D5CC">PL</div>
<div class="wrap" style="position:relative;{COL}gap:22px"><h2 class="h2 h2xl h2xxl">Two cities. <span class="em">One team.</span></h2><p class="mut" style="font-size:18px;max-width:560px">Tell us about your congress — the Alexandria or Dubai team answers.</p><div class="row"><a href="{CONTACT}" class="btn bp">Request a proposal</a>[pl_company field="portfolio_link" class="btn bs" label="Download our portfolio"]</div></div>
[/pl_section]''',
 ]},
 'services': {"title": "Services", "sections": [
  f'''[pl_section class="" style="padding:72px 0 24px" wrap_style="{COL}gap:20px"]
<span class="lab mut">Services</span><h1 class="h1">Behind the <span class="em">lens.</span></h1><p class="lead">One team runs the science, the sponsors, the stage and the travel — for congresses, symposia, wetlabs and webinars.</p>
[/pl_section]''',
  '[pl_section class="" style="padding:24px 0 72px"][pl_services layout="zigzag"][/pl_section]',
  f'''[pl_section class="ink sec" id="sponsorship" wrap_style="{COL}gap:24px"]
<span class="lab mut">Sponsorship</span><h2 class="h2 m-title">Put your brand <span class="em">in focus.</span></h2><p class="lead" style="color:var(--oim)">[pl_stats layout="sponsor_line" tag="span"] Companies that have sponsored PL congresses:</p>[pl_logos type="sponsor"]<div class="row"><a href="[pl_url to="contact" topic="sponsor"]" class="btn bw">Sponsor a congress</a></div>
[/pl_section]''',
  f'''[pl_section class="sec" wrap_class="sh"]
<h2 class="h2 m-title" style="max-width:720px">Planning a congress? Talk to our <span class="em">team.</span></h2><a href="{CONTACT}" class="btn bp">Request a proposal</a>
[/pl_section]''',
 ]},
 'careers': {"title": "Careers", "sections": [
  f'''[pl_section class="" style="padding:72px 0 40px" wrap_style="{COL}gap:20px"]
<span class="lab mut">Careers</span><h1 class="h1">Work behind the <span class="em">lens.</span></h1><p class="lead">We hire for congress operations, production, registration and sponsorship in Alexandria and Dubai.</p>
[/pl_section]''',
  '[pl_section class="" style="padding:24px 0 56px"][pl_careers][/pl_section]',
  f'''[pl_section class="sand sec" id="cv" wrap_style="max-width:860px;{COL}gap:20px"]
<h2 class="h2 m-title" style="font-size:40px">Send your CV</h2>[pl_form name="careers"]
[/pl_section]''',
 ]},
 'contact': {"title": "Contact", "sections": [
  f'''[pl_section class="" style="padding:72px 0 40px" wrap_style="{COL}gap:20px"]
<span class="lab mut">Contact</span><h1 class="h1">Talk to our <span class="em">team.</span></h1><p class="lead">Alexandria and Dubai. Tell us about your congress, your sponsorship or your registration — the right office answers.</p>
[/pl_section]''',
  f'''[pl_section class="" style="padding:0 0 72px" wrap_class="ct-grid" wrap_style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:40px;align-items:start"]
<div style="grid-column:span 7" class="ct-f">[pl_form name="contact"]</div><div style="grid-column:span 5;{COL}gap:28px" class="ct-o">[pl_offices]</div>
[/pl_section]''',
 ]},
 'privacy': {"title": "Privacy", "privacy": True, "sections": [
  f'''[pl_section class="" style="padding:72px 0 96px" wrap_style="max-width:860px;{COL}gap:18px"]
<span class="lab mut">Privacy</span><h1 class="h1" style="font-size:64px">Privacy policy</h1><p class="lead">Our full privacy policy is being prepared. It will cover the contact and careers forms, how long we keep CVs, and cookies.</p>[pl_sample label="Text TBC"]<p class="mut" style="font-size:14px">[pl_credits]</p>
[/pl_section]''',
 ]},
}

# Rank Math title + meta description per page (Step 5.3). Unique, <= 60 / <= 155 chars, no typed numbers.
SEO = {
 'home': ("PL Events — Medical congresses from Alexandria & Dubai",
          "PL Events plans and runs medical congresses for scientific societies from Alexandria and Dubai: the science, the sponsors, the stage and the travel."),
 'events': ("Events — Congresses, symposia & meetings | PL Events",
            "Live, upcoming and past PL Events congresses, symposia and meetings, with dates, cities, venues and registration. Filter by specialty, country or year."),
 'about': ("About PL Events — Head office Alexandria, office in Dubai",
           "How PL Events works for scientific societies: every congress on the map, the societies that trust us, and our teams in Alexandria and Dubai."),
 'services': ("Services — Congress planning & sponsorship | PL Events",
              "Scientific programmes, wetlabs and live surgery, sponsorship and exhibitions, stage and AV, registration and travel — for congresses and webinars."),
 'careers': ("Careers at PL Events — Jobs in Alexandria & Dubai",
             "Work behind the lens: PL Events hires for congress operations, production, registration and sponsorship in Alexandria and Dubai. Send us your CV."),
 'contact': ("Contact PL Events — Alexandria & Dubai offices",
             "Request a congress proposal or ask about sponsorship or registration. Write to the PL Events team in Alexandria or Dubai — the right office answers."),
 'privacy': ("Privacy policy | PL Events",
             "How PL Events handles what you send through the contact and careers forms. The full privacy policy is being prepared."),
}
for k, (t, d) in SEO.items():
    assert len(t) <= 60 and len(d) <= 155, (k, len(t), len(d))
assert len({d for _, d in SEO.values()}) == len(SEO) and len({t for t, _ in SEO.values()}) == len(SEO)

os.makedirs(OUT, exist_ok=True)
for slug, p in PAGES.items():
    tpl = {
        "version": "0.4", "title": "PL — " + p["title"], "type": "page",
        "page_settings": {"template": "elementor_header_footer", "hide_title": "yes"},
        "content": [section(slug, i, code) for i, code in enumerate(p["sections"])],
        "pl": {"slug": slug, "title": p["title"], "front": bool(p.get("front")), "privacy": bool(p.get("privacy")),
               "seo": {"title": SEO[slug][0], "description": SEO[slug][1]},
               "_note": "Read by plt_provision_pages(); Elementor ignores this key."},
    }
    with open(os.path.join(OUT, f'pl-{slug}.json'), 'w') as f:
        json.dump(tpl, f, ensure_ascii=False, indent=1)
    print(f'elementor/pl-{slug}.json  {len(p["sections"])} sections')
