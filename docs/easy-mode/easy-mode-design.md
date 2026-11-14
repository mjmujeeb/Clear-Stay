# ClearStay 4.2 — Easy Mode Design System & Philosophy

**Document Reference:** `docs/easy-mode/easy-mode-design.md`  
**Brand Identity:** ClearStay Easy Mode — Simple Hotel Operations / ClearStay かんたん操作  
**Primary Users:** Hotel receptionists, housekeeping staff, elderly/senior employees, non-technical hospitality staff  
**Version:** ClearStay 4.2  

---

## 1. Design Vision & Guiding Principles

ClearStay Easy Mode is an alternative, full-fidelity operational interface designed for hospitality professionals who need maximum clarity, tactile comfort, and zero cognitive friction.

Rather than a dumbed-down or cosmetic view, Easy Mode is a **streamlined, senior-friendly operational interface** connecting directly to the same underlying transactional services, database models, and inventory ledgers as Standard Professional Mode.

### Design Principles:
1. **Apple-like Clarity:** Generous white space, obvious visual hierarchy, clear color semantics, and unambiguous interactive elements.
2. **Airbnb-like Warmth & Approachability:** Friendly time-aware greetings ("おはようございます", "Good afternoon"), human-centered guest summaries, and reassuring success confirmations.
3. **Linear-like Interaction Reliability:** Instant state feedback, deterministic task recommendations, keyboard accessible shortcuts, and zero unexpected screen jumps.
4. **Japanese Hospitality Standards (Omotenashi):** Polished bilingual typography (Noto Sans JP + Plus Jakarta Sans), polite Japanese messaging, and clear keycard handover instructions.
5. **High Accessibility & Senior Comfort (WCAG 2.2 AA):**
   - Minimum button / touch target height: **48px to 56px** with ample padding.
   - Base typography: **18px to 20px**, with an interactive zoom scaler (Standard, Large, Extra Large).
   - High contrast colors: Text-to-background contrast ratio $\ge 4.5:1$ (exceeding WCAG AA).
   - Clear focus rings and keyboard navigability.
   - Persistent first-time onboarding guide with one-touch dismissal remembered across sessions.

---

## 2. Color Palette & Typography

| Token | Value | Semantic Use | Contrast Ratio |
| :--- | :--- | :--- | :--- |
| `--easy-navy` | `#0F1D2F` | Primary text, titles, deep contrast headers | 14.8:1 on `#FFFFFF` |
| `--easy-teal` | `#1F6B66` | Primary action buttons, active navigation, focus rings | 5.2:1 on `#FFFFFF` |
| `--easy-green` | `#047857` | Arrival badges, clean room status, success alerts | 4.8:1 on `#FFFFFF` |
| `--easy-blue` | `#1D4ED8` | Departures, checked-in status, secondary actions | 6.1:1 on `#FFFFFF` |
| `--easy-amber` | `#B45309` | Dirty rooms, pending turnovers, warnings | 5.1:1 on `#FFFFFF` |
| `--easy-red` | `#B91C1C` | Cancellations, maintenance alerts, error states | 5.3:1 on `#FFFFFF` |
| `--easy-card` | `#FFFFFF` | Tile backgrounds, form containers | - |
| `--easy-bg` | `#F0F4F8` | Page background providing soft, glare-free contrast | - |

---

## 3. Architecture of Workspaces

Easy Mode divides daily hotel operations into four primary tactile workspaces:

1. **Operations Hub (`/easy-mode`):**
   - Time-aware greeting & date bar.
   - Top status counters: Arrivals, Departures, Rooms to Clean, Ready Rooms.
   - **Deterministic "Next Recommended Task"** widget: rules engine pointing to the single most urgent priority.
   - Role-Tailored action tiles: Receptionist, Housekeeping, Manager.
   - Persistent "操作ガイド (Guide)" onboarding modal.
2. **5-Step Check-In Wizard (`/easy-mode/checkin-wizard`):**
   - Step 1: Search guest or pick from today's arrivals.
   - Step 2: Confirm reservation details & pricing.
   - Step 3: Select clean & vacant room with visual card grid.
   - Step 4: Verify guest identity & select keycards (1 to 4).
   - Step 5: Concurrency-safe check-in completion with double-click prevention and handover card.
3. **Interactive Departures & Express Checkout (`/easy-mode/departures`):**
   - Today's departures list.
   - Folio review & 1-click checkout transaction.
   - Auto-triggers housekeeping turnover task and inventory ledger entry.
4. **Housekeeping & Room Status Workspace (`/easy-mode/rooms`):**
   - Grouped cards: Dirty, Cleaning, Inspecting, Clean, Maintenance.
   - 1-Touch status transition buttons.
   - Real-time synchronization with standard Room Board.
5. **Interactive Booking Workspace (`/easy-mode/create`):**
   - Progressive 3-step reservation creation with date pickers, room selection, and live price calculation.
