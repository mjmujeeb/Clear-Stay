# ClearStay 5.0 — Pricing Rule Calculation & Stacking Specification

## 1. Stay Period & Occupied Night Enumeration

In hotel management, reservations charge per room night stayed. The check-out date represents the guest's departure morning and must never be calculated as an occupied night.

### Formal Stay Period
For check-in date $D_{\text{in}}$ and check-out date $D_{\text{out}}$ where $D_{\text{in}} < D_{\text{out}}$:
$$\text{Stay Set} = \{ D_k \mid D_{\text{in}} \le D_k < D_{\text{out}} \}$$
$$\text{Nights Count } N = |\text{Stay Set}| = \text{diffInDays}(D_{\text{in}}, D_{\text{out}})$$

*Example:* Check-in `2026-04-28`, Check-out `2026-05-03`:
- Night 1: `2026-04-28` (Tuesday)
- Night 2: `2026-04-29` (Wednesday, Showa Day / Golden Week)
- Night 3: `2026-04-30` (Thursday)
- Night 4: `2026-05-01` (Friday, Weekend + Golden Week)
- Night 5: `2026-05-02` (Saturday, Weekend + Golden Week)
- `2026-05-03`: Check-out day (¥0 charged)
- Total Nights: 5

---

## 2. Nightly Rule Resolution

For each date $D_k \in \text{Stay Set}$ and Room Type with base price $B$:

### 2.1 Weekend Rule
- Checked against `hotel_pricing_settings.weekend_days` (default: Friday `5` and Saturday `6`).
- If $D_k.\text{dayOfWeek} \in \text{weekend\_days}$:
  $$p_{\text{weekend}} = \text{weekend\_increase\_percentage} \quad (\text{default } +15.0\%)$$

### 2.2 Japanese National Holidays & Special Events
- Matched where `calendar_events.is_active = true` and `start_date <= D_k <= end_date`.
- Verified statutory holidays for 2025–2027 seeded in ClearStay database:
  - New Year's Day (元日): +30%
  - Coming of Age Day (成人の日): +20%
  - National Foundation Day (建国記念の日): +20%
  - Emperor's Birthday (天皇誕生日): +25%
  - Vernal Equinox Day (春分の日): +20%
  - Golden Week (ゴールデンウィーク): +35%
  - Marine Day (海の日): +20%
  - Mountain Day (山の日): +25%
  - Obon Festival (お盆特別期間): +40%
  - Respect for the Aged Day (敬老の日): +20%
  - Autumnal Equinox Day (秋分の日): +20%
  - Sports Day (スポーツの日): +20%
  - Culture Day (文化の日): +20%
  - Labor Thanksgiving Day (勤労感謝の日): +20%
  - Year-End Peak (年末年始): +45%

### 2.3 Manager Seasonal Rules
- Matched where `pricing_rules.is_active = true`, `start_date <= D_k <= end_date`, and `(room_type_id IS NULL OR room_type_id = RT)`.
- If `weekdays` JSON filter is specified, only applies on matching days.
- Percentage adjustment $p_{\text{seasonal}}$ and fixed adjustment $f_{\text{seasonal}}$.

---

## 3. Stacking Policy Formulations

Let $\{p_1, p_2, \dots, p_m\}$ be all percentage adjustments applicable on date $D_k$.
Let $\{f_1, f_2, \dots, f_n\}$ be all fixed adjustments applicable on date $D_k$, with $F = \sum_{j=1}^n f_j$.

### 3.1 Additive Stacking (Default)
Each percentage applies directly to the base price $B$:
$$\text{Rate}_{\text{raw}} = B \times \left(1 + \sum_{i=1}^m \frac{p_i}{100}\right) + F$$

*Example:* Base $B = ¥10,000$, Weekend $+15\%$, Golden Week $+35\%$:
$$\text{Rate}_{\text{raw}} = 10000 \times (1 + 0.15 + 0.35) = 10000 \times 1.50 = ¥15,000$$

### 3.2 Multiplicative Stacking
Compounding percentage adjustments:
$$\text{Rate}_{\text{raw}} = \left(B \times \prod_{i=1}^m \left(1 + \frac{p_i}{100}\right)\right) + F$$

*Example:* Base $B = ¥10,000$, Weekend $+15\%$, Culture Day $+20\%$:
$$\text{Rate}_{\text{raw}} = 10000 \times 1.15 \times 1.20 = 10000 \times 1.38 = ¥13,800$$

### 3.3 Highest-Only Stacking
Only the highest single rule percentage applies:
$$p_{\max} = \max(\{0\} \cup \{p_1, \dots, p_m\})$$
$$\text{Rate}_{\text{raw}} = B \times \left(1 + \frac{p_{\max}}{100}\right) + F$$

*Example:* Base $B = ¥10,000$, Weekend $+15\%$, Holiday $+25\%$:
$$\text{Rate}_{\text{raw}} = 10000 \times (1 + \max(0.15, 0.25)) = 10000 \times 1.25 = ¥12,500$$

---

## 4. Rounding Policies

Applied to $\text{Rate}_{\text{raw}}$:
- **`whole_yen` (Default in Japan):**
  $$\text{Rate} = \text{round}(\text{Rate}_{\text{raw}}, 0)$$
- **`nearest_100`:**
  $$\text{Rate} = \text{round}(\text{Rate}_{\text{raw}}, -2)$$
- **`none`:**
  $$\text{Rate} = \text{round}(\text{Rate}_{\text{raw}}, 2)$$

Safety lower bound: $\text{Rate} = \max(0.00, \text{Rate})$.

---

## 5. Tax Calculation (Japanese Consumption Tax Standard)

### 5.1 Tax Included (総額表示 - Japanese Standard)
The calculated sum of nightly rates is the grand total. The tax amount is extracted:
$$\text{Grand Total} = \sum_{k=1}^N \text{Rate}_k$$
$$\text{Tax Amount} = \text{Grand Total} - \left(\frac{\text{Grand Total}}{1 + \frac{\text{Tax \%}}{100}}\right)$$

### 5.2 Tax Exclusive
$$\text{Subtotal} = \sum_{k=1}^N \text{Rate}_k$$
$$\text{Tax Amount} = \text{Subtotal} \times \left(\frac{\text{Tax \%}}{100}\right)$$
$$\text{Grand Total} = \text{Subtotal} + \text{Tax Amount}$$
