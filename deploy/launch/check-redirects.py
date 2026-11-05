#!/usr/bin/env python3
"""Launch DoD for 5.3 / 6.3: "no broken old URLs remain".
Usage: python3 deploy/launch/check-redirects.py https://plevents.org [deploy/launch/redirects.csv]
  301  : old → exactly one 301 hop → new_path, and new_path → 200
  410  : old → 410
  keep : old → 200
  start: tested with a sample path under the prefix (old_path + "pl-redirect-check/")
Read-only GET requests. Rows with old_path TBC are skipped and counted."""
import csv, sys, urllib.request, urllib.error
from urllib.parse import urlsplit

base = sys.argv[1].rstrip('/')
path = sys.argv[2] if len(sys.argv) > 2 else __file__.replace('check-redirects.py', 'redirects.csv')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None

opener = urllib.request.build_opener(NoRedirect)

def get(url):
    try:
        r = opener.open(urllib.request.Request(url, headers={'User-Agent': 'pl-redirect-check'}), timeout=20)
        return r.status, r.headers.get('Location', '')
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location', '')

def rel(loc):
    p = urlsplit(loc)
    out = p.path[len(urlsplit(base).path):] if p.netloc else p.path
    return (out or '/') + ('?' + p.query if p.query else '')

ok = bad = skipped = 0
with open(path) as f:
    for row in csv.DictReader(line for line in f if not line.startswith('#')):
        old, match, new, code = (row[k].strip() for k in ('old_path', 'match', 'new_path', 'code'))
        if old in ('', 'TBC'):
            skipped += 1
            continue
        url = base + old + ('pl-redirect-check/' if match == 'start' else '')
        got, loc = get(url)
        if code == 'keep' or code == '410':
            good = got == (200 if code == 'keep' else 410)
            note = f'[{got}]'
        else:
            target = rel(loc) if loc else ''
            got2, _ = get(base + new) if target else (0, '')
            good = got == 301 and target.rstrip('/') == new.rstrip('/') and got2 == 200
            note = f'[{got} → {target or "-"} {got2 or ""}]'
        ok += good
        bad += not good
        print(('OK   ' if good else 'FAIL ') + f'{old}{"*" if match == "start" else ""} → {new or code}  {note}')
print(f'\n{ok} ok, {bad} failed, {skipped} skipped (TBC)')
sys.exit(1 if bad else 0)
