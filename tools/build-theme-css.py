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

# --- 6.1b: drop prototype-only components the site never renders (checked: their selectors match no
# element on any page, width, motion context or UI state), then minify. Source stays readable in
# reference/prototype/src + assets/css/src/wp.css.
DEAD = [r'\.save\b', r'\.modal\b', r'#helpPanel', r'\.sp-tiles', r'\.sp-h\b', r'#shell\b', r'\.svcstack',
        r'\.svc-(photos|steps|frame|ph|count|step)\b', r'^\.m\s', r'\.story(?![\w-])', r'^\.form(?![\w-])',
        r'^\.form\s', r'^textarea\.inp', r'^\.note\b']
def dead(selector_list):
    sels = [x.strip() for x in selector_list.split(',') if x.strip()]
    return sels and all(any(re.search(d, x) for d in DEAD) for x in sels)
def prune(css):
    css = re.sub(r'/\*[\s\S]*?\*/', '', css)
    out, n = [], [0]
    def rule(m):
        if dead(m.group(1)):
            n[0] += 1
            return ''
        return m.group(0)
    css = re.sub(r'([^{}@;]+)\{([^{}]*)\}', rule, css)
    css = re.sub(r'@media[^{]+\{\s*\}', '', css)  # media blocks left empty
    return css, n[0]
def minify(css):
    css = re.sub(r'\s+', ' ', css)
    css = re.sub(r'\s*([{};,>])\s*', r'\1', css)
    css = re.sub(r':\s+', ':', css)  # safe: selectors keep their own spaces around descendant combinators
    css = css.replace(';}', '}')
    return css.strip() + '\n'
css, dropped = prune(css)
before_min = len(css)
css = minify(css)
assert '.pc{' not in css and 'pc-btn' not in css, 'prototype controls left in'
assert ':is(main,.elementor,.pl-event) [style*="grid-template-columns:repeat(12"]' in css, 'main-scoped grid rule not widened'
open(os.path.join(theme, 'assets/css/pl.css'), 'w').write(css)
print('pl.css', len(css), 'bytes (dropped', dropped, 'dead prototype rules; minified from', before_min, ')')
