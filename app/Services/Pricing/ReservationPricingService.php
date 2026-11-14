<?php

namespace App\Services\Pricing;

use App\Models\CalendarEvent;
use App\Models\HotelPricingSetting;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\ReservationPricingSnapshot;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class ReservationPricingService
{
    /**
     * Day of week map for Japanese localization.
     */
    protected const JA_DAYS = [
        0 => '日',
        1 => '月',
        2 => '火',
        3 => '水',
        4 => '木',
        5 => '金',
        6 => '土',
    ];

    /**
     * Calculate an authoritative, date-aware hotel reservation quote with nightly breakdown.
     *
     * @param int $hotelId
     * @param int $roomTypeId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $roomId
     * @param int|null $excludeReservationId
     * @param array|null $overrideSettings (for manager simulation)
     * @return array
     * @throws InvalidArgumentException
     */
    public function calculateQuote(
        int $hotelId,
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        ?int $roomId = null,
        ?int $excludeReservationId = null,
        ?array $overrideSettings = null
    ): array {
        $checkIn = Carbon::parse($checkInDate)->startOfDay();
        $checkOut = Carbon::parse($checkOutDate)->startOfDay();

        if ($checkIn->gte($checkOut)) {
            throw new InvalidArgumentException(__('clearstay.errors.checkout_after_checkin'));
        }

        $roomType = RoomType::findOrFail($roomTypeId);
        $baseRate = (float) $roomType->base_price;

        // Resolve hotel pricing settings or defaults
        $settings = $this->resolveSettings($hotelId, $overrideSettings);

        $stackingPolicy = $settings['stacking_policy']; // additive, multiplicative, highest_only
        $weekendDays = $settings['weekend_days']; // e.g. [5, 6] (Friday, Saturday)
        $weekendPercentage = (float) $settings['weekend_increase_percentage'];
        $roundingPolicy = $settings['rounding_policy']; // whole_yen, nearest_100, none
        $taxPercentage = (float) $settings['tax_percentage'];
        $taxIncluded = (bool) $settings['tax_included'];
        $currency = $settings['currency'] ?? 'JPY';

        // Check availability if roomId is supplied
        $availability = $this->checkRoomAvailability($roomId, $checkIn->toDateString(), $checkOut->toDateString(), $excludeReservationId);

        // Calculate nightly breakdown: check-in date up to (but not including) check-out date
        $occupiedPeriod = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $nightlyBreakdown = [];
        $baseTotal = 0.0;
        $surchargeTotal = 0.0;
        $discountTotal = 0.0;
        $subtotal = 0.0;

        foreach ($occupiedPeriod as $currentDate) {
            $dateString = $currentDate->toDateString();
            $dayOfWeek = $currentDate->dayOfWeek; // 0=Sun, 1=Mon, ..., 5=Fri, 6=Sat
            $dayName = $currentDate->format('D');
            $dayNameJa = self::JA_DAYS[$dayOfWeek] ?? '';

            $appliedRules = [];
            $percentageAdjustments = [];
            $fixedAdjustments = [];

            // 1. Weekend Rule
            $isWeekend = in_array($dayOfWeek, $weekendDays);
            if ($isWeekend && $weekendPercentage != 0.0) {
                $percentageAdjustments[] = $weekendPercentage;
                $appliedRules[] = [
                    'rule_type' => 'weekend',
                    'name' => 'Weekend Rate (' . $dayName . ')',
                    'name_ja' => '週末料金 (' . $dayNameJa . '曜日)',
                    'percentage' => $weekendPercentage,
                    'fixed' => 0.0,
                    'badge' => 'Weekend +' . $weekendPercentage . '%',
                    'badge_color' => 'amber',
                ];
            }

            // 2. Japanese National Holidays & Special Events
            $events = CalendarEvent::where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->get();

            foreach ($events as $event) {
                $pct = (float) ($event->percentage_adjustment ?? 0.0);
                if ($pct == 0.0 && $event->demand_multiplier > 1.0) {
                    $pct = round(($event->demand_multiplier - 1.0) * 100, 2);
                }
                $fixed = (float) ($event->fixed_surcharge ?? 0.0);

                if ($pct != 0.0) {
                    $percentageAdjustments[] = $pct;
                }
                if ($fixed != 0.0) {
                    $fixedAdjustments[] = $fixed;
                }

                $badgeLabel = ($pct >= 0 ? '+' : '') . $pct . '%';
                if ($fixed > 0) {
                    $badgeLabel .= ' (+' . number_format($fixed) . ' ' . $currency . ')';
                }

                $appliedRules[] = [
                    'rule_type' => $event->event_type === 'holiday' ? 'national_holiday' : 'special_event',
                    'name' => $event->title,
                    'name_ja' => $event->title_ja ?: $event->title,
                    'percentage' => $pct,
                    'fixed' => $fixed,
                    'badge' => ($event->title_ja ?: $event->title) . ' ' . $badgeLabel,
                    'badge_color' => $event->event_type === 'holiday' ? 'red' : 'purple',
                ];
            }

            // 3. Manager Seasonal Pricing Rules
            $seasonalRules = PricingRule::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->where(function ($q) use ($roomTypeId) {
                    $q->whereNull('room_type_id')->orWhere('room_type_id', $roomTypeId);
                })
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->orderBy('priority', 'desc')
                ->get();

            foreach ($seasonalRules as $rule) {
                // If weekdays restriction is specified, verify current day
                if (!empty($rule->weekdays) && is_array($rule->weekdays)) {
                    if (!in_array($dayOfWeek, $rule->weekdays)) {
                        continue;
                    }
                }

                $pct = (float) $rule->percentage_adjustment;
                $fixed = (float) $rule->fixed_adjustment;

                if ($pct != 0.0) {
                    $percentageAdjustments[] = $pct;
                }
                if ($fixed != 0.0) {
                    $fixedAdjustments[] = $fixed;
                }

                $isDiscount = $pct < 0 || $fixed < 0;
                $badgeLabel = ($pct >= 0 ? '+' : '') . $pct . '%';
                if ($fixed != 0.0) {
                    $badgeLabel .= ' (' . ($fixed >= 0 ? '+' : '') . number_format($fixed) . ' ' . $currency . ')';
                }

                $appliedRules[] = [
                    'rule_type' => $rule->rule_type,
                    'name' => $rule->rule_name,
                    'name_ja' => $rule->rule_name_ja ?: $rule->rule_name,
                    'percentage' => $pct,
                    'fixed' => $fixed,
                    'badge' => ($rule->rule_name_ja ?: $rule->rule_name) . ' ' . $badgeLabel,
                    'badge_color' => $isDiscount ? 'emerald' : 'blue',
                ];
            }

            // Compute Nightly Rate according to Stacking Policy
            $rawNightlyRate = $this->applyStackingPolicy(
                $baseRate,
                $percentageAdjustments,
                $fixedAdjustments,
                $stackingPolicy
            );

            // Apply Rounding Policy
            $nightlyRate = $this->applyRounding($rawNightlyRate, $roundingPolicy);
            $nightlyRate = max(0.0, $nightlyRate); // Safety: non-negative

            $diffFromBase = $nightlyRate - $baseRate;
            $surchargeAmount = $diffFromBase > 0 ? $diffFromBase : 0.0;
            $discountAmount = $diffFromBase < 0 ? abs($diffFromBase) : 0.0;

            $baseTotal += $baseRate;
            $surchargeTotal += $surchargeAmount;
            $discountTotal += $discountAmount;
            $subtotal += $nightlyRate;

            $nightlyBreakdown[] = [
                'date' => $dateString,
                'day_of_week' => $dayName,
                'day_name_ja' => $dayNameJa,
                'is_weekend' => $isWeekend,
                'base_rate' => $baseRate,
                'applied_rules' => $appliedRules,
                'surcharge_amount' => round($surchargeAmount, 2),
                'discount_amount' => round($discountAmount, 2),
                'nightly_rate' => round($nightlyRate, 2),
            ];
        }

        $nightsCount = count($nightlyBreakdown);

        // Tax calculation
        if ($taxIncluded) {
            // Price already includes tax (e.g. 10% JPY consumption tax)
            $taxTotal = $taxPercentage > 0
                ? round($subtotal - ($subtotal / (1 + ($taxPercentage / 100))), 2)
                : 0.0;
            $grandTotal = $subtotal;
        } else {
            // Tax exclusive: tax is added on top of subtotal
            $taxTotal = round($subtotal * ($taxPercentage / 100), 2);
            $grandTotal = $subtotal + $taxTotal;
        }

        return [
            'hotel_id' => $hotelId,
            'room_type_id' => $roomTypeId,
            'room_type_name' => $roomType->name,
            'room_type_name_ja' => $roomType->name_ja ?? $roomType->name,
            'room_id' => $roomId,
            'is_available' => $availability['is_available'],
            'conflicting_code' => $availability['conflicting_code'],
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'nights_count' => $nightsCount,
            'currency' => $currency,
            'base_rate_per_night' => $baseRate,
            'base_total' => round($baseTotal, 2),
            'surcharge_total' => round($surchargeTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'subtotal' => round($subtotal, 2),
            'tax_percentage' => $taxPercentage,
            'tax_included' => $taxIncluded,
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($grandTotal, 2),
            'stacking_policy' => $stackingPolicy,
            'rounding_policy' => $roundingPolicy,
            'nightly_breakdown' => $nightlyBreakdown,
            'has_dynamic_adjustments' => $surchargeTotal > 0 || $discountTotal > 0,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Compute rate using configured stacking policy.
     */
    protected function applyStackingPolicy(
        float $baseRate,
        array $percentageAdjustments,
        array $fixedAdjustments,
        string $policy
    ): float {
        $totalFixed = array_sum($fixedAdjustments);

        if (empty($percentageAdjustments)) {
            return $baseRate + $totalFixed;
        }

        switch ($policy) {
            case 'multiplicative':
                // Base * (1 + p1/100) * (1 + p2/100) ... + sum(fixed)
                $multiplier = 1.0;
                foreach ($percentageAdjustments as $pct) {
                    $multiplier *= (1.0 + ($pct / 100.0));
                }
                return ($baseRate * $multiplier) + $totalFixed;

            case 'highest_only':
                // Base * (1 + max(p)/100) + sum(fixed)
                $maxPct = max($percentageAdjustments);
                return ($baseRate * (1.0 + ($maxPct / 100.0))) + $totalFixed;

            case 'additive':
            default:
                // Base * (1 + sum(p)/100) + sum(fixed)
                $totalPct = array_sum($percentageAdjustments);
                return ($baseRate * (1.0 + ($totalPct / 100.0))) + $totalFixed;
        }
    }

    /**
     * Apply rounding policy to calculated rate.
     */
    protected function applyRounding(float $amount, string $policy): float
    {
        switch ($policy) {
            case 'nearest_100':
                return round($amount, -2);
            case 'none':
                return round($amount, 2);
            case 'whole_yen':
            default:
                return round($amount, 0);
        }
    }

    /**
     * Resolve hotel settings with sensible defaults or optional overrides.
     */
    protected function resolveSettings(int $hotelId, ?array $overrideSettings = null): array
    {
        $dbSettings = HotelPricingSetting::where('hotel_id', $hotelId)->first();

        $defaults = [
            'stacking_policy' => 'additive',
            'weekend_days' => [5, 6], // Friday & Saturday
            'weekend_increase_percentage' => 15.00,
            'rounding_policy' => 'whole_yen',
            'currency' => 'JPY',
            'tax_percentage' => 10.00,
            'tax_included' => true,
        ];

        if ($dbSettings) {
            $defaults['stacking_policy'] = $dbSettings->stacking_policy;
            $defaults['weekend_days'] = $dbSettings->weekend_days ?? [5, 6];
            $defaults['weekend_increase_percentage'] = (float) $dbSettings->weekend_increase_percentage;
            $defaults['rounding_policy'] = $dbSettings->rounding_policy;
            $defaults['currency'] = $dbSettings->currency;
            $defaults['tax_percentage'] = (float) $dbSettings->tax_percentage;
            $defaults['tax_included'] = (bool) $dbSettings->tax_included;
        }

        if ($overrideSettings) {
            return array_merge($defaults, array_filter($overrideSettings, fn ($v) => $v !== null));
        }

        return $defaults;
    }

    /**
     * Check if a specific room is available for given dates.
     */
    public function checkRoomAvailability(?int $roomId, string $checkInDate, string $checkOutDate, ?int $excludeReservationId = null): array
    {
        if (!$roomId) {
            return ['is_available' => true, 'conflicting_code' => null];
        }

        $query = Reservation::withoutGlobalScopes()
            ->where('room_id', $roomId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in_date', '<', $checkOutDate)
            ->whereDate('check_out_date', '>', $checkInDate);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        $conflict = $query->first();

        return [
            'is_available' => $conflict === null,
            'conflicting_code' => $conflict ? $conflict->confirmation_code : null,
        ];
    }

    /**
     * Persist an immutable pricing snapshot for a reservation.
     */
    public function createPricingSnapshot(Reservation $reservation, array $quote): ReservationPricingSnapshot
    {
        return ReservationPricingSnapshot::create([
            'hotel_id' => $reservation->hotel_id,
            'reservation_id' => $reservation->id,
            'currency' => $quote['currency'] ?? 'JPY',
            'base_total' => $quote['base_total'],
            'surcharge_total' => $quote['surcharge_total'],
            'discount_total' => $quote['discount_total'],
            'tax_total' => $quote['tax_total'],
            'grand_total' => $quote['grand_total'],
            'pricing_rule_version' => '5.0-' . $quote['stacking_policy'],
            'nightly_breakdown' => $quote['nightly_breakdown'],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Update or append a pricing snapshot when reservation dates/room change.
     */
    public function updatePricingSnapshot(Reservation $reservation, array $quote): ReservationPricingSnapshot
    {
        return ReservationPricingSnapshot::create([
            'hotel_id' => $reservation->hotel_id,
            'reservation_id' => $reservation->id,
            'currency' => $quote['currency'] ?? 'JPY',
            'base_total' => $quote['base_total'],
            'surcharge_total' => $quote['surcharge_total'],
            'discount_total' => $quote['discount_total'],
            'tax_total' => $quote['tax_total'],
            'grand_total' => $quote['grand_total'],
            'pricing_rule_version' => '5.0-' . $quote['stacking_policy'] . '-updated',
            'nightly_breakdown' => $quote['nightly_breakdown'],
            'calculated_at' => now(),
        ]);
    }
}
