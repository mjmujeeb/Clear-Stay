# ClearStay 4.1 — Feature Status Matrix

**Date:** 2026-11-14  
**Last Verified:** 185/185 tests passing

Legend: ✅ Working | ⚠️ Degraded | ❌ Broken | 🔵 Read-only/Safe | 🔄 Repaired in this cycle

---

## Authentication & Access Control

| Feature | Status | Notes |
|---|---|---|
| Staff Registration (public) | ✅ | Auto-logs in new user, redirects to dashboard |
| Staff Registration (admin-initiated) | ✅ | Redirects to `/staff` with success message |
| Login with credentials | ✅ | Challenge token verified, rate-limited |
| Two-Factor Authentication | ✅ | Redirects to 2FA screen if enabled |
| Account lockout (5 failed attempts) | ✅ | 15-minute lockout enforced |
| Logout | ✅ | Session invalidated, redirects to `/login` |
| Hotel context switching (SuperAdmin) | ✅ | Updates `current_hotel_id` in session |
| Multi-tenant isolation | ✅ | Managers cannot see other hotels' data |

---

## Reservation Management

| Feature | Status | Notes |
|---|---|---|
| Create reservation | ✅ | Guest create/lookup, room assignment, pricing |
| View reservation list | ✅ | Filtered by hotel, paginated |
| View reservation detail (JSON modal) | ✅ | Returns `reservation.guest_name` and full object |
| Change reservation dates | ✅ | Locked transaction, recalculates price |
| Check In | ✅ | Status → `checked_in`, session flash success |
| Check Out | ✅ | Status → `checked_out`, session flash success |
| Cancel reservation | ✅ | Status → `cancelled` |

---

## Room Board

| Feature | Status | Notes |
|---|---|---|
| Room board display | ✅ | Grouped by floor, filters by status/type |
| AJAX live status refresh (`GET /board/status`) | 🔄 ✅ | Now includes `available` count in JSON response |
| Update room status | ✅ | Returns updated status and `is_occupied` |
| Room status: clean/dirty/inspecting/maintenance | ✅ | Validated enum |

---

## Housekeeping

| Feature | Status | Notes |
|---|---|---|
| Create housekeeping task | ✅ | Linked to room, validates priority/type |
| Update task status (pending → in_progress → completed) | ✅ | Cascades room status to `clean` on complete |
| Task list | ✅ | Filtered by hotel |

---

## Bulk Change Studio

| Feature | Status | Notes |
|---|---|---|
| Page load (`GET /bulk-editor`) | 🔄 ✅ | Now strictly read-only — no DB inserts on load |
| Inventory matrix display | ✅ | Reads existing records for upcoming 14 days |
| Universal Update Preview | ✅ | Non-mutating; returns diff for review |
| Commit bulk changes | ✅ | Updates existing records, creates rollback snapshot |
| 1-Click Rollback | 🔄 ✅ | Correctly sets `status = 'rolled_back'`; `is_rolled_back` accessor works |
| Seed demo inventory | ✅ | Explicit POST only; idempotent via `ensureUpcomingInventory()` |
| Change history | ✅ | Last 10 batches shown with user info |

---

## Central Inventory Reconciliation

| Feature | Status | Notes |
|---|---|---|
| Reconciliation hub (`GET /inventory-reconciliation`) | 🔄 ✅ | No longer auto-inserts into central_inventories |
| Run Full Channel Audit (`POST /inventory-reconciliation/audit`) | 🔄 ✅ | Read-only on central_inventories; writes to audit tables only |
| Oversell risk detection | 🔄 ✅ | `oversell_risk` status detected and shown in red with CRITICAL badge |
| Discrepancy detection | ✅ | Quantity mismatches detected and stored |
| In-sync detection | ✅ | Matching pairs counted |
| Missing central record detection | ✅ | Channel records without central pair flagged |
| Audit run persistence | 🔄 ✅ | Stored in `inventory_audit_runs` table (new) |
| CSV export | 🔄 ✅ | `GET /inventory-reconciliation/export?format=csv` |
| JSON export | 🔄 ✅ | `GET /inventory-reconciliation/export?format=json` |
| Resolve discrepancy | ✅ | Dispatches outbox event, records audit |
| Audit history panel | 🔄 ✅ | Shows recent audit runs with KPIs |
| Filter by status | ✅ | Includes `oversell_risk` filter option |
| Filter by room type | ✅ | |
| Filter by channel | ✅ | |
| Filter by stay date | ✅ | |

---

## Rate Studio

| Feature | Status | Notes |
|---|---|---|
| Rate Studio page | ✅ | |
| Create rate plan | ✅ | Validates pricing model, cancellation policy, meal plan |
| Rate plan list | ✅ | Filtered by hotel |
| Rate parity management | ✅ | |

---

## Payment Reconciliation

| Feature | Status | Notes |
|---|---|---|
| Payment reconciliation hub | ✅ | Auto-runs batch if no records exist |
| Run reconciliation batch | ✅ | Matches payments to reservations |
| Resolve discrepancy | 🔄 ✅ | Now sets `reconciliation_status = 'resolved'`; accepts `resolution_notes` alias |
| CSV export | ✅ | UTF-8 BOM, correct headers |
| Filter by status/channel | ✅ | |

---

## Reports & Analytics

| Feature | Status | Notes |
|---|---|---|
| Reports hub | ✅ | |
| Inventory CSV export | ✅ | `Content-Type: text/csv` |
| Audit logs CSV export | ✅ | `Content-Type: text/csv` |
| Booking analytics | ✅ | |

---

## Infrastructure & Security

| Feature | Status | Notes |
|---|---|---|
| ClearShield bot defense | ✅ | Challenge token on all public forms |
| Security incident logging | ✅ | Logs failed logins, lockouts, registrations |
| Audit log | ✅ | Logs all state-changing operations |
| Trusted device tracking | ✅ | |
| Rate limiting | ✅ | 5 attempts / 60 seconds |
| Multi-tenant data isolation | ✅ | All queries scoped by hotel_id |
| SQLite unique constraint (central_inventories) | ✅ | Preserved throughout all repairs |
| NormalizedDateCast | 🔄 ✅ | Forces Y-m-d format; prevents datetime-vs-date mismatch |

---

## Production Deployment

| Item | Status | Notes |
|---|---|---|
| Local tests | ✅ 185/185 | Full green |
| Migrations pending push | ⚠️ | 2 new migrations will run on Render deploy via `php artisan migrate` |
| Render deploy | Pending push | Will auto-deploy on `git push origin main` |
| Production URL | `https://clearstay-hospitality.onrender.com` | SQLite (ephemeral) |
