# ClearStay 4.2 — Real-Time Room Quantity, Booking Capacity & Remaining Inventory Simulator

## 1. Executive Summary & Objective

In modern hotel distribution, Online Travel Agencies (OTAs) such as Booking.com, Agoda, and Expedia frequently deliver multi-room reservation webhooks (e.g. 2, 3, or more rooms requested by families, groups, or corporate guests). 

Prior to ClearStay 4.2, the Inbound Webhook Simulator only accepted single-room reservations and lacked a real-time stay capacity and remaining inventory indicator. The **ClearStay 4.2 Room Quantity & Remaining Inventory Simulator** elevates this into a fully reactive, 3-column interactive simulation console that:
1. Accepts variable room counts ($K \ge 1$) per webhook transaction.
2. Evaluates inventory across **every individual occupied night of the stay** (with strict exclusion of check-out day).
3. Evaluates stay bottleneck capacity:
   $$\text{Bottleneck Capacity} = \min_{d \in \text{Stay Nights}} \left(\max(0, \text{Total}_d - \text{Reserved}_d - \text{Maintenance}_d)\right)$$
4. Guarantees anti-double-decrement for originating OTAs while reliably broadcasting outbound inventory updates to all other connected channels.
5. Displays a live reactive before/delta/after reconciliation grid across ClearStay Central and all 5 partner OTAs.
6. Provides inline outbox retry capabilities for simulated channel failures and network discrepancies.

---

## 2. Architecture & Component Interaction

```
[ Inbound Booking Webhook Simulator ]
               │
               ▼ (POST /integrations/simulators/availability-preview)
[ InboundBookingWebhookService::calculateAvailabilityPreview ]
       ├─ Validates check-in & check-out dates (excluding check-out day)
       ├─ Queries CentralInventory per occupied date
       ├─ Computes bottleneck capacity & remaining percentage
       └─ Returns Live Breakdown & 6-System Partner Comparison
               │
               ▼ (POST /integrations/inbound-webhook)
[ InboundBookingWebhookService::processInboundBooking ]
       ├─ DB::transaction() with SELECT ... FOR UPDATE row locks
       ├─ Verifies capacity on ALL nights (rejects HTTP 409 if bottleneck < requested rooms)
       ├─ Decrements Central Inventory by K rooms
       ├─ Allocates physical rooms & sets group_booking_reference if K > 1
       ├─ Decrements Originating Partner Remote Inventory by K rooms
       ├─ Broadcasts outbound update to OTHER connected channels (excluding originating partner)
       └─ Returns structured before/after comparison table across all 6 systems
```

---

## 3. Authoritative Multi-Night Stay Logic & Formulas

### Occupied Night Definition
For a stay from `check_in_date` to `check_out_date`, the occupied room nights are:
$$\text{Stay Nights} = [ \text{check\_in}, \text{check\_in} + 1\text{ day}, \dots, \text{check\_out} - 1\text{ day} ]$$
- **Check-out day is strictly never counted or deducted as an occupied room night.**
- Example: Check-in Dec 20, Check-out Dec 22 = 2 nights (Dec 20 and Dec 21).

### Nightly Availability Calculation
For each night $d$:
$$\text{Available Units}_d = \begin{cases} 0 & \text{if } \text{stop\_sell}_d = \text{true} \\ \max(0, \text{Total}_d - \text{Reserved}_d - \text{Maintenance}_d) & \text{otherwise} \end{cases}$$

### Stay Bottleneck & Acceptance Rule
$$\text{Stay Bottleneck} = \min_{d \in \text{Stay Nights}} (\text{Available Units}_d)$$
$$\text{Can Accept Booking} = (\text{Stay Bottleneck} \ge \text{Requested Rooms}) \land (\forall d \in \text{Stay Nights}: \neg \text{stop\_sell}_d)$$

If $\text{Can Accept Booking} = \text{false}$, the entire booking is rejected immediately with **HTTP 409 Conflict (`CAPACITY_EXHAUSTED`)** and zero inventory units are deducted.

---

## 4. Originating OTA Anti-Double-Decrement Guarantee

When an inbound reservation arrives from e.g. **Agoda**:
1. Agoda sold the rooms externally on its platform first. Therefore, Agoda's remote inventory is updated directly from $5 \to 3$.
2. ClearStay Central PMS accepts the booking and decreases central inventory from $5 \to 3$.
3. ClearStay broadcasts the new remaining inventory ($3$) via `SyncOutboxEvent` to the **remaining connected channels** (Booking.com, Expedia, Rakuten Travel, Jalan.net).
4. `broadcastInventoryChange($hotelId, $payload, 'inbound_booking_sync', $partnerCode)` explicitly excludes Agoda from outbound broadcast.
5. **Agoda is never sent an outbound inventory reduction for its own sale, preventing double-decrementing from 3 down to 1.**

---

## 5. User Interface & Interactive Layout

The `/integrations/simulators` interface features a 3-column reactive layout:

| Column 1: Inbound Booking Form | Column 2: Live Capacity & Availability | Column 3: Channel Reconciliation |
| :--- | :--- | :--- |
| • Originating Partner dropdown<br>• **Number of Rooms ($K$)** (1 to 10)<br>• Room Type selector<br>• Check-in / Check-out pickers<br>• Guest Name & Total Price<br>• External Res ID & Idempotency Key<br>• Dispatch Webhook Button | • **Live Availability Before** hero card<br>• **Estimated Remaining** hero card<br>• Dynamic color badge (Green/Amber/Red)<br>• Capacity progress bar<br>• Date-by-date stay breakdown table<br>• Instant debounced AJAX preview | • 6-system live availability table<br>• ClearStay Central vs 5 OTA simulators<br>• Before, Delta, After, and Status<br>• Status: `MATCHED`, `FAILED`, `DISCREPANCY`<br>• Inline Outbox Retry for failed channels |

Directly beneath the 3-column simulator is the **Inventory Movement History Log**, providing an instant real-time audit record of every simulated reservation, room quantity booked, and before/after inventory levels.
