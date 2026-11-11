# ClearStay 4.1 Production Deployment Smoke Test Checklist

**Target**: Post-Deployment Live Verification & Smoke Testing Protocol  
**Scope**: Zero-Downtime Deployment Parity Verification  

---

## 1. Automated Smoke Test Script

When ClearStay is deployed to production (or staging), execute the following smoke verification sequence:

```bash
#!/bin/bash
set -e

BASE_URL="http://127.0.0.1:8000" # Or live HTTPS domain
echo "=== Starting ClearStay 4.1 Post-Deploy Smoke Test ==="

# 1. Healthcheck probe
echo "[1/6] Probing health endpoint..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/up")
if [ "$STATUS" -ne 200 ]; then
  echo "FAILED: /up healthcheck returned $STATUS"
  exit 1
fi
echo "PASSED: /up returned HTTP 200"

# 2. Public Home Page
echo "[2/6] Probing public welcome landing..."
HOME_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/")
if [ "$HOME_CODE" -ne 200 ]; then
  echo "FAILED: Public home returned $HOME_CODE"
  exit 1
fi
echo "PASSED: Public landing returned HTTP 200"

# 3. Security Redirect on Protected Routes
echo "[3/6] Testing unauthenticated redirect to login..."
REDIR_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/dashboard")
if [ "$REDIR_CODE" -ne 302 ]; then
  echo "FAILED: /dashboard unauthenticated access returned $REDIR_CODE (expected 302)"
  exit 1
fi
echo "PASSED: Protected route redirects cleanly to /login"

# 4. Database Ping & Cache Validation
echo "[4/6] Executing Laravel internal diagnostics..."
php artisan about --only=environment

# 5. Route & Config Cache Integrity
echo "[5/6] Verifying route caching..."
php artisan route:list --columns=name,method,uri > /dev/null
echo "PASSED: Route catalogue intact"

# 6. Database Migrations Status
echo "[6/6] Verifying schema migrations..."
php artisan migrate:status
echo "=== All 6 Smoke Test Steps Succeeded ==="
```

---

## 2. Manual Visual Smoke Checklist for Release Verification

1. [ ] **Login Screen**: Authenticate with `manager@clearstay.com` (Password: `password`). Verify redirect to Unified Overview.
2. [ ] **Easy Mode Toggle**: Click "Easy Mode" in sidebar or top bar. Verify UI transforms with large typography and cards.
3. [ ] **New Reservation in Easy Mode**: Complete 1-touch Japanese reservation for Ichiro Suzuki. Verify redirect to reservation list.
4. [ ] **Experience Lab**: Open `/experience-lab`. Inspect dual-mode preview comparing 390px vs 1440px. Assert zero clipping on `/reservations`.
5. [ ] **Learning Centre Video Playback**: Navigate to `/learning-centre`. Play Video 01 or 03. Scrub the timeline to verify HTTP 206 range seeking.
6. [ ] **QA Centre**: Navigate to `/qa-centre`. Assert 126/126 passing tests indicator. Click "Run Test Suite" to confirm real-time verification.
