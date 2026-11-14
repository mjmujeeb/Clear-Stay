# ClearStay 4.2 — Simulator Reservation Workflow & Demonstration Guide

This guide describes how an interviewer, product manager, or engineer operates the ClearStay 4.2 Inbound Webhook Simulator to observe realistic multi-channel hotel inventory behavior.

---

## 1. Operating the Inbound Booking Simulator

1. Navigate to `/integrations/simulators` in the ClearStay web application.
2. Locate the **Real-Time Room Quantity, Capacity & Inventory Simulator** section.
3. Observe the three columns:
   - **Column 1 (Left)**: Inbound Booking Form.
   - **Column 2 (Middle)**: Live Capacity & Availability Indicator.
   - **Column 3 (Right)**: Simulated Partner Network & Live Reconciliation.

---

## 2. Walkthrough: Multi-Room Availability Inspection

1. In **Column 1**, select the room type (e.g. `Superior Queen Double`).
2. Adjust the **Number of Rooms** input:
   - When set to `2`, **Column 2** immediately updates via reactive AJAX:
     - **Live Availability Before**: `5 rooms`
     - **Estimated Remaining**: `3 rooms`
     - **Capacity Status**: `Booking can be accepted` (Green)
     - **Stay Nights Breakdown Table**: Displays each individual night with Total, Reserved, Available, and Remaining units.
   - When set to `6` (exceeding total units of 5):
     - **Live Availability Before**: `5 rooms`
     - **Estimated Remaining**: `0 rooms`
     - **Capacity Status**: `Capacity Exhausted (Rejection Guaranteed)` (Red)
     - **Dispatch Button**: Automatically switches to danger state: `<i class="fa-solid fa-ban"></i> Capacity Exhausted (Will Reject 409)`

---

## 3. Walkthrough: Executing an Inbound Booking Transaction

1. Set **Number of Rooms** to `2`.
2. Select **Originating Partner**: `Agoda Simulator`.
3. Click **Dispatch Inbound Webhook Booking**.
4. Observe the results:
   - An accepted card appears with confirmation code (e.g. `CS-A-E4F28190`), PMS Reservation ID, and assigned physical room numbers.
   - **Column 3 (Channel Reconciliation)** updates immediately:
     - **ClearStay Central PMS**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`)
     - **Agoda Simulator**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`) *(sold externally first)*
     - **Booking.com Simulator**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`)
     - **Expedia Simulator**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`)
     - **Rakuten Travel Simulator**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`)
     - **Jalan.net Simulator**: Before 5 &rarr; Delta -2 &rarr; After 3 (`MATCHED`)
   - A new row is automatically prepended to the **Inventory Movement History Log** at the bottom with a brief green highlight animation.
   - The form automatically generates a fresh random `external_reservation_id` and `idempotency_key` ready for the next test.

---

## 4. Walkthrough: Testing Multi-Night Bottlenecks

1. In `/bulk-change` or through database state, set Night 1 to have 5 rooms available, and Night 2 to have only 1 room available.
2. In the simulator, select the 2-night stay and request `2 rooms`.
3. The Live Availability Preview instantly detects the bottleneck on Night 2 and marks the entire stay as `Conflict`.
4. If dispatched, the server responds with **HTTP 409 Conflict**:
   ```json
   {
       "success": false,
       "status_code": 409,
       "error_code": "CAPACITY_EXHAUSTED",
       "error": "Inventory conflict on 2026-11-25: Available units (1) is less than requested rooms (2) or stop-sell is active. Reservation rejected.",
       "failed_stay_date": "2026-11-25",
       "available_units": 1,
       "requested_rooms": 2
   }
   ```
5. Central inventory and all partner channels remain completely unchanged. Zero units are deducted.

---

## 5. Walkthrough: Failure Injection & Discrepancy Retry

1. Scroll to the **Simulated Partner Network** cards at the top of `/integrations/simulators`.
2. Under **Expedia**, configure an intentional failure on operation `inventory_push` with status `HTTP 500`.
3. In the Inbound Simulator, dispatch a booking from **Agoda** for 1 room.
4. Observe the Channel Reconciliation Table in Column 3:
   - ClearStay Central drops to 4 (`MATCHED`).
   - Agoda drops to 4 (`MATCHED`).
   - Booking.com drops to 4 (`MATCHED`).
   - **Expedia Simulator remains at 5 (`FAILED`)** with a red badge and an inline **"Retry"** button.
5. In the Expedia failure card, remove or deactivate the failure injection.
6. Click the inline **Retry** button in the reconciliation table.
7. The outbox event re-executes, commits the inventory to Expedia's remote database, and updates Expedia's inventory to 4 (`MATCHED`).
