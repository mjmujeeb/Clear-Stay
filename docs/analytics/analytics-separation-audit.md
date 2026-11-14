# ClearStay 4.2 — Business Intelligence & Analytics Separation Audit

**Document Reference:** `docs/analytics/analytics-separation-audit.md`  
**Application Version:** ClearStay 4.2 Enterprise Hospitality Platform  
**Status:** Audit Complete & Implemented  
**Date:** November 2026  

---

## 1. Executive Summary & Root Cause Analysis

Prior to this upgrade, ClearStay exhibited a major UX and architectural duplication between two primary routes:
1. `/booking-analytics` (`BookingAnalyticsController@index`)
2. `/analytics` (`AnalyticsController@index`)

### The Problem Identified:
- Both controllers returned or referenced the same underlying view template (`analytics.index`) or duplicated high-level commercial metrics (Occupancy %, ADR, RevPAR).
- Users accessing `/booking-analytics` were presented with general hotel occupancy and revenue cards instead of pure booking behavior, pace velocity, and lead-time analytics.
- Users accessing `/analytics` were missing deeper operational indicators such as OTA commission deductions, net realized revenue, room-type yield distribution, and housekeeping turnaround performance.
- Navigation in the primary sidebar lacked clear conceptual distinction between booking demand trends (Revenue Management domain) and overall property health (General Management / Accounting domain).

---

## 2. Functional Separation Matrix

To provide a professional hospitality intelligence suite, ClearStay 4.2 strictly segregates these areas into distinct, non-overlapping tools:

| Dimension | Booking Analytics Plus (`/booking-analytics`) | Hotel Analytics & Reports (`/analytics`) | Reports Studio (`/reports`) |
| :--- | :--- | :--- | :--- |
| **Primary Audience** | Revenue Managers, Distribution Specialists, Channel Managers | General Managers, Owners, Financial Controllers, Operations Heads | Auditors, Compliance Officers, Accountants |
| **Core Question** | *"When and how are guests booking our hotel?"* | *"How profitably and efficiently is the property operating?"* | *"Where are the official audit records and CSV exports?"* |
| **Time Orientation** | Forward-looking (Pace, OTB, Lead Times, Pickup) | Historical & Real-Time Performance (MTD, YTD, RevPAR) | Point-in-time Records & Compliance Logs |
| **Key Metrics** | - Confirmed Reservations<br>- Net Booking Pickup (7d/30d)<br>- Cancellation Rate % & Churn<br>- Avg Booking Lead Time (Days)<br>- Avg Length of Stay (ALOS)<br>- On the Books (OTB) 30d/60d Demand | - Total Room Revenue (Gross ¥)<br>- Net Realized Revenue (After OTA fees)<br>- Occupancy Rate %<br>- ADR (Average Daily Rate ¥)<br>- RevPAR (Revenue Per Available Room ¥)<br>- Housekeeping Turnaround Efficiency | - Scheduled Sync Outbox Logs<br>- Inventory Ledger Audits<br>- Channel Error Telemetry<br>- Financial Reconciliation Logs |
| **Visualizations** | - Multi-Horizon Booking Pace Curves (Current vs 7d, 30d, 1y ago)<br>- Lead-Time Distribution Cohorts (0d, 1-3d, 4-7d, etc.)<br>- Length of Stay (LoS) Cohorts<br>- 30-Day On The Books Demand Forecast | - Monthly Gross vs Net Revenue (6-12 Months)<br>- Channel Profitability & OTA Commission Deduction Breakdown<br>- Room Type Revenue Contribution<br>- Housekeeping Task Turnaround & Maintenance Status | Tabular Data Grids with Custom Filtering, Sorting, and UTF-8 BOM CSV Streaming |
| **Data Models** | `BookingSnapshot`, `Reservation`, `Channel`, `RoomType` | `Reservation`, `Room`, `RoomType`, `HousekeepingTask`, `PaymentReconciliation` | `InventoryLedgerEntry`, `SyncEvent`, `ChannelOutbox` |

---

## 3. Detailed Architecture of Booking Analytics Plus (`/booking-analytics`)

### 3.1 Historical Pace Curve Engine
Inspired by the **TEMAIRAZU JIDO BA+** intelligence module used widely in Japan, Booking Analytics Plus tracks booking curves across multiple snapshot horizons:
- **Current Pace**: Live reservations on the books for target stay dates.
- **7 Days Prior**: Snapshot taken 7 days before the current date.
- **30 Days Prior**: Snapshot taken 30 days before the current date.
- **Last Year (365d)**: Year-over-year comparison snapshot.

### 3.2 Lead-Time Cohorts
Analyzes guest booking windows (days between `created_at` and `check_in_date`):
1. **Same-Day (0 Days)**: Last-minute walk-ins or mobile bookings.
2. **1–3 Days**: Short lead-time domestic travelers.
3. **4–7 Days**: One-week leisure planning window.
4. **8–14 Days**: Fortnight leisure/business window.
5. **15–30 Days**: 1-month advance bookings.
6. **31–60 Days**: Extended advance bookings.
7. **60+ Days**: Long-term holiday/inbound international bookings.

### 3.3 Length-of-Stay (LoS) Cohorts
Categorizes guest stay duration:
- 1 Night (Single overnight)
- 2 Nights (Weekend getaway)
- 3–4 Nights (Mid-week or extended weekend)
- 5–7 Nights (Weekly vacation)
- 8+ Nights (Long-stay / digital nomad / relocation)

---

## 4. Detailed Architecture of Hotel Analytics & Reports (`/analytics`)

### 4.1 Channel Net Profitability & Commission Deduction
Standard hotel PMS systems report only gross room charges. ClearStay 4.2 incorporates realistic commercial OTA commission schedules:
- **Booking.com**: 15% standard commission
- **Agoda**: 15% standard commission
- **Expedia**: 18% merchant/agency commission
- **Rakuten Travel**: 10% standard Japanese OTA fee
- **Jalan.net**: 10% standard Recruit OTA fee
- **Direct Web Engine**: 0% commission (100% net retention)

The system computes:
$$\text{Net Revenue} = \sum (\text{Gross Price} \times (1 - \text{Commission Rate}))$$

### 4.2 Operational & Housekeeping Efficiency
Monitors room asset readiness:
- Rooms Clean vs Dirty vs Maintenance.
- Average turnover completion time (hours between check-out and clean status).
- Active maintenance incidents and resolution status.

---

## 5. Verification Plan

1. **Routing Verification:**
   - `/booking-analytics` strictly renders `booking_analytics.index`.
   - `/analytics` strictly renders `analytics.index`.
   - `/reports` strictly renders `reports.index`.
   - `/csv-import` strictly renders `csv.index`.
2. **Automated Testing:**
   - Ensure `tests/Feature/ClearStayTemairazuTest.php` continues passing without regression.
   - Add dedicated test suite `tests/Feature/AnalyticsSeparationAndPaceTest.php` asserting strict isolation of metrics, pace curves, lead times, and channel profitability calculations.
