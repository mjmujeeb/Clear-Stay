# ClearStay 4.1 Interview Demonstration Scenarios (Temairazu, Inc.)

**Target**: Web Application Engineer Technical Interview Scenarios  
**Hub Location**: Access live via `GET /demo-centre` or follow these real-time walk-through steps.  

---

## Scenario 01: Multi-Channel Outbox Synchronization
- **Concept**: How does ClearStay prevent channel synchronization drift with Temairazu without blocking web requests?
- **Workflow**:
  1. Login as `revenue@clearstay.com`. Navigate to `/channels`.
  2. Modify base rate or room allocation for Deluxe Suite.
  3. Observe outbox entry generated in `ota_sync_logs` with status `queued`.
  4. Trigger manual or worker sync (`POST /channels/sync-all`). Observe state transition to `delivered` with payload latency under 80ms.

---

## Scenario 02: Concurrency-Safe Date Change & Double-Booking Defense
- **Concept**: Defending against simultaneous room modification requests using pessimistic row locks.
- **Workflow**:
  1. Navigate to `/reservation-integrity`.
  2. Click "Simulate Concurrent Booking Race" on a single remaining room.
  3. Notice Worker #1 acquiring `SELECT ... FOR UPDATE` lock and securing confirmation.
  4. Notice Worker #2 receiving graceful `409 Conflict` or dynamic reassignment rather than catastrophic double-booking.

---

## Scenario 03: Easy Mode Senior Operator Experience
- **Concept**: Addressing labor shortages and aging hospitality workers with 1-touch simplified interfaces.
- **Workflow**:
  1. Click "Easy Mode" in the top navigation bar.
  2. Notice typography expanding to 18px+, cards enlarging with high-contrast borders, and navigation simplifying to 3 primary choices.
  3. Click "New Reservation" (`/easy-mode/create`). Enter guest name "Ichiro Suzuki".
  4. Submit reservation. Notice instant confirmation and real persistence in `reservations` and `guests` tables.
  5. Toggle back to Standard Mode. Notice the new booking appears immediately in the main overview dashboard.

---

## Scenario 04: Responsive Experience Lab Zero-Clipping Verification
- **Concept**: Proving multi-device layout integrity without relying on static mockups.
- **Workflow**:
  1. Open `/experience-lab`.
  2. Switch to "Dual Preview Mode" comparing 390px (Mobile Standard) and 1440px (Desktop High-Res).
  3. Select `/reservations` from the route dropdown.
  4. Observe live side-by-side frames. Note zero horizontal scroll blowout and fluid table containment.
  5. Click "Run Responsive Audit Sweep" to verify 100% compliance across all 27 enterprise routes.

---

## Scenario 05: Two-Person Elevation & Access Governance
- **Concept**: Enterprise security, principle of least privilege, and dual-key authorization.
- **Workflow**:
  1. Login as `manager@clearstay.com`.
  2. Request emergency access elevation for high-risk action (`POST /access-governance/grant`).
  3. Risk score engine classifies action as "High Risk (Score: 85)".
  4. Login as `secadmin@clearstay.com` in separate tab.
  5. Review and approve the request. Observe time-bound elevation activation with automated 4-hour expiration countdown.
