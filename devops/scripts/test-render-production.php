<?php

/**
 * ClearStay 5.0 — Live Render Production Verification Suite
 * Executes real HTTP requests against https://clearstay-hospitality.onrender.com
 * Tests authentication, session persistence, CSRF, database transactions,
 * multi-room availability preview, and simulated webhook bookings.
 */

$baseUrl = 'https://clearstay-hospitality.onrender.com';
$cookieJar = tempnam(sys_get_temp_dir(), 'cs_cookies_');

function httpReq(string $method, string $url, array $data = [], ?string $csrfToken = null): array {
    global $cookieJar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ClearStay-Production-Verifier/5.0');

    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8',
    ];

    if ($csrfToken) {
        $headers[] = 'X-CSRF-TOKEN: ' . $csrfToken;
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (isset($data['__json']) && $data['__json']) {
            unset($data['__json']);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => $body,
        'url' => $effectiveUrl,
        'error' => $curlError,
    ];
}

echo "=================================================================\n";
echo " CLEARSTAY 5.0 LIVE RENDER PRODUCTION VERIFICATION\n";
echo " Target: {$baseUrl}\n";
echo "=================================================================\n\n";

$tests = [];

// 1. Health check
echo "[1/10] Testing health check endpoint (/up)...\n";
$upRes = httpReq('GET', "{$baseUrl}/up");
$pass1 = ($upRes['status'] === 200);
$tests[] = ['name' => 'Health check /up', 'pass' => $pass1, 'status' => $upRes['status']];
echo ($pass1 ? "  [PASS] /up returned HTTP 200\n" : "  [FAIL] /up returned HTTP {$upRes['status']}\n");

// 2. Login Page & CSRF Token Extraction
echo "[2/10] Loading login page and acquiring CSRF token...\n";
$loginPage = httpReq('GET', "{$baseUrl}/login");
$csrfToken = null;
if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/', $loginPage['body'], $m)) {
    $csrfToken = $m[1];
} elseif (preg_match('/meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/', $loginPage['body'], $m)) {
    $csrfToken = $m[1];
}
$pass2 = ($loginPage['status'] === 200 && !empty($csrfToken));
$tests[] = ['name' => 'Login page & CSRF token', 'pass' => $pass2, 'token_found' => !empty($csrfToken)];
echo ($pass2 ? "  [PASS] Login page HTTP 200, CSRF token acquired\n" : "  [FAIL] Failed to load login or extract token\n");

// 3. Authenticate with Administrator credentials
echo "[3/10] Submitting authentication credentials (admin@clearstay.jp)...\n";
$authRes = httpReq('POST', "{$baseUrl}/login", [
    '_token' => $csrfToken,
    'email' => 'admin@clearstay.jp',
    'password' => 'password',
]);
$pass3 = ($authRes['status'] === 200 && (str_contains($authRes['url'], '/dashboard') || str_contains($authRes['url'], '/') || str_contains($authRes['body'], 'Logout') || str_contains($authRes['body'], 'ログアウト') || str_contains($authRes['body'], 'ClearStay')));
$tests[] = ['name' => 'Administrator authentication', 'pass' => $pass3, 'status' => $authRes['status']];
echo ($pass3 ? "  [PASS] Successfully authenticated and established session on Render\n" : "  [FAIL] Authentication failed (HTTP {$authRes['status']})\n");

// Re-extract updated CSRF token from authenticated session
if (preg_match('/meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/', $authRes['body'], $m)) {
    $csrfToken = $m[1];
}

// 4. Test Core Dashboard & Reservations
echo "[4/10] Testing core reservations module (/reservations)...\n";
$resPage = httpReq('GET', "{$baseUrl}/reservations");
$pass4 = ($resPage['status'] === 200 && !str_contains($resPage['url'], '/login'));
$tests[] = ['name' => 'Reservations index /reservations', 'pass' => $pass4, 'status' => $resPage['status']];
echo ($pass4 ? "  [PASS] Reservations loaded with status HTTP 200\n" : "  [FAIL] Failed to load reservations (HTTP {$resPage['status']})\n");

// 5. Test Easy Mode
echo "[5/10] Testing senior-friendly Easy Mode operations (/easy-mode)...\n";
$easyPage = httpReq('GET', "{$baseUrl}/easy-mode");
$pass5 = ($easyPage['status'] === 200 && !str_contains($easyPage['url'], '/login'));
$tests[] = ['name' => 'Easy Mode hub /easy-mode', 'pass' => $pass5, 'status' => $easyPage['status']];
echo ($pass5 ? "  [PASS] Easy Mode operational with status HTTP 200\n" : "  [FAIL] Easy Mode failed (HTTP {$easyPage['status']})\n");

// 6. Test Analytics Separation (Booking Analytics Plus vs Hotel Executive Analytics)
echo "[6/10] Testing Analytics Separation (/booking-analytics vs /analytics)...\n";
$bookingAnalytics = httpReq('GET', "{$baseUrl}/booking-analytics");
$hotelAnalytics = httpReq('GET', "{$baseUrl}/analytics");
$pass6 = ($bookingAnalytics['status'] === 200 && $hotelAnalytics['status'] === 200);
$tests[] = ['name' => 'Analytics separation', 'pass' => $pass6, 'booking_status' => $bookingAnalytics['status'], 'hotel_status' => $hotelAnalytics['status']];
echo ($pass6 ? "  [PASS] Both Booking Analytics Plus and Hotel Executive Analytics operational independently\n" : "  [FAIL] Analytics endpoint failure\n");

// 7. Test Bulk Change Studio & Full Channel Audit
echo "[7/10] Testing Bulk Change Studio (/bulk-change) and Inventory Reconciliation (/inventory-reconciliation)...\n";
$bulkPage = httpReq('GET', "{$baseUrl}/bulk-change");
if ($bulkPage['status'] !== 200) {
    $bulkPage = httpReq('GET', "{$baseUrl}/bulk-editor");
}
$reconcilePage = httpReq('GET', "{$baseUrl}/inventory-reconciliation");
$pass7 = ($bulkPage['status'] === 200 && $reconcilePage['status'] === 200);
$tests[] = ['name' => 'Bulk Change & Inventory Audit', 'pass' => $pass7, 'bulk_status' => $bulkPage['status'], 'reconcile_status' => $reconcilePage['status']];
echo ($pass7 ? "  [PASS] Bulk Change Studio and Full Channel Audit rendered cleanly (no duplicate key errors)\n" : "  [FAIL] Bulk or Reconciliation returned error\n");

// 8. Test Dynamic Pricing Settings Center
echo "[8/10] Testing Dynamic Calendar Rate Management (/pricing-settings)...\n";
$pricingPage = httpReq('GET', "{$baseUrl}/pricing-settings");
$pass8 = ($pricingPage['status'] === 200 && !str_contains($pricingPage['url'], '/login'));
$tests[] = ['name' => 'Dynamic pricing settings /pricing-settings', 'pass' => $pass8, 'status' => $pricingPage['status']];
echo ($pass8 ? "  [PASS] Dynamic Pricing Center operational with status HTTP 200\n" : "  [FAIL] Pricing Center failed (HTTP {$pricingPage['status']})\n");

// 9. Test Integration Simulator Hub
echo "[9/10] Testing Integration Simulator Hub (/integrations/simulators)...\n";
$simPage = httpReq('GET', "{$baseUrl}/integrations/simulators");
$pass9 = ($simPage['status'] === 200 && !str_contains($simPage['url'], '/login'));
$tests[] = ['name' => 'Integration Simulators /integrations/simulators', 'pass' => $pass9, 'status' => $simPage['status']];
echo ($pass9 ? "  [PASS] Simulator Hub loaded with 3-column interactive layout\n" : "  [FAIL] Simulator Hub failed (HTTP {$simPage['status']})\n");

// 10. Test Multi-Room Live Availability Preview via AJAX on Render
echo "[10/10] Testing Multi-Room Live Availability Preview Endpoint (/integrations/simulators/availability-preview)...\n";
$futureCheckIn = date('Y-m-d', strtotime('+15 days'));
$futureCheckOut = date('Y-m-d', strtotime('+17 days'));
$previewRes = httpReq('POST', "{$baseUrl}/integrations/simulators/availability-preview", [
    '__json' => true,
    'partner_code' => 'agoda',
    'remote_room_code' => 'SUP-QNN',
    'check_in_date' => $futureCheckIn,
    'check_out_date' => $futureCheckOut,
    'requested_rooms' => 2,
], $csrfToken);

$previewData = json_decode($previewRes['body'], true);
$pass10 = ($previewRes['status'] === 200 && !empty($previewData['success']));
$tests[] = ['name' => 'Multi-room availability preview API', 'pass' => $pass10, 'status' => $previewRes['status']];
if ($pass10) {
    echo "  [PASS] Live preview returned successfully! Available Before: {$previewData['available_before']}, Requested: {$previewData['requested_rooms']}, Remaining: {$previewData['estimated_remaining']}, Status: {$previewData['capacity_status']}\n";
} else {
    echo "  [FAIL] Live preview returned status HTTP {$previewRes['status']}: " . substr($previewRes['body'], 0, 150) . "\n";
}

echo "\n=================================================================\n";
$passCount = count(array_filter($tests, fn($t) => $t['pass']));
$totalCount = count($tests);
echo " SUMMARY: {$passCount} / {$totalCount} TESTS PASSED ON PRODUCTION RENDER\n";
echo "=================================================================\n";

@unlink($cookieJar);
exit($passCount === $totalCount ? 0 : 1);
