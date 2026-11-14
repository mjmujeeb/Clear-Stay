# ClearStay 4.2 — Hotel Analytics & Performance Reports Specification

**Document Reference:** `docs/analytics/hotel-analytics-specification.md`  
**Route:** `/analytics` (`analytics.index`)  
**Controller:** `App\Http\Controllers\AnalyticsController`  
**Service:** `App\Services\AnalyticsService`  
**Model Dependencies:** `Hotel`, `Reservation`, `Room`, `RoomType`, `HousekeepingTask`, `PaymentReconciliation`  

---

## 1. Vision & Core Philosophy

Hotel Analytics & Performance Reports (`/analytics`) is ClearStay's core property business intelligence dashboard. It serves general managers, property owners, financial controllers, and head housekeepers.

Unlike Booking Analytics Plus (which focuses on booking behavior and lead-time pace), Hotel Analytics focuses on **property financial health, operational asset utilization, net channel margins, and turnaround efficiency**.

---

## 2. Key Performance Indicators (KPIs)

1. **Total Gross Room Revenue (¥):** Cumulative revenue booked and realized during the period.
2. **Net Realized Revenue (¥):** Room revenue after deducting channel commission fees (15% Booking.com, 15% Agoda, 18% Expedia, 10% Rakuten/Jalan, 0% Direct).
3. **Property Occupancy Rate (%):**
   $$\text{Occupancy Rate} = \frac{\text{Occupied Room Nights}}{\text{Total Available Room Nights}} \times 100$$
4. **Average Daily Rate (ADR ¥):**
   $$\text{ADR} = \frac{\text{Total Room Revenue}}{\text{Rooms Sold}}$$
5. **Revenue Per Available Room (RevPAR ¥):**
   $$\text{RevPAR} = \frac{\text{Total Room Revenue}}{\text{Total Available Room Nights}} = \text{ADR} \times \text{Occupancy Rate}$$
6. **Operational Asset Health:** Active rooms count, Clean vs Dirty inventory, and open maintenance tickets.

---

## 3. Dedicated Modules

### 3.1 Channel Net Profitability & Commission Deduction Matrix
Computes the true bottom-line contribution of each channel partner by accounting for contractual commissions:
- Gross Revenue
- Commission Expense
- Net Revenue
- Effective Margin %

### 3.2 Monthly Financial Trend (6 to 12 Months)
Month-over-month revenue tracking comparing completed stays and forward confirmed revenue.

### 3.3 Room Type Performance Yield
Evaluates revenue contribution and occupancy across room categories (e.g., Deluxe King, Executive Suite, Standard Twin, Japanese Tatami Suite).

### 3.4 Housekeeping & Asset Operations Efficiency
Tracks operational turnaround:
- Cleaning tasks completed today vs pending.
- Dirty rooms requiring immediate turnover.
- Maintenance tasks flagged and resolved.

### 3.5 Executive Commentary Generator
Deterministic rule engine generating natural language summary of property performance:
- *"Month-to-date revenue is ¥X with Y% occupancy."*
- *"Direct Web booking channel yielded 100% net margin, saving ¥Z in OTA commission fees."*
- *"Deluxe Suites represent highest ADR at ¥W per night."*

---

## 4. API Endpoints

- `GET /analytics`: Primary executive performance dashboard.
- `GET /analytics/occupancy-data`: JSON endpoint providing historical and forward occupancy curves for Chart.js.
- `GET /analytics/revenue-data`: JSON endpoint returning month-over-month revenue history.
- `GET /analytics/distribution-data`: JSON endpoint returning room type inventory and revenue share.
- `GET /analytics/profitability-data`: JSON endpoint returning gross vs net revenue per distribution channel.
