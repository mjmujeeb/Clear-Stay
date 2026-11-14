# ClearStay 4.2 — Real vs. Simulated Capabilities

**Document:** Capability Transparency Matrix  
**Author:** Antigravity AI Engineering  
**Application:** ClearStay Hospitality Engine (Version 4.2)  
**Date:** 2026-11-14  

---

## 1. Transparency Commitment

ClearStay is an enterprise hotel property management and channel distribution system. To ensure absolute transparency with clients, evaluators, and technical interviewers, this document details what features are **fully real and database-persisted**, what features are **statefully simulated**, and what features require **authorized live external partner credentials**.

**Core Principle:** *ClearStay never claims that a simulated partner connection is a real external production integration.*

---

## 2. Comprehensive Capability Matrix

| Feature / Integration Area | Real Backend Logic? | Database Persisted? | Simulated vs Live? | Actual Production Credentials Needed? | Notes & Technical Implementation |
|---|---|---|---|---|---|
| **Central Inventory Engine** | ✅ Yes | ✅ Yes (`central_inventories`) | **Real Core** | No | Authoritative source of truth for hotel room capacity. Enforces strict composite unique constraint on `(hotel_id, room_type_id, date)`. |
| **Bulk Change Studio** | ✅ Yes | ✅ Yes (`bulk_change_batches`) | **Real Core** | No | Generates non-mutating preview diffs, commits atomic multi-room updates, captures rollback snapshots, and dispatches outbox sync. |
| **Transactional Outbox Engine** | ✅ Yes | ✅ Yes (`sync_outbox_events`, `sync_attempt_logs`) | **Real Core** | No | Captures events transactionally, isolates per-channel delivery, logs attempt telemetry, and manages retry / dead-letter queues. |
| **Full Channel Audit** | ✅ Yes | ✅ Yes (`inventory_audit_runs`, `inventory_reconciliations`) | **Real Core** | No | Performs 14-day comparison between Central PMS and actual remote partner inventory tables, flagging overselling risks and quantity drifts. |
| **Booking.com Adapter** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for live B.XML / JSON API) | Simulates B.XML v2.4 protocol. Updates independent remote inventory table, validates payloads, and responds with realistic latency. |
| **Agoda Adapter** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for live Agoda YCS API) | Simulates YCS v3.1 JSON protocol, supports flash-sale rate schemas, and connects to Agoda Pay VCC settlement simulation. |
| **Expedia Adapter** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for live Partner Central API) | Simulates Partner Central v4.0. Validates room mappings and supports member deal restrictions. |
| **Rakuten Travel Adapter** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for live Rakuten NEOT API) | Simulates NEOT v2.0. Enforces whole-yen (integer JPY) rate constraints and Japanese OTA business rules. |
| **Jalan.net Adapter** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for live Jalan Web API) | Simulates Jalan Web API v3.2. Enforces strict ISO YYYY-MM-DD date validation and JPY rate constraints. |
| **Direct Booking Engine** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | No (Internal Engine) | Simulates direct commission-free bookings, loyalty member rates, and instant guest confirmations. |
| **Sakura PMS Connector** | ✅ Yes | ✅ Yes (`simulated_partner_*`) | **Stateful Simulator** | Yes (for external PMS installation) | Simulates PMS-to-CRS folio synchronization and version fencing against out-of-order packets. |
| **Inbound OTA Booking Webhook** | ✅ Yes | ✅ Yes (`simulated_partner_reservations`, `reservations`) | **Stateful Simulator** | No | Authenticates partner requests, checks idempotency keys, validates room mappings, checks capacity, creates reservations, and updates central inventory. |
| **Failure Injection Lab** | ✅ Yes | ✅ Yes (`simulated_failure_configs`) | **Real Testbed** | No | Forces real HTTP 400, 401, 403, 409, 429, 500, 504 status codes and network latency on selected partner adapters. |
| **Virtual Card Payment Terminal** | ✅ Yes | ✅ Yes (`payment_reconciliations`) | **Stateful Simulator** | Yes (for live NTT DATA / bank gateway) | Simulates credit card charges, refunds, partial refunds, declines, and timeouts with realistic terminal receipts. |
| **Real RFC 5545 iCalendar Feed** | ✅ Yes | ✅ Yes (Dynamic generation) | **Real Live Standard** | No | Generates valid RFC 5545 `.ics` calendar feed at `/ical/hotel/{hotel}/room/{roomType}/availability.ics` for real external calendar apps. |
| **AI Governance & Risk Heuristics**| ✅ Yes | ✅ Yes (`ai_governance_recommendations`)| **Deterministic Local Engine** | No (uses local heuristics) | Uses rule-based deterministic scoring and IEEE security standards. No cloud AI credentials required. |
| **Email Delivery** | ⚠️ Dev | ⚠️ Log File (`storage/logs/laravel.log`) | **Log Driver** | Yes (for live SMTP/SES delivery) | Configured with Laravel `log` transport. Emails are written to application logs for inspection during demonstration. |

---

## 3. Why This Simulation Architecture Matters

1. **True Discrepancy Detection:**
   When an interviewer clicks "Inject Agoda 504 Timeout" and pushes an inventory change from 5 to 2 units, Agoda's remote inventory remains at 5 units while other OTAs update to 2. Running the Full Channel Audit immediately shows a genuine database-backed discrepancy of -3 units on Agoda.
2. **True Idempotent Processing:**
   Replaying an inbound booking with the same idempotency key returns HTTP 200 without creating a duplicate PMS reservation or double-decrementing inventory.
3. **Safe Interviewer Demonstration:**
   Engineering interviewers can exercise the entire channel distribution lifecycle—from inventory change and outbox creation to delivery failure, retry, inbound booking, and full audit—completely self-contained on any standard workstation.
