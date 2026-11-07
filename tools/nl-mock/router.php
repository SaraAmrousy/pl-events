<?php
// Mock Mailchimp + Zoho for the sandbox only. Mode file: ok | down. Every request is appended to log.jsonl.
$dir = __DIR__; $mode = trim(@file_get_contents("$dir/mode") ?: 'ok');
$uri = $_SERVER['REQUEST_URI']; $m = $_SERVER['REQUEST_METHOD']; $body = file_get_contents('php://input');
file_put_contents("$dir/log.jsonl", json_encode(['m' => $m, 'uri' => $uri, 'auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? '', 'body' => $body ?: $_POST]) . "\n", FILE_APPEND);
header('Content-Type: application/json');
if ($mode === 'down') { http_response_code(503); echo '{"title":"Service Unavailable"}'; return; }
if (str_starts_with($uri, '/mailchimp')) {
  $hooks = json_decode(@file_get_contents("$dir/mc_hooks.json") ?: '[]', true);
  if (str_contains($uri, '/webhooks')) {
    if ($m === 'POST') { $hooks[] = json_decode($body, true); file_put_contents("$dir/mc_hooks.json", json_encode($hooks)); echo '{"id":"wh1"}'; return; }
    echo json_encode(['webhooks' => $hooks]); return;
  }
  if ($m === 'GET' && str_contains($uri, '/members')) { echo '{"members":[{"marketing_permissions":[{"marketing_permission_id":"mp_email","text":"Email"},{"marketing_permission_id":"mp_ads","text":"Customized Online Advertising"}]}]}'; return; }
  if ($m === 'GET') { echo '{"name":"Sara test audience","stats":{"member_count":3}}'; return; }
  if (str_contains($uri, '/tags')) { http_response_code(204); return; }
  $b = json_decode($body, true) ?: [];
  if ($m === 'PUT' && file_exists("$dir/mc_compliance") && ($b['status'] ?? '') === 'subscribed') { http_response_code(400); echo '{"title":"Member In Compliance State","detail":"x is already a list member in compliance state due to unsubscribe."}'; return; }
  echo json_encode(['id' => 'x', 'status' => $b['status'] ?? 'subscribed']); return;
}
if (str_starts_with($uri, '/zoho-accounts')) { echo '{"access_token":"mock-access","expires_in":3600,"refresh_token":"mock-refresh"}'; return; }
if (str_starts_with($uri, '/zoho')) {
  if (str_contains($uri, 'getmailinglists')) { echo '{"status":"success","code":"0","list_of_details":[{"listkey":"testlistkey","listname":"Sara test list","noofcontacts":"2"}]}'; return; }
  if (str_contains($uri, 'listsubscribe') && file_exists("$dir/zoho_doi")) { echo '{"status":"success","code":"0","message":"A confirmation email is sent to the user. User needs to confirm to successfully subscribe."}'; return; }
  echo '{"status":"success","code":"0","message":"User successfully subscribed."}'; return;
}
http_response_code(404); echo '{}';
