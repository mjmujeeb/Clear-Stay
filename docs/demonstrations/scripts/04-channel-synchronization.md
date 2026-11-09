# Demonstration 04: Bi-Directional Channel Synchronization & Outbox Queue

## Objective
Demonstrate reliable channel integration (Booking.com, Agoda, Expedia, Rakuten Travel) utilizing the Transactional Outbox pattern with exponential backoff and retry mechanisms.

## Walkthrough Steps
1. **Channel Manager View**: Inspect active OTA connectors and their health telemetry.
2. **Trigger Sync Event**: Push inventory modification to outbox queue.
3. **Outbox Inspector**: Examine queued sync jobs, payload structure, and delivery timestamps.
4. **Simulate Webhook / Inbound**: Process OTA booking notification and observe immediate allocation deduction.
