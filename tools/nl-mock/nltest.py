# Newsletter flow against the sandbox mocks (see README.md). 44 checks. Sandbox only.
import json, subprocess, urllib.request, urllib.parse, re, os, time, sys
M = os.environ.get('PL_NL_MOCK_DIR', os.path.dirname(os.path.abspath(__file__)))  # folder where router.php writes log.jsonl
B = 'http://localhost:8080'
def wp(code):
    return subprocess.run(['php','../.bin/wp','--allow-root','--path=.','eval',code],cwd='/home/user/wp-sandbox/site',capture_output=True,text=True).stdout.strip()
def http(url, data=None, method=None, headers=None, json_body=None):
    h = {'Origin': B} if headers is None else headers
    body = None
    if json_body is not None: body = json.dumps(json_body).encode(); h = dict(h, **{'Content-Type':'application/json'})
    elif data is not None: body = urllib.parse.urlencode(data, doseq=True).encode()
    req = urllib.request.Request(url, data=body, method=method, headers=h)
    try:
        r = urllib.request.urlopen(req); return r.status, r.read().decode()
    except urllib.error.HTTPError as e: return e.code, e.read().decode()
def log():
    try: return [json.loads(l) for l in open(M+'/log.jsonl')]
    except FileNotFoundError: return []
def reset():
    for f in ['log.jsonl','mail.jsonl']:
        try: os.remove(M+'/'+f)
        except FileNotFoundError: pass
def mails():
    try: return [json.loads(l) for l in open(M+'/mail.jsonl')]
    except FileNotFoundError: return []
res = []
def ok(name, cond, extra=''):
    res.append((name, bool(cond))); print(('PASS ' if cond else 'FAIL ') + name + (' — ' + str(extra) if extra and not cond else ''))
def rec(email):
    return json.loads(wp(f'$i=get_posts(["post_type"=>"pl_nl_signup","post_status"=>"private","title"=>"{email}","fields"=>"ids","numberposts"=>1]); $i=$i[0]??0; echo wp_json_encode(["id"=>$i,"st"=>PL_Newsletter::status($i),"c"=>get_post_meta($i,"_pl_nl_consent",true),"out"=>get_post_meta($i,"_pl_nl_out",true),"z"=>get_post_meta($i,"_pl_nl_zoho",true),"m"=>get_post_meta($i,"_pl_nl_mailchimp",true),"todo"=>get_post_meta($i,"_pl_nl_unsub_todo",true)]);') or '{}')
def signup_confirm(email, tags_extra=None):
    reset(); wp('delete_transient("pl_nl_rate_".md5("127.0.0.1")); delete_transient("pl_nl_rate_".md5("::1"));')
    data = {'email': email, 'form': 'footer', 'page': B+'/events/', 'specialty[]': ['ophthalmology'], 'country[]': ['eg']}
    st, body = http(B+'/wp-json/pl/v1/subscribe', data)
    return st, body

wp('foreach(get_posts(["post_type"=>"pl_nl_signup","post_status"=>"any","numberposts"=>-1,"fields"=>"ids"]) as $i) wp_delete_post($i,true); delete_transient("pl_nl_rate_".md5("127.0.0.1")); delete_transient("pl_nl_rate_".md5("::1")); delete_option("pl_nl_zoho_doi"); delete_transient("pl_nl_mc_hook");')
for f in ['mc_hooks.json','mc_compliance','zoho_doi']:
    try: os.remove(M+'/'+f)
    except FileNotFoundError: pass

# 1. Both → one confirmation email, nothing sent to providers yet
E='a.test1@example.org'
st, body = signup_confirm(E)
ok('subscribe REST 200 + state ok', st==200 and json.loads(body).get('state')=='ok', body)
ok('REST does not expose record id', 'id' not in json.loads(body), body)
ms = mails()
ok('exactly one email sent', len(ms)==1, len(ms))
ok('no provider call before confirmation', len(log())==0, log())
r = rec(E); ok('status awaiting confirmation', r['st']=='awaiting confirmation', r)
m = ms[0]; ok('email subject', m['subject']=='Confirm your subscription to PL Events updates', m['subject'])
ok('email has button text', 'Confirm my subscription' in m['message'])
url = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', m['message']).group(1).replace('&#038;','&').replace('&amp;','&')
q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(url).query))
ok('link expires in 7 days', abs(int(q['x']) - time.time() - 7*86400) < 120, q)
# 2. Open link (GET) → page with button, still nothing sent
st, page = http(url, headers={})
ok('GET confirm page 200 with button', st==200 and 'Confirm my subscription' in page and '<form method="post"' in page, re.search(r'<main.*?</main>', page, re.S).group(0)[:600])
ok('GET does not confirm (mail scanners)', len(log())==0 and rec(E)['st']=='awaiting confirmation')
ok('confirm page noindex', 'noindex' in page)
# 3. POST → subscribed in both with tags
st, page = http(url, data={}, headers={})
ok('POST confirm → You’re subscribed', st==200 and 'subscribed' in page and 'Unsubscribe' in page, st)
L = log()
put = [l for l in L if l['m']=='PUT' and '/mailchimp' in l['uri']]
pb = json.loads(put[0]['body']) if put else {}
ok('Mailchimp PUT status=subscribed', pb.get('status')=='subscribed' and pb.get('status_if_new')=='subscribed', pb)
ok('Mailchimp marketing_permissions: Email only, enabled', pb.get('marketing_permissions')==[{'marketing_permission_id':'mp_email','enabled':True}], pb.get('marketing_permissions'))
tg = [l for l in L if '/mailchimp' in l['uri'] and l['uri'].endswith('/tags')]
ok('Mailchimp tags', tg and all(t in tg[0]['body'] for t in ['specialty-ophthalmology','country-eg','source-footer']), tg)
nt = [l for l in L if l['uri'].endswith('/notes')]
ok('Mailchimp consent note has confirmed time', nt and 'confirmed by email 20' in nt[0]['body'], nt)
zs = [l for l in L if 'listsubscribe' in l['uri']]
ok('Zoho listsubscribe once', len(zs)==1, zs)
za = [l for l in L if 'tag/associate' in l['uri']]
ok('Zoho tags associated', len(za)==3, len(za))
r = rec(E); ok('record subscribed + confirmed time', r['st']=='subscribed' and r['c'].get('confirmed') and r['z']=='sent' and r['m']=='sent', r)
ok('still only one email in total', len(mails())==1)
# 4. tampered + expired
bad = url.replace('s='+q['s'], 's='+('0'*len(q['s'])))
st, page = http(bad, headers={}); ok('tampered link → calm page, 200', st==200 and 'isn’t complete' in page and 'sign up again' in page.lower(), st)
st, page = http(bad, data={}, headers={}); ok('tampered POST changes nothing', 'isn’t complete' in page)
exp = wp(f'echo PL_NL_Links::confirm_url({r["id"]}, time()-8*DAY_IN_SECONDS);')
st, page = http(exp, headers={}); ok('expired link → "has expired" + fresh link hint', st==200 and 'has expired' in page and 'fresh link' in page, st)
for w in ['wrong','invalid','error','fail']:
    pass
ok('no blaming words on error pages', not re.search(r'\byou (entered|typed|made)|invalid|wrong\b', page, re.I))

# 5. Mailchimp webhook unsubscribe → Zoho unsubscribed
key = wp('echo PL_NL_Hooks::key();')
st,_ = http(B+'/wp-json/pl/v1/nl-hook/mailchimp?k=nope', data={'type':'unsubscribe','data[email]':E}, headers={})
ok('webhook wrong secret → 404, no change', st==404 and rec(E)['st']=='subscribed', st)
st,_ = http(B+'/wp-json/pl/v1/nl-hook/mailchimp?k='+key, headers={})
ok('Mailchimp URL check (GET) → 200', st==200)
reset()
st,body = http(B+'/wp-json/pl/v1/nl-hook/mailchimp?k='+key, data={'type':'unsubscribe','fired_at':'2026-11-07 10:00:00','data[action]':'unsub','data[email]':E,'data[list_id]':'abc123'}, headers={})
r = rec(E); L = log()
ok('MC webhook → local unsubscribed via mailchimp', r['st']=='unsubscribed' and r['out'].get('via')=='mailchimp', r)
ok('MC webhook → Zoho listunsubscribe, no Mailchimp call', any('listunsubscribe' in l['uri'] for l in L) and not any('/mailchimp' in l['uri'] for l in L), L)
reset(); http(B+'/wp-json/pl/v1/nl-hook/mailchimp?k='+key, data={'type':'unsubscribe','data[email]':E}, headers={})
ok('repeat webhook is a no-op (no loop)', len(log())==0, log())

# 6. Zoho webhook unsubscribe → Mailchimp unsubscribed
E2='b.test2@example.org'
signup_confirm(E2); u2 = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', mails()[0]['message']).group(1).replace('&#038;','&'); http(u2, data={}, headers={})
ok('second person subscribed', rec(E2)['st']=='subscribed')
reset()
st,body = http(B+'/wp-json/pl/v1/nl-hook/zoho?k='+key, json_body={'action':'unsubscribe','contact_email':E2,'listkey':'testlistkey','time':1730970000000}, headers={})
L = log(); r = rec(E2)
ok('Zoho webhook (JSON) → local unsubscribed via zoho', r['st']=='unsubscribed' and r['out'].get('via')=='zoho', (r, body))
pa = [l for l in L if l['m']=='PATCH']
ok('Zoho webhook → Mailchimp PATCH unsubscribed', pa and '"unsubscribed"' in pa[0]['body'] and not any('/zoho/' in l['uri'] for l in L), L)
# Zoho form-encoded bounce (hard) vs soft
E3='c.test3@example.org'
signup_confirm(E3); u3 = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', mails()[0]['message']).group(1).replace('&#038;','&'); http(u3, data={}, headers={})
reset(); http(B+'/wp-json/pl/v1/nl-hook/zoho?k='+key, data={'action':'bounce','bounce_type':'soft','email':E3}, headers={})
ok('Zoho soft bounce ignored', rec(E3)['st']=='subscribed' and len(log())==0)
http(B+'/wp-json/pl/v1/nl-hook/zoho?k='+key, data={'action':'bounce','bounce_type':'hard','email':E3}, headers={})
ok('Zoho hard bounce → bounced + Mailchimp unsubscribed', rec(E3)['st']=='bounced' and any(l['m']=='PATCH' for l in log()))

# 7. Site unsubscribe link → both
E4='d.test4@example.org'
signup_confirm(E4); u4 = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', mails()[0]['message']).group(1).replace('&#038;','&'); st, page = http(u4, data={}, headers={})
un = re.search(r'href="([^"]*pl_nl_act=unsubscribe[^"]*)"', page).group(1).replace('&#038;','&').replace('&amp;','&')
reset(); st, page = http(un, headers={})
ok('unsubscribe GET → button, no change', 'Unsubscribe me' in page and rec(E4)['st']=='subscribed' and not log())
st, page = http(un, data={}, headers={})
L = log()
ok('unsubscribe POST → removed from both', 'You’re unsubscribed' in page and any(l['m']=='PATCH' for l in L) and any('listunsubscribe' in l['uri'] for l in L) and rec(E4)['out'].get('via')=='site', L)
st, page = http(un.replace('s=','s=x'), headers={}); ok('tampered unsubscribe link → calm page', 'isn’t complete' in page and 'write to us' in page)

# 8. Provider down during unsubscribe → retried by cron
E5='e.test5@example.org'
signup_confirm(E5); u5 = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', mails()[0]['message']).group(1).replace('&#038;','&'); http(u5, data={}, headers={})
open(M+'/mode','w').write('down'); http(B+'/wp-json/pl/v1/nl-hook/mailchimp?k='+key, data={'type':'unsubscribe','data[email]':E5}, headers={})
ok('Zoho down → unsubscribe queued', rec(E5)['todo']==['zoho'], rec(E5))
open(M+'/mode','w').write('ok'); reset(); wp('PL_Newsletter::retry();')
ok('cron retry → Zoho unsubscribed, queue empty', any('listunsubscribe' in l['uri'] for l in log()) and rec(E5)['todo']==[], rec(E5))

# 9. Zoho list with signup form on → warning; Mailchimp compliance → pending fallback
open(M+'/zoho_doi','w').write('1'); open(M+'/mc_compliance','w').write('1')
E6='f.test6@example.org'
signup_confirm(E6); u6 = re.search(r'href="([^"]*pl_nl_act=confirm[^"]*)"', mails()[0]['message']).group(1).replace('&#038;','&'); reset(); http(u6, data={}, headers={})
puts = [json.loads(l['body']).get('status') for l in log() if l['m']=='PUT']
ok('MC compliance state → retried as pending (Mailchimp re-confirms)', puts==['subscribed','pending'], puts)
ok('Zoho own-confirmation detected → admin warning flag', wp('echo get_option("pl_nl_zoho_doi") ? "y" : "n";')=='y')
os.remove(M+'/zoho_doi'); os.remove(M+'/mc_compliance')

# 10. Mailchimp webhook connect + status
reset(); r = wp('$p=PL_Newsletter::providers("mailchimp"); $r=reset($p)->connect_webhook(PL_NL_Hooks::url("mailchimp")); echo is_wp_error($r)?$r->get_error_message():"ok"; delete_transient("pl_nl_mc_hook"); echo " ".PL_NL_Hooks::state("mailchimp");')
hb = [json.loads(l['body']) for l in log() if l['m']=='POST' and '/webhooks' in l['uri']]
ok('Mailchimp webhook created (unsub+cleaned, no api source) → Connected', r=='ok connected' and hb and hb[0]['events']['unsubscribe'] and hb[0]['events']['cleaned'] and not hb[0]['sources']['api'], (r, hb))
ok('Zoho webhook state Connected after its first call', wp('echo PL_NL_Hooks::state("zoho");')=='connected')
# 11. duplicates within 24h: no second email
reset(); http(B+'/wp-json/pl/v1/subscribe', {'email': 'g.test7@example.org', 'form':'footer'}); http(B+'/wp-json/pl/v1/subscribe', {'email': 'g.test7@example.org', 'form':'footer'})
ok('same address twice in 24h → one email', len(mails())==1, len(mails()))
print('\n%d/%d passed' % (sum(1 for _,c in res if c), len(res)))
