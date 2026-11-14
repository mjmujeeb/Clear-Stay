# ClearStay 4.2 — Analytics Separation Test Report

**Document Reference:** `docs/analytics/analytics-separation-test-report.md`  
**Test Suite:** `tests/Feature/ClearStayTemairazuTest.php`, `tests/Feature/AnalyticsSeparationAndPaceTest.php`  
**Status:** All Tests Passing  

---

## 1. Test Coverage Overview

The Analytics Separation test suite verifies that Booking Analytics Plus and Hotel Analytics & Reports operate as independent, non-overlapping BI platforms:

1. **Booking Analytics Plus (`/booking-analytics`):**
   - Asserts route renders dedicated `booking_analytics.index` view (NOT `analytics.index`).
   - Asserts availability of booking-specific variables: `metrics`, `historicalComparison`, `bookingPace`, `leadTimeDistribution`, `lengthOfStayDistribution`, `futureOnTheBooks`, `channelDistribution`.
   - Verifies snapshot capture logic across 4 horizons (Current, 7d ago, 30d ago, 1y ago).
   - Verifies CSV streaming download for reservation-level lead time data.
2. **Hotel Analytics & Reports (`/analytics`):**
   - Asserts route renders dedicated `analytics.index` view.
   - Asserts presence of property financial indicators: `kpis` (ADR, RevPAR, Occupancy %, Gross Revenue, Net Revenue after commission deductions).
   - Verifies channel profitability matrix deducting contractual OTA commissions (15% Booking.com, 15% Agoda, 18% Expedia, 10% Rakuten/Jalan, 0% Direct).
   - Verifies operational and housekeeping turnaround metrics.
   - Verifies JSON endpoints for occupancy trends, monthly revenue, and distribution.
3. **Reports Studio (`/reports`) and CSV Import (`/csv-import`):**
   - Verifies routes exist independently and are accessible to authorized staff.
   - Verifies sidebar navigation links and active states.
