#!/usr/bin/env python3
"""Builds pl-events-core/data/geo.json (countries + cities) and copies flag SVGs.
Inputs (downloaded by hand into a scratch dir, see docs/Step-4.1):
  countries.json   mledoze/countries (ODbL)
  cities15000.txt  GeoNames cities ≥ 15,000 people (CC BY 4.0)
  flags/           flag-icons 4x3 SVGs (MIT)
Usage: build-geo.py <scratch_dir> <repo_dir>
Region: Europe, Africa, Middle East (Western Asia + Iran)."""
import json, sys, os, shutil
src, repo = sys.argv[1], sys.argv[2]
out_dir = os.path.join(repo, 'wp-content/plugins/pl-events-core')
seed = json.load(open(os.path.join(repo, 'data/pl-data.json')))['geo']
C = json.load(open(os.path.join(src, 'countries.json')))
keep = {}
for c in C:
    if c['region'] in ('Europe', 'Africa') or c.get('subregion') == 'Western Asia' or c['cca2'] == 'IR':
        keep[c['cca2']] = c
countries, cities, capital_tz = {}, {}, {}
for line in open(os.path.join(src, 'cities15000.txt'), encoding='utf-8'):
    f = line.rstrip('\n').split('\t')
    cc = f[8]
    if cc not in keep: continue
    name, ascii_, lat, lon, fcode, tz = f[1], f[2], float(f[4]), float(f[5]), f[7], f[17]
    if fcode == 'PPLC': capital_tz[cc] = tz
    d = cities.setdefault(cc, {})
    for n in {name, ascii_}:
        if n and n not in d: d[n] = [round(lon, 2), round(lat, 2)]
for cc, c in keep.items():
    idd = c.get('idd') or {}
    root, suf = idd.get('root', ''), idd.get('suffixes') or []
    dial = root + (suf[0] if len(suf) == 1 else '')
    lat, lng = c['latlng']
    countries[cc] = {'name': c['name']['common'], 'tz': capital_tz.get(cc, ''), 'centre': [round(lng, 1), round(lat, 1)], 'dial': dial}
# The seed data (Step 3.4b) wins: names like "UAE" and hand-checked centres/tz.
for cc, s in seed['countries'].items():
    countries.setdefault(cc, {}).update(s)
seed_city_cc = {'Alexandria':'EG','New Alamein':'EG','Luxor':'EG','Cairo':'EG','Hurghada':'EG','Damanhour':'EG','Dubai':'AE','Abu Dhabi':'AE','Jeddah':'SA','Riyadh':'SA','Tunis':'TN','Baghdad':'IQ','Amman':'JO','Beirut':'LB','Casablanca':'MA','Kuwait City':'KW','Muscat':'OM'}
for name, ll in seed['cities'].items():
    cities.setdefault(seed_city_cc[name], {})[name] = ll
geo = {'_note': 'Built by tools/build-geo.py. Do not edit by hand.', 'credits': seed['credits'],
       'countries': dict(sorted(countries.items(), key=lambda kv: kv[1]['name'])), 'cities': cities}
os.makedirs(os.path.join(out_dir, 'data'), exist_ok=True)
for k in ('countries', 'cities'):
    json.dump({'credits': geo['credits'], k: geo[k]}, open(os.path.join(out_dir, 'data/%s.json' % k), 'w'), ensure_ascii=False, separators=(',', ':'))
fd = os.path.join(out_dir, 'assets/flags'); os.makedirs(fd, exist_ok=True)
n = 0
for cc in countries:
    p = os.path.join(src, 'flags', cc.lower() + '.svg')
    if os.path.exists(p): shutil.copy(p, fd); n += 1
print(len(countries), 'countries', sum(len(v) for v in cities.values()), 'city names', n, 'flags')
