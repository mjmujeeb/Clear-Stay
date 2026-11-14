# ClearStay 5.0 — Dynamic Hotel Reservation Pricing Architecture

## 1. System Overview

ClearStay 5.0 introduces an intelligent, date-aware hotel reservation pricing engine designed specifically for internal property management system (PMS) operations. 

**Architectural Scope & Non-Requirements:**
- **Zero OTA or Channel-Manager Dependencies:** The pricing engine is strictly focused on internal hotel operations, direct reservations created by receptionists and authorized front-desk staff, and manager-configured yield rules.
- **True Calendar Awareness:** Rather than applying flat room type rates ($N \times \text{Base Price}$), the engine independently calculates every occupied night of a guest's stay, evaluating base rates, weekend rules, statutory Japanese holidays, special events, and seasonal rules.
- **Authoritative Server Recalculation:** Client-submitted prices or frontend manipulation attempts are completely disregarded. The server authoritatively recomputes the price quote within a transaction with row-level locking (`SELECT ... FOR UPDATE`), persists the immutable calculation breakdown in `reservation_pricing_snapshots`, and records audit history.
- **Check-Out Date Excluded:** A reservation from April 28 to May 3 charges exactly 5 nights. The departure date is never billed as an occupied room night.

---

## 2. Receptionist Reservation Workflow (`/reservations/create`)

### 2.1 Live Price Calculation Preview
When the front-desk agent selects a room and inputs check-in / check-out dates:
1. **Debounced AJAX Request:** A lightweight asynchronous request is dispatched to `POST /reservations/price-quote` with `{ room_id, check_in_date, check_out_date }`.
2. **Dynamic Breakdown Card:**
   - **Nightly Breakdown Table:** Displays each occupied night's date, day of week (`Fri (金)`), base rate, color-coded rule badges (e.g. `[Weekend +15%]`, `[ゴールデンウィーク +35%]`, `[桜シーズン +20%]`), and the final nightly rate.
   - **Financial Summary:** Subtotal, Base Total, Surcharges Total, Tax Total, and Grand Total.
   - **Room Availability Pill:** If the room is already booked on any overlapping date, the UI instantly displays `Room Occupied (Conflict with CS-XXXX-1234)` and disables the booking button.

### 2.2 Transactional Booking & Row Locking
Upon form confirmation (`POST /reservations`):
1. Begins a database transaction.
2. Acquires an exclusive row-level lock on the target room (`Room::lockForUpdate()`).
3. Executes a pessimistic collision check across all active reservations (`status IN ('confirmed', 'checked_in')`). If an overlap exists, a localized validation exception is thrown.
4. Invokes `ReservationPricingService::calculateQuote(...)` on the server to compute the authoritative price.
5. Inserts the reservation record with the authoritative `total_price`.
6. Persists the complete `nightly_breakdown` JSON into `reservation_pricing_snapshots`.
7. Commits the transaction and logs the audit event.

---

## 3. Manager Pricing Settings Centre (`/pricing-settings`)

The dedicated manager control center is structured into seven distinct sections:

1. **Section A — Base Room Rates by Type:** Real-time overview and inline editor for base prices of all room types.
2. **Section B — Configurable Weekend Surcharges:** Toggle active weekend days (e.g. Friday and Saturday, or including Sunday) and specify the percentage increase (default: 15.0%).
3. **Section C — Japanese National Holidays & Special Events:** Seeded official statutory holidays (2025–2027) with surge multipliers and custom hotel festivals.
4. **Section D — Seasonal & Custom Pricing Rules:** Date-range pricing adjustments (e.g., Cherry Blossom Sakura Peak, Summer Vacation, Autumn Foliage) with priority weighting and room-type scoping.
5. **Section E — Rule Stacking Policy & Rounding Engine:** Selection of stacking logic (Additive, Multiplicative, or Highest-Only) and rounding precision (Whole Yen, Nearest 100 JPY, or exact decimals).
6. **Section F — Interactive Rate Calculation Sandbox:** Live interactive simulator allowing managers to test hypothetical rate quotes, inspect nightly breakdowns, and evaluate policy changes before publishing.
7. **Section G — Pricing Audit History:** Immutable record of all pricing rule updates, base rate modifications, and policy changes with timestamp, manager ID, and before/after JSON payloads.

---

## 4. Reservation Modification Lifecycle

When a receptionist or guest changes dates via `ReservationService::changeDatesWithRowLock`:
1. Acquires row lock on the reservation (`Reservation::lockForUpdate()`).
2. Acquires row lock on the target room (`Room::lockForUpdate()`).
3. Verifies zero overlapping collisions on the new date range.
4. Re-runs `ReservationPricingService::calculateQuote(...)`.
5. Updates reservation dates and `total_price` to the new authoritative calculation.
6. Writes a new pricing snapshot version in `reservation_pricing_snapshots`.
7. Records a `ReservationChange` record with price delta, user attribution, and IP address.
