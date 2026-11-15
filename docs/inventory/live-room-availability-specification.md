# ClearStay 5.0 — Live Room Availability Matrix & Multi-Channel Inventory Visibility

## Technical Specification & System Architecture

### 1. Overview & Objective
In modern hotel operations, receptionists, revenue managers, and front-desk supervisors require real-time visibility into physical room inventory alongside channel distribution availability. Discrepancies between Central PMS and Online Travel Agencies (OTAs) can lead to catastrophic double-bookings or lost revenue due to stale allotments.

The **Live Room Availability Matrix** (`/room-availability`) provides a unified, interactive command center inside ClearStay 5.0. It exposes:
- Real-time room type capacity, reserved units, maintenance holds, and blocked allocations.
- Real-time comparisons between the **Central PMS available inventory** and **6 independent simulated partner channels**:
  1. Booking.com Simulator
  2. Agoda Simulator
  3. Expedia Partner Solutions Simulator
  4. Rakuten Travel Simulator
  5. Jalan.net Simulator
  6. Direct Web Booking Simulator
- Automatic discrepancy detection (MATCHED, DISCREPANCY, OVERSELL RISK, LOW STOCK, SOLD OUT, NOT MAPPED, MISSING REMOTE INVENTORY).
- Dual operational modes: **Single Date View** and **Multi-Night Stay Range View** (with strict checkout-date exclusion).
- An interactive **Quantity Inspector** modal providing ledger breakdown and transaction histories.
- A **1-Click Channel Inventory Re-alignment** tool to synchronize remote channel stocks to the PMS ground truth.
- High-fidelity **CSV** (UTF-8 BOM formatted) and **Print-Friendly** export engines.

---

### 2. Architectural Design & Boundaries

```
+-----------------------------------------------------------------------------------+
|                            ClearStay UI Layer                                     |
|  - room_availability/index.blade.php (Single-date & Stay-range interactive tabs)  |
|  - room_availability/print.blade.php (Print report stylesheet & layouts)         |
+------------------------------------------+----------------------------------------+
                                           | AJAX / JSON & Server-Side Render
                                           v
+-----------------------------------------------------------------------------------+
|                        RoomAvailabilityMatrixController                           |
|  - index(): Initial page load & KPI calculations                                  |
|  - matrixData(): Reactive JSON endpoint for dynamic date / range changes          |
|  - quantityDetails(): Deep ledger drill-down endpoint                             |
|  - realignChannel(): 1-Click channel inventory synchronizer                       |
|  - export(): CSV & Print generator                                                |
+------------------------------------------+----------------------------------------+
                                           | Calls Service Methods
                                           v
+-----------------------------------------------------------------------------------+
|                        RoomAvailabilityMatrixService                              |
|  - getMatrixForDate($hotel, $date): Single-date snapshot calculation              |
|  - getMatrixForStayRange($hotel, $checkIn, $checkOut): Range Bottleneck analysis  |
|  - getQuantityDetails($hotel, $roomTypeId, $date): Ledger itemization             |
|  - realignChannelInventory($hotel, $channelId, $roomTypeId, $date, $actor): Sync  |
+---------------------+-------------------------------+-----------------------------+
                      |                               |
                      v                               v
+-------------------------------+   +-----------------------------------------------+
|      Central PMS Domain       |   |         Simulated OTA Partner Domain          |
|  - CentralInventory           |   |  - SimulatedPartnerProperty                   |
|  - RoomType                   |   |  - SimulatedPartnerRoomMapping                |
|  - Room (Physical units)      |   |  - SimulatedPartnerInventory                  |
|  - Reservation (Occupied)     |   |  - WebhookOutboxEvent (Pending sync traffic)  |
|  - InventoryLedger            |   +-----------------------------------------------+
+-------------------------------+
```

---

### 3. Core Principles & Non-Destructive Invariant

1. **Strictly Non-Destructive Queries**:
   - Page renders, AJAX matrix data requests, and quantity details calls **never** mutate, insert, or delete inventory rows.
   - If an inventory record does not yet exist for a future date, the system dynamically derives available capacity from physical active room counts minus reservations without inserting phantom records.
2. **Strict Multi-Tenant Isolation**:
   - All database queries, channel mappings, and partner inventories strictly filter by `hotel_id = $hotel->id`.
   - Managers and receptionists can only view and re-align inventory belonging to their authenticated property.
3. **Simulated Channel Transparency**:
   - Every OTA partner column is explicitly badged as **SIMULATED** to prevent confusion with production external OTA APIs.
   - Each channel queries its own persistent `SimulatedPartnerInventory` table. It does not blindly echo the PMS available number.
4. **Checkout Date Exclusion**:
   - In stay range evaluations, the checkout date is strictly treated as departure day and is **excluded** from occupied room-night calculations.

---

### 4. RBAC & Permissions Matrix

| Role | Access `/room-availability` | View Ledger Details | 1-Click Channel Re-alignment | CSV / Print Export |
|---|:---:|:---:|:---:|:---:|
| **Superadmin** | Yes | Yes | Yes | Yes |
| **Manager** | Yes | Yes | Yes | Yes |
| **Frontdesk / Receptionist** | Yes | Yes | No (Manager only) | Yes |
| **Housekeeping** | No (403) | No (403) | No (403) | No (403) |
| **Unauthenticated** | Redirect to Login | Redirect to Login | Redirect to Login | Redirect to Login |

---

### 5. UI/UX Interaction Design

- **KPI Header Cards**: Instant visual summary of Total Configured Room Types, PMS Available Units, Channel Discrepancy Count, and Critical Oversell Risks.
- **View A (Single Date)**:
  - Date selector with "Today", "+1 Day", "+7 Days" quick-nav buttons.
  - Room type rows with physical breakdown (Capacity, Reserved, Maintenance, Blocked, PMS Available).
  - Expandable Channel Allotment accordion showing each simulated partner's units, sync timestamp, and status.
- **View B (Multi-Night Stay Range)**:
  - Check-in and check-out date pickers with automatic night counter.
  - Night-by-night breakdown matrix showing availability for each individual date.
  - **Bottleneck Column**: Highlights the minimum available quantity across the stay and displays a green "FEASIBLE" or red "INSUFFICIENT" badge.
- **Quantity Inspector Drawer/Modal**:
  - Activated by clicking on any room type or channel figure.
  - Displays room-by-room status, active guest reservations for that night, maintenance holds, and outbox event audit trail.
  - Provides the 1-click **Realign with PMS** action with instant feedback.
