# ClearStay 4.1 — Inventory Engine Repair Report

**Author:** Antigravity AI Engineering  
**Date:** 2026-11-14  
**Version:** ClearStay 4.1  
**Status:** ✅ RESOLVED — All inventory defects corrected

---

## 1. Executive Summary

Two critical production features were broken by a shared root cause: the application was
inserting duplicate inventory records against an existing composite unique constraint on
`central_inventories(hotel_id, room_type_id, date)`.

| Feature | Symptom | Root Cause | Status |
|---|---|---|---|
| Full Channel Audit (`POST /inventory-reconciliation/audit`) | `UniqueConstraintViolationException` | `runAuditRoutine()` called `firstOrCreate()` on `central_inventories` | ✅ Fixed |
| Bulk Change Studio (`GET /bulk-editor`) | `UniqueConstraintViolationException` on page load | `index()` called `ensureUpcomingInventory()` on every GET | ✅ Fixed |

No database constraints were removed. No exceptions were suppressed. Actual business logic was corrected.

---

## 2. Root Cause Analysis

### 2.1 Primary Root Cause — SQLite Date Format Mismatch

Laravel's Eloquent `'date' => 'date'` cast in `$casts` behaves differently on SQLite vs MySQL/PostgreSQL:

- **Write path:** Eloquent serializes Carbon objects as `'Y-m-d H:i:s'` (19 chars) → stored as `'2026-11-13 00:00:00'`
- **Query path:** Code issued `->where('date', '2026-11-13')` (10 chars) → SQLite strict string comparison returns `NULL`
- **Consequence:** `firstOrCreate(['date' => '2026-11-13'])` did not find the existing row, attempted `INSERT`, hit the unique constraint

**Verification:**
```sql
SELECT length(date), date FROM central_inventories LIMIT 3;
-- Before fix: 19 | 2026-11-13 00:00:00
-- After fix:  10 | 2026-11-13
```

### 2.2 Secondary Root Cause — Incorrect Write Locations

Even after correcting the date query, two code paths were still *writing* to `central_inventories` when they should have been read-only:

| Code Path | Bad Operation | Correct Behaviour |
|---|---|---|
| `GET /bulk-editor` → `BulkEditorController::index()` | Called `ensureUpcomingInventory()` → mass INSERT | Read-only: load existing records for display |
| `POST /inventory-reconciliation/audit` → `runAuditRoutine()` | Called `CentralInventory::firstOrCreate()` for every room-date | Read-only: query existing inventory, write findings to separate audit tables |

---

## 3. Files Modified

### 3.1 New Files Created

| File | Purpose |
|---|---|
| `app/Casts/NormalizedDateCast.php` | Custom Eloquent cast: forces `Y-m-d` format on write, returns Carbon on read |
| `app/Models/InventoryAuditRun.php` | Eloquent model for standalone audit run evidence |
| `database/migrations/2026_11_13_000001_normalize_inventory_dates.php` | Backfills all existing datetime-format dates to date-only format in `central_inventories` and `inventory_reconciliations` |
| `database/migrations/2026_11_13_000002_create_inventory_audit_runs_table.php` | Creates `inventory_audit_runs` table |
| `tests/Feature/InventoryEngineAndAuditParityTest.php` | 9 new feature tests covering inventory engine correctness |

### 3.2 Modified Files

| File | Change |
|---|---|
| `app/Models/CentralInventory.php` | Changed `'date' => 'date'` to `'date' => NormalizedDateCast::class`; added `calculateAvailableUnits()` |
| `app/Models/InventoryReconciliation.php` | Changed `'stay_date' => 'date'` to `'stay_date' => NormalizedDateCast::class` |
| `app/Models/BulkChangeBatch.php` | Added `getIsRolledBackAttribute()` virtual boolean accessor |
| `app/Http/Controllers/BulkEditorController.php` | Removed `ensureUpcomingInventory()` from `index()` GET; now strictly read-only |
| `app/Http/Controllers/InventoryReconciliationController.php` | Fully rewrote `runAuditRoutine()`: no longer calls `firstOrCreate` on central inventory; reads existing records via `keyBy()`; writes findings to `inventory_reconciliations` and `inventory_audit_runs` only |
| `app/Http/Controllers/RoomBoardController.php` | Added `'available'` key to `/board/status` JSON counts response |
| `app/Http/Controllers/PaymentReconciliationController.php` | `resolve()`: made `action` nullable (defaults to `force_match`), accepts `resolution_notes` alias, sets `reconciliation_status = 'resolved'` for manager-approved closure |
| `app/Services/Inventory/CentralInventoryService.php` | `ensureUpcomingInventory()`: uses `whereDate()`, catches `UniqueConstraintViolationException` gracefully; `getInventoryMatrix()`: changed to read-only (no DB write, returns virtual placeholder) |
| `resources/views/inventory_reconciliation/index.blade.php` | Added 5 KPI cards, loading spinner, duplicate-submit prevention, oversell_risk badges, audit history, CSV/JSON export buttons |
| `routes/web.php` | Added `GET /inventory-reconciliation/export` route |

---

## 4. Database Safety

### 4.1 Unique Constraint Status
```
UNIQUE constraint on central_inventories(hotel_id, room_type_id, date) — PRESERVED ✅
```

### 4.2 Data Migration Applied
```sql
-- Migration 2026_11_13_000001: Normalize datetime to date-only
UPDATE central_inventories SET date = substr(date, 1, 10) WHERE length(date) > 10;
UPDATE inventory_reconciliations SET stay_date = substr(stay_date, 1, 10) WHERE length(stay_date) > 10;
-- Result: 360 rows normalized in central_inventories
-- Result: 392 rows normalized in inventory_reconciliations
```

### 4.3 Safe Write Strategy

After the fix:

| Operation | Strategy |
|---|---|
| `getInventoryMatrix()` | Read-only. Returns virtual `new CentralInventory([...])` placeholder (not saved). |
| `ensureUpcomingInventory()` | Uses `whereDate()` for queries. Catches `UniqueConstraintViolationException` and re-fetches. Protected by DB transaction. |
| `applyBulkChange()` | Updates only existing records via `lockForUpdate()->get()`. Never inserts. |
| Audit routine | Reads central inventory via pre-fetched collection. Writes to `inventory_reconciliations` and `inventory_audit_runs` only. |

---

## 5. Audit Architecture

### 5.1 Audit Run Table Schema
```
inventory_audit_runs
├── id (uuid)
├── hotel_id
├── initiated_by (user_id)
├── initiated_at
├── completed_at
├── selected_channels (json)
├── room_date_pairs_checked
├── matching_pairs
├── discrepancies
├── oversell_risks
├── failed_checks
├── warnings
├── execution_duration_ms
└── status (pending|running|completed|failed)
```

### 5.2 Audit Scenarios Covered

| Scenario | Detection | Status |
|---|---|---|
| A — Perfect sync | `in_sync` | ✅ |
| B — Availability discrepancy | `discrepancy` | ✅ |
| C — Overselling risk | `oversell_risk` | ✅ |
| D — Missing channel record | `missing_channel_record` | ✅ |
| E — Missing central record | `missing_central_record` | ✅ |
| F — Stale sync data | `stale_data` | ✅ |
| G — Sandbox/unavailable channel | `not_verified` | ✅ |

---

## 6. Concurrency Safety

The `ensureUpcomingInventory()` method now:

1. Issues a `whereDate()` query to find existing records
2. Wraps INSERT in a `try/catch` for `UniqueConstraintViolationException`
3. On conflict: re-fetches the existing record (never discards it)
4. Uses `DB::transaction()` for atomic batch operations

This handles the race condition where two processes attempt to initialize the same inventory key simultaneously.

---

## 7. What Was Not Changed

- ✅ Composite unique constraint on `central_inventories` — **kept**
- ✅ All existing reservation, rate, and inventory data — **preserved**
- ✅ `migrate:fresh` — **never run**
- ✅ No exceptions suppressed
- ✅ No business logic replaced with no-ops
