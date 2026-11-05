#!/usr/bin/env python3
"""Launch DoD for 5.3 / 6.3: "no broken old URLs remain".
Usage: python3 deploy/launch/check-redirects.py https://plevents.org [deploy/launch/redirects.csv]
For every row with a real old_path and status 301: old → one 301 hop → new_path, and new_path → 200.
Read-only (HEAD/GET requests only). Rows whose old_path is TBC or starts with # are skipped and counted."""
import csv, sys, urllib.request, urllib.error

base = sys.argv[1].rstrip('/')
path = sys.argv[2] if len(sys.argv) > 2 else __file__.replace('check-redirects.py', 'redirects.csv')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None

opener = urllib.request.build_opener(NoRedirect)

def get(url):
    try:
        r = opener.open(urllib.request.Request(url, method='GET', headers={'User-Agent': 'pl-redirect-check'}), timeout=20)
        return r.status, r.headers.get('Location', '')
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location', '')

ok = bad = skipped = 0
with open(path) as f:
    for row in csv.DictReader(line for line in f if not line.startswith('#')):
        old, new, status = row['old_path'].strip(), row['new_path'].strip(), row['status'].strip()
        if old in ('', 'TBC') or '{' in new:
            skipped += 1
            continue
        if status == 'keep':
            code, _ = get(base + old)
            good = code == 200
        else:
            code, loc = get(base + old)
            target = loc.replace(base, '') if loc else ''
            code2, _ = get(base + new)
            good = code == 301 and target.rstrip('/') == new.rstrip('/') and code2 == 200
        ok += good
        bad += not good
        print(('OK   ' if good else 'FAIL ') + f'{old} → {new}  [{code}]')
print(f'\n{ok} ok, {bad} failed, {skipped} skipped (TBC)')
sys.exit(1 if bad else 0)
