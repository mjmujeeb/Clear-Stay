# ClearStay 4.2 — Easy Mode Workflows & Operational Procedures

**Document Reference:** `docs/easy-mode/easy-mode-workflows.md`  
**Application Version:** ClearStay 4.2 Enterprise Hospitality Platform  
**Target Roles:** Front Desk Receptionists, Housekeepers, Operations Supervisors  

---

## 1. Workflow 1: 5-Step Guided Guest Check-In Wizard

### Objective:
Enable any receptionist or volunteer front-desk staff to safely check in a guest in under 30 seconds without misassigning a room, double-assigning an occupied room, or skipping keycard issuance.

### Execution Steps:
1. **Access Wizard:** Navigate to `/easy-mode/checkin-wizard` or click **「本日の到着ゲストをチェックイン」** on the Easy Mode home screen.
2. **Step 1 — Search Guest:**
   - Type guest name, phone number, or confirmation code into the large search box (with live debounce AJAX search).
   - Alternatively, tap any guest from the **本日の到着予定 (Today's Arrivals)** list.
3. **Step 2 — Confirm Reservation Details:**
   - Review guest name, booked dates, room type, total price, and payment status.
   - Verify that the status is `confirmed` (un-checked-in).
4. **Step 3 — Select Clean & Available Room:**
   - Visual cards display all available rooms (`status = clean` and `is_occupied = false`).
   - If a room was already pre-assigned, it is highlighted with a green checkmark.
   - Tap to change or confirm the room assignment.
5. **Step 4 — Prepare Keycards & Verification:**
   - Select number of keycards issued (1 to 4 tactile pill buttons).
   - Optionally enter or verify Passport / ID number for international guests.
   - Add any special front-desk notes (e.g., extra towel requested).
6. **Step 5 — Complete Check-In Transaction:**
   - Tap **「チェックインを確定する (Complete Check-In)」**.
   - Concurrency Safety: Database transaction executes `SELECT ... FOR UPDATE` on both reservation and room records.
   - Double-submission guard: Button disables instantly and displays loading spinner.
   - Immediate Handover Display: Big green card instructs staff:
     *「お部屋番号 {room_number} の鍵 ({keycard_count}枚) をお渡しください」* (Hand over {keycard_count} room key(s) for Room {room_number}).

---

## 2. Workflow 2: Express Checkout & Housekeeping Turnover Generation

### Objective:
Complete guest checkout in one tap, release room occupancy immediately, and automatically notify housekeeping by generating a high-priority turnover cleaning task.

### Execution Steps:
1. Navigate to `/easy-mode/departures` or tap **「チェックアウト処理」** on the home screen.
2. The system displays all checked-in guests scheduled for departure today.
3. Tap **「チェックアウト完了」** for the departing room.
4. The system executes a database transaction:
   - Sets reservation status to `checked_out` and logs `checked_out_at = now()`.
   - Sets room `is_occupied = false` and `status = 'dirty'`.
   - Generates a `HousekeepingTask` record (`type = 'turnover'`, `priority = 'high'`, `status = 'pending'`).
   - Creates an `InventoryLedgerEntry` recording the departure.
5. A green confirmation banner notifies the receptionist that the room is dirty and queued for housekeeping.

---

## 3. Workflow 3: Housekeeping Cleanliness & Maintenance Updates

### Objective:
Allow housekeeping staff on mobile devices or tablets to view their daily cleaning list and update room status with large, tactile buttons.

### Execution Steps:
1. Navigate to `/easy-mode/rooms` or tap **「お部屋の状態・清掃管理」**.
2. Filter rooms by status tabs:
   - **清掃待ち (Dirty)**
   - **清掃中 (In Progress)**
   - **点検待ち (Inspecting)**
   - **販売可能 (Clean & Ready)**
   - **故障・修繕中 (Maintenance)**
3. On any room tile, tap:
   - **「清掃完了 (Mark Clean)」** -> Sets status to `clean`.
   - **「点検依頼 (Request Inspection)」** -> Sets status to `inspecting`.
   - **「メンテナンス中 (Flag Maintenance)」** -> Sets status to `maintenance`.
4. Updates immediately reflect across the application, including the Room Board, Front Desk Arrivals, and Standard Mode.

---

## 4. Workflow 4: New Reservation Creation

### Objective:
Create an in-person walk-in or telephone reservation without leaving Easy Mode.

### Execution Steps:
1. Navigate to `/easy-mode/create` or tap **「新しい予約を登録」**.
2. **Step 1:** Select Check-in and Check-out dates using large date selectors.
3. **Step 2:** Select an available room with real-time base pricing.
4. **Step 3:** Enter guest full name and phone number.
5. Tap **「予約を確定する」**.
6. The system generates a guest record, assigns the room, generates a `CS-EZ-XXXXXX` confirmation code, and sets status to `confirmed`.
