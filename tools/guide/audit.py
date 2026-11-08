#!/usr/bin/env python3
"""Guide audit (round 7): every email / phone number SHOWN in the guide must come from the seed.

Reads (OCR, Tesseract) every screenshot + pairing shot, one video frame every 2 s, and the caption
files (.vtt). Emails and phone numbers found are checked against data/pl-data.json:
  - seed office/event emails and phones: allowed everywhere;
  - test addresses (@example.com / .invalid): allowed only in the forms + newsletter tasks;
  - the few obviously fake demo numbers below: allowed only in the tasks that type a number.
Blurred emails can't be read by OCR, which is the point of the blur.
  python3 tools/guide/audit.py [out_dir]     → prints a report, exit 1 on anything unexpected
"""
import json, os, re, subprocess, sys, tempfile, glob

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(ROOT, 'tools', 'guide', 'out')
seed = json.load(open(os.path.join(ROOT, 'data', 'pl-data.json')))
digits = lambda s: re.sub(r'\D', '', s)

SEED_EMAILS = {e.lower() for o in seed.get('offices', []) for e in o.get('emails', [])} | {str(e.get('email', '')).lower() for e in seed.get('events', []) if e.get('email')}
SEED_PHONES = set()
for x in seed.get('offices', []) + seed.get('events', []):
    for p in x.get('phones', []) or []:
        n = digits(p['number'] if isinstance(p, dict) else p)
        if n: SEED_PHONES.add(n[-9:])  # compare on the last 9 digits (with/without code and leading 0)
TEST_TASKS = ('forms-', 'nl-', 'lost-password', 'login', 'lockout', 'change-password')
DEMO_NUMBERS = {'1000000000': ('event-wa-inquiry', 'forms-', 'pair-wa-inquiry'), '1020000000': ('forms-',), '71000000': ('event-phone',)}

EMAIL = re.compile(r'[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}')
PHONE = re.compile(r'(?:\+|00)?\d[\d \t\-().]{7,}\d')  # one line only: OCR joins columns, never lines

def ocr(path):
    # 2× upscale first: Tesseract misreads 13–14 px admin text at 1×.
    from PIL import Image
    im = Image.open(path).convert('L'); im = im.resize((im.width * 2, im.height * 2), Image.LANCZOS)
    with tempfile.NamedTemporaryFile(suffix='.png') as t:
        im.save(t.name)
        r = subprocess.run(['tesseract', t.name, '-', '--psm', '11', '-l', 'eng'], capture_output=True, text=True)
    return r.stdout

SEED_FULL = set()
for x in seed.get('offices', []) + seed.get('events', []):
    for p in x.get('phones', []) or []:
        SEED_FULL.add(digits(p['number'] if isinstance(p, dict) else p))

def near_seed(d):
    """A seed number read by OCR: whole or partly typed (a run of ≥ 6 digits inside a seed number,
    e.g. "102555900" while 1025559003 is being typed), with at most one misread digit."""
    d = d.lstrip('0') if not d.startswith('00') else d[2:]
    if len(d) < 6:
        return False
    for full in SEED_FULL:
        for cand in (full, full[-10:], full[-9:]):
            for i in range(0, max(1, len(cand) - len(d) + 1)):
                part = cand[i:i + len(d)]
                if len(part) == len(d) and sum(a != b for a, b in zip(part, d)) <= 1:
                    return True
    return False

def task_of(name):
    return os.path.splitext(os.path.basename(name))[0]

def check(text, where):
    bad = []
    for e in set(EMAIL.findall(text)):
        el = e.lower().strip('.')
        if el in SEED_EMAILS: continue
        if (el.endswith('@example.com') or el.endswith('.invalid')) and where.startswith(TEST_TASKS): continue
        bad.append('email ' + e)
    text = re.sub(r'\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:[+-]\d{2}:?\d{2})?)?', ' ', text)  # dates / times
    text = re.sub(r'\b\d{1,2}:\d{2}\s?(?:am|pm)?', ' ', text, flags=re.I)
    for p in set(PHONE.findall(text)):
        d = digits(p)
        if len(d) < 8 or len(d) > 15: continue
        if d[-9:] in SEED_PHONES or near_seed(d): continue  # exact, partly typed, or one misread digit
        demo = next((k for k in DEMO_NUMBERS if d.endswith(k)), None)
        if demo and where.startswith(DEMO_NUMBERS[demo]): continue
        if re.fullmatch(r'20\d{6}', d) or re.fullmatch(r'\d{4}\d{2}\d{2}', d): continue  # dates like 20270131 in fields
        bad.append('number ' + p.strip())
    return bad

report, nfiles = [], 0
# pairs/*.png are made from shots/pair-*-admin.png + -site.png, which are read here at full size
for f in sorted(glob.glob(os.path.join(OUT, 'shots', '*.png'))):
    nfiles += 1
    b = check(ocr(f), task_of(f))
    if b: report.append((os.path.relpath(f, OUT), b))
for f in sorted(glob.glob(os.path.join(OUT, 'video', '*.vtt'))):
    nfiles += 1
    b = check(open(f).read(), task_of(f))
    if b: report.append((os.path.relpath(f, OUT), b))
nframes = 0
with tempfile.TemporaryDirectory() as tmp:
    for f in sorted(glob.glob(os.path.join(OUT, 'video', '*.mp4'))):
        t = task_of(f)
        subprocess.run(['ffmpeg', '-loglevel', 'error', '-i', f, '-vf', 'fps=0.5', os.path.join(tmp, t + '-%03d.png')], check=True)
        found = set()
        for fr in sorted(glob.glob(os.path.join(tmp, t + '-*.png'))):
            nframes += 1
            found |= set(check(ocr(fr), t))
            os.unlink(fr)
        if found: report.append((os.path.relpath(f, OUT), sorted(found)))

# hits checked by eye and recorded with the reason (tools/guide/audit-reviewed.json) are listed, not failed
rev = json.load(open(os.path.join(ROOT, 'tools', 'guide', 'audit-reviewed.json')))
reviewed, left = [], []
for f, b in report:
    ok = [x for x in b if x in rev.get(f, {})]
    if ok: reviewed.append((f, ok))
    rest = [x for x in b if x not in rev.get(f, {})]
    if rest: left.append((f, rest))
report = left
print(f'audit: {nfiles} pictures/caption files + {nframes} video frames read (OCR)')
for f, b in reviewed: print('👁 reviewed', f, '·', '; '.join(x + ' = ' + rev[f][x] for x in b))
for f, b in report: print('❌', f, '·', ', '.join(b))
print('✅ nothing unexpected' if not report else f'{len(report)} file(s) to re-record')
sys.exit(1 if report else 0)
