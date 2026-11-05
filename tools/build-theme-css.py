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
/* Metric-matched fallback: while Montserrat loads on a slow line, Arial takes the same space,
   so the swap does not re-wrap lines (CLS). Values: Montserrat vs Arial (as used by Next.js). */
@font-face{font-family:"Montserrat Fallback";src:local("Arial"),local("Helvetica"),local("Liberation Sans");ascent-override:84.95%;descent-override:22.03%;line-gap-override:0%;size-adjust:113.39%}
"""
def clean(css):
    out = []
    for line in css.splitlines():
        if re.match(r'\s*\.pc(-btn)?[\s{.,:\[]', line):
            continue
        line = re.sub(r'\.pc-btn\{[^}]*\}', '', line)
        out.append(line)
    return '\n'.join(out)
def fallback(css):
    n = css.count('"Montserrat","Segoe UI"')
    assert n >= 1, 'font stack not found'
    return css.replace('"Montserrat","Segoe UI"', '"Montserrat","Montserrat Fallback","Segoe UI"')

def wp_scope(css):
    # The prototype scoped tablet/mobile grid collapses to <main>; Elementor Full Width pages
    # have no <main>, so the same rules also apply inside .elementor and the event page.
    css = css.replace(' main [style*="grid-template-columns:repeat(12"]', ' :is(main,.elementor,.pl-event) [style*="grid-template-columns:repeat(12"]')
    css = css.replace(' main [style*="grid-column:8"]', ' :is(main,.elementor,.pl-event) [style*="grid-column:8"]')
    return css
parts = [fonts, '/* ---- prototype base.css ---- */', fallback(open(os.path.join(src, 'base.css')).read()),
         '/* ---- prototype proto.css ---- */', wp_scope(clean(open(os.path.join(src, 'proto.css')).read())),
         open(os.path.join(theme, 'assets/css/src/wp.css')).read()]
css = '\n'.join(parts)
assert '.pc{' not in css and 'pc-btn' not in css, 'prototype controls left in'
assert ':is(main,.elementor,.pl-event) [style*="grid-template-columns:repeat(12"]' in css, 'main-scoped grid rule not widened'
open(os.path.join(theme, 'assets/css/pl.css'), 'w').write(css)
print('pl.css', len(css), 'bytes')
