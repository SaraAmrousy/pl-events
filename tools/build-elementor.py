#!/usr/bin/env python3
"""Builds the Elementor page templates in /elementor (Step 4.4).

Each file is an Elementor Free template export ("Templates › Import" accepts it) and is also
what `plt_provision_pages()` writes into the pages on staging. Every section = one full-width
container holding one Shortcode widget: [pl_section] + the prototype copy (reference/prototype/
src/views.js) + the plugin's [pl_*] shortcodes. Layout only; all data comes from the plugin.
Run after changing a page here; then deploy the theme and re-run provisioning with force.
"""
import hashlib, json, os
# [pl_url] inside an attribute uses single quotes: href="[pl_url to='contact']". With double quotes
# WordPress leaves the shortcode unprocessed (found in 6.1 QA: four 404 links).

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


# ---------------------------------------------------------------- Page texts (6.1b round 7)
# Every fixed sentence on the pages is a field in PL Website › Page texts. ONE source: this table.
# It writes wp-content/plugins/pl-events-core/data/page-texts.json (the plugin's registry: defaults,
# labels, limits) and the pages below print [pl_t k="…"] where the words used to be.
# kind: label (eyebrow) · h1 · h2 · lead · text · button · caption.  max = soft limit (yellow note).
# hl = the highlighted word (italic, blue), printed as <span class="em"> after the text.
# {since} = "June 2024", worked out from the events (kept live).
TEXTS = []
def T(key, page, label, where, kind, text, hl='', mx=None):
    lim = {'label': 32, 'h1': 34, 'h2': 48, 'lead': 190, 'text': 160, 'button': 30, 'caption': 60}[kind]
    TEXTS.append({'key': key, 'page': page, 'label': label, 'where': where, 'kind': kind, 'text': text, 'hl': hl, 'max': mx or lim})
    return f'[pl_t k="{key}"]'

CONTACT = "[pl_url to='contact' topic='proposal']"
COL = 'display:flex;flex-direction:column;'

PAGES = {
 'home': {"title": "Home", "front": True, "sections": [
  '[pl_hero]',
  '[pl_upcoming layout="row" tabs="all,live,upcoming,past" default="all" limit="0"]',
  f'''[pl_section class="sec" style="border-top:1.5px solid var(--line);overflow:hidden;position:relative" wrap_style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:center"]
<div class="d-only" style="grid-column:span 5;margin-left:-120px" aria-hidden="true"><svg viewBox="0 0 520 330" width="520" height="330" style="overflow:visible"><text id="plOutline" x="0" y="320" fill="#FAFAF7" stroke="#0A0D18" stroke-width="4" style="font:800 420px Montserrat,sans-serif;letter-spacing:-.02em;paint-order:stroke fill">PL</text></svg></div>
<div style="grid-column:span 7;{COL}gap:20px" class="nm">[pl_stats layout="line"][pl_logos type="society"]<a href="[pl_url to='about' anchor='story']" class="btn bg" style="align-self:flex-start">{T('home_map_btn','home','Map link under the numbers','Home › under “13 congresses. 7 cities…”','button','Explore the record on the map →')}</a></div>
[/pl_section]''',
  f'''[pl_section class="sand cutb" style="padding:96px 0 136px" wrap_style="{COL}gap:32px"]
<div class="sh"><h2 class="h2 m-title">{T('home_svc_h2','home','Services heading','Home › “Behind the lens.” section (services)','h2','Behind the','lens.')}</h2><p class="mut" style="font-size:17px;max-width:400px">{T('home_svc_text','home','Services intro','Home › under “Behind the lens.”','text','One team runs the science, the sponsors, the stage and the travel.',mx=110)}</p></div>
[pl_services layout="numbered"]
<a href="[pl_url to='services']" class="btn bs" style="align-self:flex-start">{T('home_svc_btn','home','Services button','Home › services section, button','button','All services')}</a>
[/pl_section]''',
  f'''[pl_section class="ink" id="cta" style="padding:112px 0 120px" wrap_class="ctag" wrap_style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0"]
<div class="ctacol" style="{COL}gap:18px;padding:8px 0"><span class="lab mut">{T('home_soc_label','home','Societies — small label','Home › dark band, left','label','For scientific societies')}</span><h2 class="h2 m-title h2l">{T('home_soc_h2','home','Societies — heading','Home › dark band, left','h2','Planning a','congress?')}</h2><p style="font-size:18px;line-height:1.55;color:var(--oim);max-width:46ch">{T('home_soc_text','home','Societies — text','Home › dark band, left','text','Our Alexandria and Dubai teams plan the science, the sponsors, the stage and the travel — one team from programme to gala dinner.')}</p><div class="row"><a href="{CONTACT}" class="btn bw">{T('home_soc_btn','home','Societies — button','Home › dark band, left','button','Request a proposal')}</a></div></div>
<div class="ctar" style="border-left:1.5px solid #2A3149;padding-left:56px"><div class="ctacol" style="{COL}gap:18px;padding:8px 0"><span class="lab mut">{T('home_spo_label','home','Sponsors — small label','Home › dark band, right','label','For sponsors & exhibitors')}</span><h2 class="h2 m-title h2l">{T('home_spo_h2','home','Sponsors — heading','Home › dark band, right','h2','Put your brand','in focus.')}</h2>[pl_stats layout="sponsor_line"]<div class="row"><a href="[pl_url to='contact' topic='sponsor']" class="btn bso">{T('home_spo_btn','home','Sponsors — button','Home › dark band, right','button','Sponsor a congress')}</a><a href="[pl_url to='services' anchor='sponsorship']" class="btn bg">{T('home_spo_link','home','Sponsors — link','Home › dark band, right','button','See sponsorship')}</a></div></div></div>
[/pl_section]''',
 ]},
 'events': {"title": "Events", "sections": [
  '[pl_calendar default="all" search="1" filters="specialty,country,year,type" views="grid,list"]',
  f'''[pl_section class="ink cutt" style="padding:120px 0 96px" wrap_class="sh"]
<h2 class="h2 m-title" style="max-width:720px">{T('events_cta_h2','events','Closing band — heading','Events › dark band at the bottom','h2','Running a congress? See how we can','help.',mx=60)}</h2><a href="{CONTACT}" class="btn bw">{T('events_cta_btn','events','Closing band — button','Events › dark band at the bottom','button','Request a proposal')}</a>
[/pl_section]''',
 ]},
 'about': {"title": "About", "sections": [
  f'''[pl_section class="" style="padding:88px 0 72px" wrap_style="{COL}gap:36px"]
<span class="lab mut">{T('about_label','about','Small label above the title','About › top','label','About PL Events')}</span><h1 class="h1 h1xl focus-set">From <span class="hl-city">Alexandria</span> to [pl_stats layout="number" field="countries"] countries.</h1>
<div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" class="ab-int"><p class="lead" style="grid-column:span 6">{T('about_intro','about','Intro (left)','About › under the title, left','lead','PL Events plans and runs medical congresses for scientific societies, from a head office in Alexandria and a regional office in Dubai. Most of our work is ophthalmology; vascular, lymphology and nephrology are growing.',mx=230)}</p><p class="mut" style="grid-column:8 / span 5;font-size:17px">{T('about_intro2','about','Intro (right) — {since} = first event month','About › under the title, right','text','One team handles the science, the sponsors, the stage and the travel — and every congress since {since} is on this site, with its date, city and registration.',mx=190)}</p></div>
<figure style="margin:0">[pl_media src="assets/hall-wide.jpg" alt="Delegates seated in a congress hall set up by PL Events" class="photo rack" frame="wide" frame_style="border-radius:10px" sizes="(max-width: 767px) 175vw, 1200px"]<figcaption class="lab mut" style="margin-top:10px">{T('about_caption','about','Photo caption','About › under the wide photo','caption','From PL’s portfolio')}</figcaption></figure>
[/pl_section]''',
  '[pl_story layout="sticky explore" aspect="1.3"]',
  '[pl_section class="sec"][pl_company_figures layout="big"][/pl_section]',
  f'''[pl_section class="sand sec" style="padding:88px 0" wrap_style="{COL}gap:24px"]
<h2 class="h2 m-title" style="font-size:40px">{T('about_soc_h2','about','Societies heading','About › above the society logos','h2','Societies that trust','us.')}</h2>[pl_logos type="society"]
[/pl_section]''',
  '[pl_offices layout="split"]',
  f'''[pl_section class="sec" style="padding:104px 0 112px;position:relative;overflow:hidden;border-top:1.5px solid var(--line)" wrap="0"]
<div aria-hidden="true" class="out pl-out d-only" style="position:absolute;right:40px;top:50%;transform:translateY(-50%);font-size:340px;line-height:.8;-webkit-text-stroke-color:#D9D5CC">PL</div>
<div class="wrap" style="position:relative;{COL}gap:22px"><h2 class="h2 h2xl h2xxl">{T('about_cta_h2','about','Closing — heading','About › last section','h2','Two cities.','One team.',mx=30)}</h2><p class="mut" style="font-size:18px;max-width:560px">{T('about_cta_text','about','Closing — text','About › last section','text','Tell us about your congress — the Alexandria or Dubai team answers.',mx=110)}</p><div class="row"><a href="{CONTACT}" class="btn bp">{T('about_cta_btn','about','Closing — button','About › last section','button','Request a proposal')}</a>[pl_company field="portfolio_link" class="btn bs" label_key="{T('about_portfolio_btn','about','Closing — portfolio button (the size is added by itself)','About › last section','button','Download our portfolio')[9:-2]}"]</div></div>
[/pl_section]''',
 ]},
 'services': {"title": "Services", "sections": [
  f'''[pl_section class="" style="padding:72px 0 24px" wrap_style="{COL}gap:20px"]
<span class="lab mut">{T('svc_label','services','Small label above the title','Services › top','label','Services')}</span><h1 class="h1">{T('svc_h1','services','Page title (H1)','Services › top','h1','Behind the','lens.')}</h1><p class="lead">{T('svc_lead','services','Intro','Services › under the title','lead','One team runs the science, the sponsors, the stage and the travel — for congresses, symposia, wetlabs and webinars.')}</p>
[/pl_section]''',
  '[pl_section class="" style="padding:24px 0 72px"][pl_services layout="zigzag"][/pl_section]',
  f'''[pl_section class="ink sec" id="sponsorship" wrap_style="{COL}gap:24px"]
<span class="lab mut">{T('svc_spo_label','services','Sponsorship — small label','Services › dark sponsorship band','label','Sponsorship')}</span><h2 class="h2 m-title">{T('svc_spo_h2','services','Sponsorship — heading','Services › dark sponsorship band','h2','Put your brand','in focus.')}</h2><p class="lead" style="color:var(--oim)">[pl_stats layout="sponsor_line" tag="span"] {T('svc_spo_text','services','Sponsorship — text before the logos (after the live numbers)','Services › dark sponsorship band','text','Companies that have sponsored PL congresses:',mx=80)}</p>[pl_logos type="sponsor"]<div class="row"><a href="[pl_url to='contact' topic='sponsor']" class="btn bw">{T('svc_spo_btn','services','Sponsorship — button','Services › dark sponsorship band','button','Sponsor a congress')}</a></div>
[/pl_section]''',
  f'''[pl_section class="sec" wrap_class="sh"]
<h2 class="h2 m-title" style="max-width:720px">{T('svc_cta_h2','services','Closing — heading','Services › last section','h2','Planning a congress? Talk to our','team.',mx=60)}</h2><a href="{CONTACT}" class="btn bp">{T('svc_cta_btn','services','Closing — button','Services › last section','button','Request a proposal')}</a>
[/pl_section]''',
 ]},
 'careers': {"title": "Careers", "sections": [
  f'''[pl_section class="" style="padding:72px 0 40px" wrap_style="{COL}gap:20px"]
<span class="lab mut">{T('car_label','careers','Small label above the title','Careers › top','label','Careers')}</span><h1 class="h1">{T('car_h1','careers','Page title (H1)','Careers › top','h1','Work behind the','lens.')}</h1><p class="lead">{T('car_lead','careers','Intro','Careers › under the title','lead','We hire for congress operations, production, registration and sponsorship in Alexandria and Dubai.')}</p>
[/pl_section]''',
  '[pl_section class="" style="padding:24px 0 56px"][pl_careers][/pl_section]',
  f'''[pl_section class="sand sec" id="cv" wrap_style="max-width:860px;{COL}gap:20px"]
<h2 class="h2 m-title" style="font-size:40px">{T('car_cv_h2','careers','CV form heading','Careers › above the form','h2','Send your CV')}</h2>[pl_form name="careers"]
[/pl_section]''',
 ]},
 'contact': {"title": "Contact", "sections": [
  f'''[pl_section class="" style="padding:72px 0 40px" wrap_style="{COL}gap:20px"]
<span class="lab mut">{T('con_label','contact','Small label above the title','Contact › top','label','Contact')}</span><h1 class="h1">{T('con_h1','contact','Page title (H1)','Contact › top','h1','Talk to our','team.')}</h1><p class="lead">{T('con_lead','contact','Intro','Contact › under the title','lead','Alexandria and Dubai. Tell us about your congress, your sponsorship or your registration — the right office answers.')}</p>
[/pl_section]''',
  f'''[pl_section class="" style="padding:0 0 72px" wrap_class="ct-grid" wrap_style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:40px;align-items:start"]
<div style="grid-column:span 7" class="ct-f">[pl_form name="contact"]</div><div style="grid-column:span 5;{COL}gap:28px" class="ct-o">[pl_offices]</div>
[/pl_section]''',
 ]},
 'privacy': {"title": "Privacy", "privacy": True, "sections": [
  f'''[pl_section class="" style="padding:72px 0 96px" wrap_style="max-width:860px;{COL}gap:18px"]
<span class="lab mut">{T('pri_label','privacy','Small label above the title','Privacy › top','label','Privacy')}</span><h1 class="h1" style="font-size:64px">{T('pri_h1','privacy','Page title (H1)','Privacy › top','h1','Privacy policy')}</h1><p class="lead">{T('pri_lead','privacy','Intro','Privacy › under the title','lead','Our full privacy policy is being prepared. It will cover the contact and careers forms, how long we keep CVs, and cookies.')}</p>[pl_sample label="Text TBC"]<p class="mut" style="font-size:14px">[pl_credits]</p>
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

# Page texts registry for pl-events-core (6.1b round 7): defaults = the text the pages had before.
assert len({t['key'] for t in TEXTS}) == len(TEXTS), 'duplicate page-text key'
REG = os.path.join(ROOT, 'wp-content', 'plugins', 'pl-events-core', 'data', 'page-texts.json')
with open(REG, 'w') as f:
    json.dump({'_note': 'Built by tools/build-elementor.py — edit there. Defaults = the original page text.', 'pages': ['home', 'events', 'services', 'about', 'careers', 'contact', 'privacy'], 'texts': TEXTS}, f, ensure_ascii=False, indent=1)
print(f'page texts: {len(TEXTS)} fields → {os.path.relpath(REG, ROOT)}')
