#!/usr/bin/env python3
"""Builds hello-pl-child/assets/css/pl.css from the approved prototype CSS (Step 4.2).
pl.css = @font-face (self-hosted Montserrat) + base.css + proto.css (minus Prototype-controls
rules) + assets/css/src/wp.css. Run after any change to the prototype CSS or wp.css."""
import os, re
repo = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
src = os.path.join(repo, 'reference/prototype/src')
theme = os.path.join(repo, 'wp-content/themes/hello-pl-child')
fonts = """/* Montserrat (SIL OFL 1.1), self-hosted, latin subset */
@font-face{font-family:"Montserrat";font-style:normal;font-weight:400 800;font-display:swap;src:url(../fonts/montserrat-normal-latin.woff2) format("woff2")}
@font-face{font-family:"Montserrat";font-style:italic;font-weight:500;font-display:swap;src:url(../fonts/montserrat-italic-latin.woff2) format("woff2")}
"""
def clean(css):
    out = []
    for line in css.splitlines():
        if re.match(r'\s*\.pc(-btn)?[\s{.,:\[]', line):
            continue
        line = re.sub(r'\.pc-btn\{[^}]*\}', '', line)
        out.append(line)
    return '\n'.join(out)
parts = [fonts, '/* ---- prototype base.css ---- */', open(os.path.join(src, 'base.css')).read(),
         '/* ---- prototype proto.css ---- */', clean(open(os.path.join(src, 'proto.css')).read()),
         open(os.path.join(theme, 'assets/css/src/wp.css')).read()]
css = '\n'.join(parts)
assert '.pc{' not in css and 'pc-btn' not in css, 'prototype controls left in'
open(os.path.join(theme, 'assets/css/pl.css'), 'w').write(css)
print('pl.css', len(css), 'bytes')
