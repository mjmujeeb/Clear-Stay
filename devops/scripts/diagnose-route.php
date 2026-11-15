<?php

$baseUrl = 'https://clearstay-hospitality.onrender.com';
$cookieJar = tempnam(sys_get_temp_dir(), 'cs_diag_');

function req($url, $method = 'GET', $fields = null) {
    global $cookieJar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $eff = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['status' => $status, 'eff' => $eff, 'body' => $body];
}

// 1. Get login page
$login = req("{$baseUrl}/login");
preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/', $login['body'], $m);
$token = $m[1] ?? '';

// 2. Authenticate
$auth = req("{$baseUrl}/login", 'POST', [
    '_token' => $token,
    'email' => 'admin@clearstay.jp',
    'password' => 'password',
]);
echo "Auth effective: {$auth['eff']} (status {$auth['status']})\n";

// 3. Test pricing-settings
$pricing = req("{$baseUrl}/pricing-settings");
echo "Pricing status: {$pricing['status']} effective: {$pricing['eff']}\n";
echo "Pricing body preview: " . substr(strip_tags($pricing['body']), 0, 300) . "\n\n";

// 4. Test simulators
$sim = req("{$baseUrl}/integrations/simulators");
echo "Simulators status: {$sim['status']} effective: {$sim['eff']}\n";
echo "Simulators body preview: " . substr(strip_tags($sim['body']), 0, 300) . "\n\n";

// 5. Test another route, e.g. /rate-studio
$rates = req("{$baseUrl}/rate-studio");
echo "Rate studio status: {$rates['status']} effective: {$rates['eff']}\n";

// 6. Test /channels
$channels = req("{$baseUrl}/channels");
echo "Channels status: {$channels['status']} effective: {$channels['eff']}\n";
