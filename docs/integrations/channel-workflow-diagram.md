# ClearStay 4.2 — Channel Workflow Diagram

**Document:** Distribution Architecture & Simulation Workflow  
**Author:** Antigravity AI Engineering  
**Application:** ClearStay Hospitality Engine (Version 4.2)  
**Date:** 2026-11-14  

---

## 1. Outbound Inventory & Rate Synchronization Workflow

```mermaid
sequenceDiagram
    autonumber
    actor Manager as Hotel Revenue Manager
    participant PMS as Central Inventory Engine
    participant Outbox as Transactional Outbox (DB)
    participant Dispatcher as Channel Sync Dispatcher
    participant FaultLab as Failure Injection Lab
    participant Adapter as Simulated Partner Adapter
    participant RemoteDB as Simulated Remote DB
    participant Audit as Full Channel Audit

    Manager->>PMS: Updates room availability (5 -> 3 units)
    PMS->>PMS: Validate bounds & commit CentralInventory atomically
    PMS->>Outbox: Insert SyncOutboxEvent per connected channel (status: pending)
    PMS-->>Manager: Acknowledges update committed on PMS
    
    loop For each connected channel (Booking, Agoda, Expedia, etc.)
        Dispatcher->>Outbox: Fetch pending outbox event
        Dispatcher->>FaultLab: Check active failure rules for partner
        alt Fault Active (e.g., Agoda HTTP 504 Timeout)
            FaultLab-->>Dispatcher: Return simulated 504 Gateway Timeout
            Dispatcher->>Outbox: Update attempt log (HTTP 504, status: failed)
            Dispatcher->>Outbox: Schedule exponential backoff retry
            Note over RemoteDB: Agoda remote inventory stays at 5 units (DRIFT)
        else Normal Operation (e.g., Booking.com HTTP 200)
            Dispatcher->>Adapter: Invoke pushInventory(payload)
            Adapter->>RemoteDB: Update simulated_partner_inventory (3 units)
            Adapter-->>Dispatcher: Acknowledged (HTTP 200, REF-ACK-12345)
            Dispatcher->>Outbox: Mark outbox event confirmed
        end
    end

    Manager->>Audit: Run Full Channel Audit
    Audit->>PMS: Read CentralInventory for next 14 days
    Audit->>RemoteDB: Query simulated_partner_inventory across all channels
    Audit-->>Manager: Displays drift report: Booking=MATCH (3), Agoda=DRIFT (5 vs 3)
```

---

## 2. Inbound OTA Booking Webhook Workflow

```mermaid
sequenceDiagram
    autonumber
    actor OTA as Simulated OTA Partner (e.g. Agoda)
    participant Webhook as Inbound Webhook Endpoint
    participant Idem as Idempotency Gate
    participant RoomMap as Room Mapping Resolver
    participant Capacity as Capacity & Central Inventory
    participant ResEngine as ClearStay PMS Reservation Engine
    participant Cascade as Outbox Cascade Broadcaster
    participant OtherOTAs as Other Channel Adapters (Expedia, Booking, etc.)

    OTA->>Webhook: POST /api/simulators/agoda/inbound-reservation
    Webhook->>Idem: Check idempotency_key & external_reservation_id
    alt Duplicate Event Detected
        Idem-->>OTA: HTTP 200 (duplicate_acknowledged, no double booking)
    else First-time Delivery
        Webhook->>RoomMap: Resolve remote_room_code to internal RoomType
        alt Unmapped Code
            RoomMap-->>OTA: HTTP 422 UNMAPPED_ROOM_CODE
        else Mapping Valid
            Webhook->>Capacity: Check availability & stop-sell for stay dates
            alt Zero Capacity / Stop-Sell Active
                Capacity-->>OTA: HTTP 409 CAPACITY_EXHAUSTED
            else Capacity Available
                Webhook->>ResEngine: Begin DB Transaction
                ResEngine->>ResEngine: Create Guest & Reservation records
                ResEngine->>Capacity: Decrement available_units (e.g. 4 -> 3)
                ResEngine->>Capacity: Increment reserved_units (e.g. 1 -> 2)
                ResEngine->>Cascade: Queue outbox sync to OTHER channels
                ResEngine-->>Webhook: Commit DB Transaction
                Webhook-->>OTA: HTTP 201 Created (confirmation_code: CS-A-9F7821)
                
                par Cascade Updates to Other Channels
                    Cascade->>OtherOTAs: Broadcast remaining availability (3 units)
                end
            end
        end
    end
```

---

## 3. Discrepancy Detection & Realignment Lifecycle

```mermaid
flowchart TD
    A["Operator Changes Availability on PMS"] --> B["Transactional Outbox Dispatches to Simulators"]
    B --> C{"Failure Injected on Channel?"}
    C -- "Yes (HTTP 504 / 500 / 429)" --> D["Outbox Event Marked Failed"]
    D --> E["Remote Simulator Inventory Stays at Old Count"]
    C -- "No (HTTP 200)" --> F["Remote Simulator Inventory Updated"]
    
    E --> G["Full Channel Audit Executed"]
    F --> G
    
    G --> H{"Does Remote Units == Central Units?"}
    H -- "Yes" --> I["Status: in_sync (Green)"]
    H -- "Remote > Central" --> J["Status: oversell_risk (CRITICAL Red)"]
    H -- "Remote < Central" --> K["Status: discrepancy (Amber)"]
    
    J --> L["Click 'Resolve & Realign'"]
    K --> L
    L --> M["Push Central Inventory Authoritatively"]
    M --> N["Remote Simulator Inventory Aligned to PMS"]
    N --> O["Audit Re-run: 100% in_sync Parity Restored"]
```
