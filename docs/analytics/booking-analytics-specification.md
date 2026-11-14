# ClearStay 4.2 — Booking Analytics Plus (BA+) Specification

**Document Reference:** `docs/analytics/booking-analytics-specification.md`  
**Route:** `/booking-analytics` (`booking_analytics.index`)  
**Controller:** `App\Http\Controllers\BookingAnalyticsController`  
**Service:** `App\Services\Analytics\BookingAnalyticsService`  
**Model Dependencies:** `BookingSnapshot`, `Reservation`, `Channel`, `RoomType`, `Hotel`  

---

## 1. Vision & Core Philosophy

Booking Analytics Plus (BA+) is an advanced hospitality revenue intelligence system patterned after the high-performance revenue management modules of leading Japanese channel managers such as **TEMAIRAZU JIDO BA+**.

While standard hotel reporting looks backward at realized revenue, BA+ looks forward and analyzes the **dynamics of guest demand**:
- How far in advance do guests book?
- At what pace are room nights accumulating for next month's holiday weekend?
- Which OTA channels produce the lowest cancellation rates?
- What is our net booking velocity over the last 7 and 30 days?

---

## 2. Key Performance Indicators (KPIs)

The top metric bar in `/booking-analytics` provides revenue-specific indicators:

1. **Confirmed Reservations in Window:** Total non-cancelled reservations booked for the selected target horizon.
2. **Net Booking Pickup (Velocity):** Net new room nights added in the trailing 7 and 30 days.
   $$\text{Net Pickup} = \text{New Bookings Created} - \text{Cancellations in Period}$$
3. **Cancellation Rate %:**
   $$\text{Cancellation Rate} = \frac{\text{Cancelled Bookings}}{\text{Total Bookings Received}} \times 100$$
4. **Average Booking Lead Time:** Average elapsed days between the booking creation date and the check-in arrival date:
   $$\text{Avg Lead Time} = \frac{\sum (\text{Check-in Date} - \text{Booking Created Date})}{\text{Total Bookings}}$$
5. **Average Length of Stay (ALOS):** Average number of nights per guest stay:
   $$\text{ALOS} = \frac{\sum \text{Nights}}{\text{Total Bookings}}$$
6. **Future Room Nights on the Books (OTB):** Total room nights already confirmed for future stay dates across the next 30 days.

---

## 3. Advanced Analytical Components

### 3.1 Historical Pace Curve Engine (`getBookingPaceData`)
Demonstrates how bookings accumulate as stay date approaches.
- Compares booking volume across historical horizons (Current vs 7d ago vs 30d ago vs 1y ago) for a selected target stay date.
- Stored as immutable records in the `booking_snapshots` table:
  - `hotel_id`: Property isolation
  - `stay_date`: The target stay date
  - `snapshot_date`: When the snapshot was captured
  - `rooms_sold`: Number of rooms on the books at that moment
  - `total_revenue`: Booked revenue at that moment
  - `adr`: Realized ADR at that moment
  - `occupancy_rate`: Occupancy at that moment

### 3.2 Lead-Time Distribution (`getLeadTimeDistribution`)
Groups reservations into 7 distinct behavioral cohorts:
- **Same Day (0 Days)**
- **1 to 3 Days**
- **4 to 7 Days**
- **8 to 14 Days**
- **15 to 30 Days**
- **31 to 60 Days**
- **60+ Days**

### 3.3 Length-of-Stay Cohort Distribution (`getLengthOfStayDistribution`)
Groups bookings by stay length:
- 1 Night
- 2 Nights
- 3–4 Nights
- 5–7 Nights
- 8+ Nights

### 3.4 30-Day On-The-Books (OTB) Demand Forecast (`getFutureOnTheBooks`)
Calculates day-by-day committed room nights for the upcoming 30 days to highlight demand peaks, weekends, and soft-demand midweek periods.

### 3.5 Channel Booking Volume & Pickup Share (`getChannelDistribution`)
Displays the percentage of bookings and room nights contributed by each distribution partner:
- Booking.com
- Agoda
- Expedia
- Rakuten Travel
- Jalan.net
- Direct Web Engine

---

## 4. API & Export Endpoints

- `GET /booking-analytics`: Interactive dashboard with multi-horizon date filter, room-type selector, channel selector, and status toggle.
- `POST /booking-analytics/capture`: Manually captures an immutable snapshot for a given target stay date.
- `GET /booking-analytics/export-csv`: Streams a UTF-8 BOM CSV containing reservation-level lead times, channels, and pricing for external analysis.
