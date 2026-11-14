<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Hotel;
use App\Models\HotelPricingSetting;
use App\Models\PricingAuditLog;
use App\Models\PricingRule;
use App\Models\RoomType;
use App\Services\Pricing\ReservationPricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingSettingsController extends Controller
{
    protected ReservationPricingService $pricingService;

    public function __construct(ReservationPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Show Hotel Pricing Rules & Calendar Management Dashboard.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);
        if (!$hotel) {
            $hotel = Hotel::where('is_active', true)->first();
            if ($hotel) {
                session(['current_hotel_id' => $hotel->id]);
                $hotelId = $hotel->id;
            }
        }

        // 1. Hotel pricing settings
        $settings = HotelPricingSetting::firstOrCreate(
            ['hotel_id' => $hotelId],
            [
                'stacking_policy' => 'additive',
                'weekend_days' => [5, 6],
                'weekend_increase_percentage' => 15.00,
                'rounding_policy' => 'whole_yen',
                'currency' => 'JPY',
                'tax_percentage' => 10.00,
                'tax_included' => true,
            ]
        );

        // 2. Room types with base prices
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();

        // 3. Calendar events & Japanese holidays
        $events = CalendarEvent::where(function ($q) use ($hotelId) {
            $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
        })
            ->orderBy('start_date', 'asc')
            ->get();

        // 4. Seasonal & custom pricing rules
        $rules = PricingRule::with('roomType')
            ->where('hotel_id', $hotelId)
            ->orderBy('priority', 'desc')
            ->orderBy('start_date', 'asc')
            ->get();

        // 5. Audit logs
        $auditLogs = PricingAuditLog::with('user')
            ->where('hotel_id', $hotelId)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return view('pricing.settings', compact(
            'hotel',
            'settings',
            'roomTypes',
            'events',
            'rules',
            'auditLogs'
        ));
    }

    /**
     * Update room type base price.
     */
    public function updateBasePrice(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'base_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $roomType = RoomType::where('hotel_id', $hotelId)->findOrFail($validated['room_type_id']);
        $oldPrice = (float) $roomType->base_price;
        $newPrice = (float) $validated['base_price'];

        $roomType->base_price = $newPrice;
        $roomType->save();

        PricingAuditLog::create([
            'hotel_id' => $hotelId,
            'user_id' => Auth::id(),
            'action' => 'update_base_price',
            'target_type' => RoomType::class,
            'target_id' => $roomType->id,
            'old_values' => ['base_price' => $oldPrice],
            'new_values' => ['base_price' => $newPrice],
            'reason' => $validated['reason'] ?? 'Manager updated base price for ' . $roomType->name,
        ]);

        return redirect()->route('pricing_settings.index')
            ->with('success', __('clearstay.pricing.base_price_updated', [
                'type' => $roomType->name,
                'price' => number_format($newPrice)
            ]));
    }

    /**
     * Update weekend rules configuration.
     */
    public function updateWeekendRules(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $validated = $request->validate([
            'weekend_days' => 'required|array|min:1',
            'weekend_days.*' => 'integer|between:0,6',
            'weekend_increase_percentage' => 'required|numeric|min:0|max:100',
            'reason' => 'nullable|string|max:255',
        ]);

        $settings = HotelPricingSetting::where('hotel_id', $hotelId)->firstOrFail();
        $oldValues = [
            'weekend_days' => $settings->weekend_days,
            'weekend_increase_percentage' => $settings->weekend_increase_percentage,
        ];

        $settings->weekend_days = array_map('intval', $validated['weekend_days']);
        $settings->weekend_increase_percentage = (float) $validated['weekend_increase_percentage'];
        $settings->save();

        PricingAuditLog::create([
            'hotel_id' => $hotelId,
            'user_id' => Auth::id(),
            'action' => 'update_weekend_rate',
            'target_type' => HotelPricingSetting::class,
            'target_id' => $settings->id,
            'old_values' => $oldValues,
            'new_values' => [
                'weekend_days' => $settings->weekend_days,
                'weekend_increase_percentage' => $settings->weekend_increase_percentage,
            ],
            'reason' => $validated['reason'] ?? 'Manager updated weekend pricing parameters',
        ]);

        return redirect()->route('pricing_settings.index')
            ->with('success', __('clearstay.pricing.weekend_rules_updated'));
    }

    /**
     * Create or update holiday / calendar event.
     */
    public function storeOrUpdateEvent(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $validated = $request->validate([
            'id' => 'nullable|exists:calendar_events,id',
            'title' => 'required|string|max:150',
            'title_ja' => 'nullable|string|max:150',
            'event_type' => 'required|in:holiday,event,festival',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'percentage_adjustment' => 'required|numeric|min:0|max:200',
            'fixed_surcharge' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        if (!empty($validated['id'])) {
            $event = CalendarEvent::findOrFail($validated['id']);
            $oldValues = $event->toArray();
            $event->update([
                'title' => $validated['title'],
                'title_ja' => !empty($validated['title_ja']) ? $validated['title_ja'] : $validated['title'],
                'event_type' => $validated['event_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'percentage_adjustment' => $validated['percentage_adjustment'],
                'demand_multiplier' => round(1.0 + ($validated['percentage_adjustment'] / 100), 2),
                'fixed_surcharge' => $validated['fixed_surcharge'] ?? 0.00,
                'is_active' => $isActive,
                'notes' => $validated['notes'] ?? null,
            ]);

            PricingAuditLog::create([
                'hotel_id' => $hotelId,
                'user_id' => Auth::id(),
                'action' => 'update_holiday_event',
                'target_type' => CalendarEvent::class,
                'target_id' => $event->id,
                'old_values' => $oldValues,
                'new_values' => $event->toArray(),
                'reason' => 'Manager updated calendar event: ' . $event->title,
            ]);

            $message = __('clearstay.pricing.event_updated', ['name' => $event->title]);
        } else {
            $event = CalendarEvent::create([
                'hotel_id' => $hotelId,
                'title' => $validated['title'],
                'title_ja' => !empty($validated['title_ja']) ? $validated['title_ja'] : $validated['title'],
                'event_type' => $validated['event_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'percentage_adjustment' => $validated['percentage_adjustment'],
                'demand_multiplier' => round(1.0 + ($validated['percentage_adjustment'] / 100), 2),
                'fixed_surcharge' => $validated['fixed_surcharge'] ?? 0.00,
                'is_active' => $isActive,
                'notes' => $validated['notes'] ?? null,
            ]);

            PricingAuditLog::create([
                'hotel_id' => $hotelId,
                'user_id' => Auth::id(),
                'action' => 'create_holiday_event',
                'target_type' => CalendarEvent::class,
                'target_id' => $event->id,
                'old_values' => null,
                'new_values' => $event->toArray(),
                'reason' => 'Manager created new calendar event: ' . $event->title,
            ]);

            $message = __('clearstay.pricing.event_created', ['name' => $event->title]);
        }

        return redirect()->route('pricing_settings.index')->with('success', $message);
    }

    /**
     * Delete calendar event.
     */
    public function deleteEvent(int $id)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $event = CalendarEvent::where('hotel_id', $hotelId)->findOrFail($id);

        $oldValues = $event->toArray();
        $title = $event->title;
        $event->delete();

        PricingAuditLog::create([
            'hotel_id' => $hotelId,
            'user_id' => Auth::id(),
            'action' => 'delete_holiday_event',
            'target_type' => CalendarEvent::class,
            'target_id' => $id,
            'old_values' => $oldValues,
            'new_values' => null,
            'reason' => 'Manager deleted calendar event: ' . $title,
        ]);

        return redirect()->route('pricing_settings.index')
            ->with('success', __('clearstay.pricing.event_deleted', ['name' => $title]));
    }

    /**
     * Create or update seasonal pricing rule.
     */
    public function storeOrUpdateSeasonalRule(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $validated = $request->validate([
            'id' => 'nullable|exists:pricing_rules,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'rule_name' => 'required|string|max:150',
            'rule_name_ja' => 'nullable|string|max:150',
            'rule_type' => 'required|in:seasonal,promotion,special_event,custom_discount',
            'percentage_adjustment' => 'required|numeric|between:-90,300',
            'fixed_adjustment' => 'nullable|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'integer|between:0,6',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        if (!empty($validated['id'])) {
            $rule = PricingRule::where('hotel_id', $hotelId)->findOrFail($validated['id']);
            $oldValues = $rule->toArray();

            $rule->update([
                'room_type_id' => !empty($validated['room_type_id']) ? $validated['room_type_id'] : null,
                'rule_name' => $validated['rule_name'],
                'rule_name_ja' => !empty($validated['rule_name_ja']) ? $validated['rule_name_ja'] : $validated['rule_name'],
                'rule_type' => $validated['rule_type'],
                'percentage_adjustment' => $validated['percentage_adjustment'],
                'fixed_adjustment' => $validated['fixed_adjustment'] ?? 0.00,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'weekdays' => !empty($validated['weekdays']) ? array_map('intval', $validated['weekdays']) : null,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => $isActive,
                'description' => $validated['description'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            PricingAuditLog::create([
                'hotel_id' => $hotelId,
                'user_id' => Auth::id(),
                'action' => 'update_seasonal_rule',
                'target_type' => PricingRule::class,
                'target_id' => $rule->id,
                'old_values' => $oldValues,
                'new_values' => $rule->toArray(),
                'reason' => 'Manager updated seasonal pricing rule: ' . $rule->rule_name,
            ]);

            $message = __('clearstay.pricing.rule_updated', ['name' => $rule->rule_name]);
        } else {
            $rule = PricingRule::create([
                'hotel_id' => $hotelId,
                'room_type_id' => !empty($validated['room_type_id']) ? $validated['room_type_id'] : null,
                'rule_name' => $validated['rule_name'],
                'rule_name_ja' => !empty($validated['rule_name_ja']) ? $validated['rule_name_ja'] : $validated['rule_name'],
                'rule_type' => $validated['rule_type'],
                'percentage_adjustment' => $validated['percentage_adjustment'],
                'fixed_adjustment' => $validated['fixed_adjustment'] ?? 0.00,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'weekdays' => !empty($validated['weekdays']) ? array_map('intval', $validated['weekdays']) : null,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => $isActive,
                'description' => $validated['description'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            PricingAuditLog::create([
                'hotel_id' => $hotelId,
                'user_id' => Auth::id(),
                'action' => 'create_seasonal_rule',
                'target_type' => PricingRule::class,
                'target_id' => $rule->id,
                'old_values' => null,
                'new_values' => $rule->toArray(),
                'reason' => 'Manager created new seasonal pricing rule: ' . $rule->rule_name,
            ]);

            $message = __('clearstay.pricing.rule_created', ['name' => $rule->rule_name]);
        }

        return redirect()->route('pricing_settings.index')->with('success', $message);
    }

    /**
     * Delete seasonal pricing rule.
     */
    public function deleteSeasonalRule(int $id)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $rule = PricingRule::where('hotel_id', $hotelId)->findOrFail($id);

        $oldValues = $rule->toArray();
        $name = $rule->rule_name;
        $rule->delete();

        PricingAuditLog::create([
            'hotel_id' => $hotelId,
            'user_id' => Auth::id(),
            'action' => 'delete_seasonal_rule',
            'target_type' => PricingRule::class,
            'target_id' => $id,
            'old_values' => $oldValues,
            'new_values' => null,
            'reason' => 'Manager deleted seasonal rule: ' . $name,
        ]);

        return redirect()->route('pricing_settings.index')
            ->with('success', __('clearstay.pricing.rule_deleted', ['name' => $name]));
    }

    /**
     * Update stacking and rounding policies.
     */
    public function updatePolicy(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $validated = $request->validate([
            'stacking_policy' => 'required|in:additive,multiplicative,highest_only',
            'rounding_policy' => 'required|in:whole_yen,nearest_100,none',
            'tax_percentage' => 'required|numeric|min:0|max:50',
            'tax_included' => 'nullable|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $settings = HotelPricingSetting::where('hotel_id', $hotelId)->firstOrFail();
        $oldValues = [
            'stacking_policy' => $settings->stacking_policy,
            'rounding_policy' => $settings->rounding_policy,
            'tax_percentage' => $settings->tax_percentage,
            'tax_included' => $settings->tax_included,
        ];

        $settings->stacking_policy = $validated['stacking_policy'];
        $settings->rounding_policy = $validated['rounding_policy'];
        $settings->tax_percentage = (float) $validated['tax_percentage'];
        $settings->tax_included = $request->has('tax_included');
        $settings->save();

        PricingAuditLog::create([
            'hotel_id' => $hotelId,
            'user_id' => Auth::id(),
            'action' => 'update_stacking_policy',
            'target_type' => HotelPricingSetting::class,
            'target_id' => $settings->id,
            'old_values' => $oldValues,
            'new_values' => [
                'stacking_policy' => $settings->stacking_policy,
                'rounding_policy' => $settings->rounding_policy,
                'tax_percentage' => $settings->tax_percentage,
                'tax_included' => $settings->tax_included,
            ],
            'reason' => $validated['reason'] ?? 'Manager updated pricing calculation policies',
        ]);

        return redirect()->route('pricing_settings.index')
            ->with('success', __('clearstay.pricing.policy_updated'));
    }

    /**
     * Simulate price quote for testing rules and stacking policies.
     */
    public function simulateQuote(Request $request): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        if (!$hotelId) {
            $firstHotel = Hotel::where('is_active', true)->first();
            $hotelId = $firstHotel ? $firstHotel->id : 1;
        }

        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'override_stacking_policy' => 'nullable|in:additive,multiplicative,highest_only',
            'override_weekend_percentage' => 'nullable|numeric|min:0|max:100',
            'override_rounding_policy' => 'nullable|in:whole_yen,nearest_100,none',
        ]);

        $overrideSettings = null;
        if (!empty($validated['override_stacking_policy']) || isset($validated['override_weekend_percentage']) || !empty($validated['override_rounding_policy'])) {
            $overrideSettings = [
                'stacking_policy' => $validated['override_stacking_policy'] ?? null,
                'weekend_increase_percentage' => isset($validated['override_weekend_percentage']) ? (float) $validated['override_weekend_percentage'] : null,
                'rounding_policy' => $validated['override_rounding_policy'] ?? null,
            ];
        }

        try {
            $quote = $this->pricingService->calculateQuote(
                $hotelId,
                $validated['room_type_id'],
                $validated['check_in_date'],
                $validated['check_out_date'],
                null,
                null,
                $overrideSettings
            );

            return response()->json([
                'success' => true,
                'quote' => $quote,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
