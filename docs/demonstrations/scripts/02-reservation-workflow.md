# Demonstration 02: Transactional Reservation Lifecycle & Concurrency Protection

## Objective
Demonstrate creating, modifying, and managing hotel reservations with database-level row locking (SELECT ... FOR UPDATE) to prevent double-booking across simultaneous channel events.

## Walkthrough Steps
1. **Navigate to Reservations**: View active bookings table with guest names, check-in/out dates, and status badges.
2. **Create New Reservation**: Select guest, room type, date range, and assign rate plan.
3. **Transactional Commit**: Show atomic decrement of Central Inventory allocation across stay dates.
4. **Date Modification Modal**: Trigger date change modal, select alternative dates, and observe optimistic/pessimistic lock verification.
5. **Guest Portal Link**: Generate secure tokenized guest portal URL for contactless check-in.
