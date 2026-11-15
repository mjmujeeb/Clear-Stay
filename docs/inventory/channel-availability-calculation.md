# ClearStay 5.0 — Channel Availability Calculation & Discrepancy Detection Logic

## Mathematical Formulas, Data Modeling & Reconciliation Engine

---

### 1. Central PMS Available Quantity Formula

The Central PMS availability is computed dynamically from persistent records without altering database state:

$$\text{Available} = \max\Big(0, \text{Capacity} - \text{Reserved} - \text{Maintenance} - \text{Blocked}\Big)$$

Where:
- $\text{Capacity}$: Total physical rooms belonging to the property associated with the specific room type, verified with `is_active = true`.
- $\text{Reserved}$: Room units occupied on the specified date by reservations in statuses `confirmed`, `checked_in`, or `guaranteed`.
- $\text{Maintenance}$: Physical rooms tagged with `out_of_order` or `under_maintenance` housekeeping status.
- $\text{Blocked}$: Managerial hold allocations stored in `central_inventories.blocked_count`.

If a `CentralInventory` record exists for `(hotel_id, room_type_id, date)`, the service reads the audited `available_count` (or verifies it against the formula). If no explicit row exists, the system dynamically calculates the count on the fly from active rooms and active reservations, strictly ensuring **zero side-effects or implicit record creation during read queries**.

---

### 2. Independent Remote Channel Availability Query

Each Online Travel Agency (OTA) simulation operates an isolated inventory ledger. Rather than assuming the partner has the PMS quantity, the `RoomAvailabilityMatrixService` performs the following reconciliation sequence:

1. **Verify Channel Mapping**:
   Query `SimulatedPartnerProperty` for `(hotel_id, channel_id)`. Then find `SimulatedPartnerRoomMapping` where `internal_room_type_id = $roomType->id`.
   - If no mapping exists: Status is marked as `NOT_MAPPED` and available units = `null`.
2. **Retrieve Simulated Partner Stock**:
   Query `SimulatedPartnerInventory` for `(partner_property_id, partner_room_type_code, date)`.
   - If record is missing: Status is marked as `MISSING_REMOTE_INVENTORY` and available units = `null`.
   - If record exists: Available units = `simulated_partner_inventories.available_count`.
3. **Outbox Synchronization Inspection**:
   Inspect `WebhookOutboxEvent` for pending sync jobs where `hotel_id = $hotel->id`, `channel_id = $channel->id`, status is `pending` or `in_flight`, and payload targets the given date and room type.
   - If pending sync events exist: An alert indicator `has_pending_outbox = true` is flagged to warn the receptionist that an update is currently queued in transit.

---

### 3. Multi-Night Stay Range Calculation & Bottleneck Detection

For a booking spanning from $\text{Check-in Date } D_{\text{in}}$ to $\text{Check-out Date } D_{\text{out}}$:

$$\text{Occupied Nights } \mathcal{N} = \Big\{ D \;\Big|\; D_{\text{in}} \le D < D_{\text{out}} \Big\}$$

> **Important**: The check-out date $D_{\text{out}}$ is strictly **excluded** from occupied nights because departing guests vacate before incoming guests occupy the room.

For each room type $R$:
- Let $A(R, D)$ be the Central PMS available units on night $D \in \mathcal{N}$.
- **Bottleneck Minimum Availability**:
  $$\text{Min Available}(R) = \min_{D \in \mathcal{N}} A(R, D)$$
- **Maximum Availability**:
  $$\text{Max Available}(R) = \max_{D \in \mathcal{N}} A(R, D)$$
- **Stay Feasibility Assessment**:
  $$\text{Feasible} = \begin{cases} \text{true} & \text{if } \text{Min Available}(R) \ge 1 \\ \text{false} & \text{if } \text{Min Available}(R) = 0 \end{cases}$$

---

### 4. Discrepancy Taxonomy & Status Classification

Every channel-room pair is classified into one of seven clear operational states:

| Status Code | Badge Color | Trigger Condition | Operational Implication |
|---|---|---|---|
| `MATCHED` | Emerald Green | $\text{Channel Available} == \text{PMS Available}$ | Inventory is in full equilibrium across hotel and OTA. |
| `OVERSELL_RISK` | Crimson Red | $\text{Channel Available} > \text{PMS Available}$ | **CRITICAL RISK**: OTA is advertising more rooms than physically available. High risk of overbooking! |
| `DISCREPANCY` | Amber Yellow | $\text{Channel Available} < \text{PMS Available}$ | OTA is advertising fewer rooms than the PMS. Potential revenue loss due to suppressed availability. |
| `LOW_STOCK` | Orange Warning | $\text{PMS Available} > 0 \land \text{PMS Available} \le 2$ | Rooms are running dangerously low. Receptionist alerted to monitor inbound traffic. |
| `SOLD_OUT` | Neutral Slate | $\text{PMS Available} == 0$ | Room type is 100% committed or blocked for the night. |
| `NOT_MAPPED` | Indigo Purple | Mapping missing in `SimulatedPartnerRoomMapping` | Channel cannot sell this room type until room code is mapped. |
| `MISSING_REMOTE_INVENTORY` | Gray Outline | Mapping exists, but no row in `SimulatedPartnerInventory` | Initial channel rate & inventory push has not yet been executed. |

---

### 5. 1-Click Inventory Re-alignment Algorithm

When a manager triggers **Realign Channel** for a channel $C$, room type $R$, and date $D$:

1. Compute authoritative ground truth:
   $$Q = \text{Central PMS Available}(R, D)$$
2. Find the mapped `partner_room_type_code` and `simulated_partner_property_id`.
3. Update or create the `SimulatedPartnerInventory` record setting `available_count = Q`, `synced_at = now()`.
4. Log an immutable audit entry into `InventoryLedger` with:
   - `event_type`: `'channel_realignment'`
   - `channel_id`: $C$
   - `delta`: $Q - \text{old\_channel\_count}$
   - `user_id`: Authenticated Manager ID
   - `metadata`: Reason and timestamp.
5. Return instantaneous JSON response with the updated matrix payload.
