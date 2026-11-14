# ClearStay 4.2 — Reports Studio & CSV Export Specification

**Document Reference:** `docs/analytics/report-studio-specification.md`  
**Route:** `/reports` (`reports.index`)  
**Controller:** `App\Http\Controllers\ReportsController`  
**Dependencies:** `InventoryLedgerEntry`, `SyncEvent`, `ChannelOutbox`, `Reservation`  

---

## 1. Overview & Separation from Analytics

ClearStay maintains a strict separation between **Analytical Dashboards** (`/booking-analytics` and `/analytics`) and **Compliance Reporting & Data Extraction** (`/reports`):

- **Dashboards** provide visual exploration, aggregations, trend charts, and predictive pace curves.
- **Reports Studio** provides official, audit-grade tabular data extraction, compliance verification, and streaming CSV downloads formatted with UTF-8 BOM for Microsoft Excel compatibility (including Japanese kanji/hiragana support).

---

## 2. Standard Report Definitions

1. **Synchronization Audit Report (`/reports/export/sync-logs`):**
   - Extracts all channel manager synchronization attempts, payload checksums, partner response codes, latency in milliseconds, and status (SUCCESS / RETRY / PERMANENT_ERROR).
2. **Inventory Ledger Audit Report (`/reports/export/inventory`):**
   - Full double-entry ledger extract displaying every availability delta, actor ID, correlation key, and balance change.
3. **Reservation & Revenue Ledger Report (`/reports/export/audit-logs`):**
   - Complete transaction log of reservations, payment reconciliation statuses, and discrepancy flags.

---

## 3. CSV Import Studio (`/csv-import`)

Separated from both Analytics and Reports, the CSV Import workspace (`CsvImportController`) allows property managers to bulk-import historical reservations, offline direct corporate contracts, or room inventory updates using standardized CSV templates.
