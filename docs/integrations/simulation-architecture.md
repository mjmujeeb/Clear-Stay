# ClearStay 4.2 — Simulation Architecture

**Document:** ClearStay 4.2 External Integration Simulation Architecture  
**Author:** Antigravity AI Engineering  
**Application:** ClearStay Hospitality Engine (Version 4.2)  
**Date:** 2026-11-14  

---

## 1. Architectural Philosophy

ClearStay 4.2 implements an **actual, stateful distribution simulator** rather than cosmetic front-end mocks. The simulator executes authentic backend business logic, records transactional outbox events, updates independent remote tables, handles incoming webhook bookings, and allows real-time fault injection.

```
+-----------------------------------------------------------------------------------+
|                            CLEARSTAY CORE HOSPITALITY PMS                        |
|                                                                                   |
|  [Central Inventory] <---> [Reservation Engine] <---> [Bulk Change Studio]        |
|          |                         |                          |                   |
|          +-------------------------+--------------------------+                   |
|                                    |                                              |
|                                    v                                              |
|                      [Transactional Outbox Pipeline]                              |
|                       (sync_outbox_events table)                                  |
|                                    |                                              |
+------------------------------------|----------------------------------------------+
                                     |
                       [Channel Synchronization Dispatcher]
                                     |
             +-----------------------+-----------------------+
             |                       |                       |
             v                       v                       v
     [Failure Injection]    [Integration Mode]       [EventTrace Hub]
      (400, 500, 504, 429)   (SIMULATOR/SANDBOX/LIVE) (Observability)
             |                       |                       |
             +-----------------------+-----------------------+
                                     |
    +--------------------------------+--------------------------------+
    |                                                                 |
    |                   SIMULATED PARTNER NETWORK                     |
    |                                                                 |
    |  +------------------+  +------------------+  +----------------+ |
    |  |  Booking.com     |  |  Agoda           |  |  Expedia       | |
    |  |  SIMULATED       |  |  SIMULATED       |  |  SIMULATED     | |
    |  |  (B.XML_v2.4)    |  |  (YCS_v3.1_JSON) |  |  (PartnerCent) | |
    |  +--------+---------+  +--------+---------+  +--------+-------+ |
    |           |                     |                     |         |
    |  +--------+---------+  +--------+---------+  +--------+-------+ |
    |  |  Rakuten Travel  |  |  Jalan.net       |  |  Direct Web    | |
    |  |  SIMULATED       |  |  SIMULATED       |  |  & Sakura PMS  | |
    |  |  (NEOT_v2.0)     |  |  (Jalan_Web_v3)  |  |  SIMULATED     | |
    |  +--------+---------+  +--------+---------+  +--------+-------+ |
    |           |                     |                     |         |
    +-----------|---------------------|---------------------|---------+
                |                     |                     |
                v                     v                     v
   +-------------------------------------------------------------------+
   |             INDEPENDENT SIMULATED REMOTE DATABASE TABLES          |
   |                                                                   |
   |   - simulated_partner_properties                                  |
   |   - simulated_partner_room_mappings                               |
   |   - simulated_partner_inventory (Distinct remote quantities)      |
   |   - simulated_partner_reservations (Inbound booking audit)        |
   |   - simulated_failure_configs                                     |
   |   - simulated_partner_requests (Telemetry logging)                |
   +-------------------------------------------------------------------+
                                     ^
                                     |
                        [Full Channel Audit Engine]
                  (Compares Central PMS vs Remote Tables)
```

---

## 2. Core Subsystems

### 2.1 Central Integration Mode System
The `IntegrationModeService` enforces strict operational boundaries across three environments:
1. **Mode A — SIMULATOR (Default):** Uses local stateful database models (`simulated_partner_*`). Zero external API credentials required.
2. **Mode B — SANDBOX:** Connects to certified partner staging endpoints when sandbox credentials are provided.
3. **Mode C — LIVE PRODUCTION:** Requires approved partner credentials and explicit environment authorization (`CLEARSTAY_LIVE_ALLOWED=true`). Cannot be activated silently or inadvertently.

### 2.2 The 7 Simulated Distribution Partners
Each simulator maintains independent identifiers, room mappings, currency rules, and protocol behaviors:

| Partner | Adapter Class | Protocol / Specification | Room Code Convention | Rate Constraints |
|---|---|---|---|---|
| **Booking.com** | `BookingComAdapter` | B.XML_v2.4_JSON | `BKG_RM_{CODE}` | Flexible rates, non-refundable, Genius promotions |
| **Agoda** | `AgodaAdapter` | Agoda_YCS_v3.1_JSON | `AGD_RM_{CODE}` | YCS Flash sales, VCC settlements |
| **Expedia** | `ExpediaAdapter` | Expedia_Partner_Central_v4.0 | `EXP_RM_{CODE}` | Package rates, member deals |
| **Rakuten Travel** | `RakutenAdapter` | Rakuten_NEOT_v2.0 | `RAK_RM_{CODE}` | JPY integer rates only (rejects floating currency) |
| **Jalan.net** | `JalanAdapter` | Jalan_Web_API_v3.2 | `JAL_RM_{CODE}` | JPY integer rates, Japanese date format validation |
| **Direct Web** | `DirectWebAdapter` | ClearStay_Direct_v1.0 | `DIR_RM_{CODE}` | Zero commission, loyalty member pricing |
| **Sakura PMS** | `SakuraPmsAdapter` | Sakura_PMS_OpenExchange_v2.1 | `SAK_RM_{CODE}` | Folio sync, housekeeping room-status bridge |

### 2.3 Independent Remote Inventory Storage
Unlike superficial UI mocks, changes to Central Inventory do not merely flip UI switches. Outbound pushes execute through the adapter and commit directly to `simulated_partner_inventory`:
- If an update succeeds: Remote inventory matches Central inventory.
- If an update fails (e.g., via injected HTTP 504 timeout): Remote inventory **retains its previous value**.
- The next execution of **Full Channel Audit** reads the actual remote records from `simulated_partner_inventory`, detecting genuine availability drift and highlighting critical overselling risks.

### 2.4 Inbound OTA Booking Webhook Pipeline
The `InboundBookingWebhookService` processes simulated reservations arriving from external distribution partners:
1. **Authentication:** Validates partner token / headers.
2. **Payload Validation:** Enforces ISO dates, required room codes, and price figures.
3. **Idempotency Gate:** Checks `idempotency_key` and `external_reservation_id`. Replays return HTTP 200 without creating duplicate bookings.
4. **Room Mapping Resolution:** Maps remote room codes to internal `RoomType` records.
5. **Capacity Verification:** Locks `CentralInventory` rows for the stay dates. Rejects bookings with HTTP 409 if availability is 0 or stop-sell is active.
6. **Transactional PMS Creation:** Creates `Guest` and `Reservation`, decrements `CentralInventory` availability, increments `reserved_units`, decrements partner remote inventory, and queues outbox broadcasts to other channels.

### 2.5 Failure Injection Lab
The `SimulatedFailureConfig` subsystem enables interactive fault engineering:
- Supports HTTP 400, 401, 403, 409, 429, 500, and 504.
- Injects configurable network latency (0 to 3000 ms).
- Simulates schema violations (malformed dates, unmapped rooms, negative units).
- Affects actual backend execution while isolating unaffected partners.

### 2.6 Real RFC 5545 iCalendar Provider
The `ICalendarController` publishes an authentic RFC 5545 calendar feed (`/ical/hotel/{hotel}/room/{roomType}/availability.ics`) with `VEVENT` blocks for closed dates and booked periods. Real calendar clients (Google Calendar, Apple Calendar, Airbnb, VRBO) can subscribe to this live endpoint.
