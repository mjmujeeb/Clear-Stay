<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Hotel;
use App\Models\RateParityObservation;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Services\Pricing\RateEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RateStudioController extends Controller
{
    protected RateEngineService $rateEngineService;

    public function __construct(RateEngineService $rateEngineService)
    {
        $this->rateEngineService = $rateEngineService;
    }

    /**
     * Rate Studio: Pricing plans, Japanese calendar events, and parity monitor.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();

        $ratePlans = RatePlan::where('hotel_id', $hotelId)->with('roomType')->get();
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();

        // Japanese Calendar Events
        $calendarEvents = CalendarEvent::orderBy('start_date')->get();

        // Rate Parity Monitor observations
        $parityObservations = RateParityObservation::where('hotel_id', $hotelId)
            ->with(['channel', 'roomType'])
            ->latest()
            ->take(12)
            ->get();

        // Sample dynamic suggestion calculation
        $today = Carbon::today()->toDateString();
        $sampleSuggestions = [];
        if ($roomTypes->isNotEmpty()) {
            $sampleType = $roomTypes->first();
            for ($i = 0; $i < 7; $i++) {
                $checkDate = Carbon::today()->addDays($i)->toDateString();
                $sampleSuggestions[] = $this->rateEngineService->computeSuggestedRate($hotelId, $sampleType->id, $checkDate);
            }
        }

        return view('rates.index', compact(
            'hotel',
            'ratePlans',
            'roomTypes',
            'calendarEvents',
            'parityObservations',
            'sampleSuggestions'
        ));
    }

    /**
     * Create a new rate plan.
     */
    public function storePlan(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:rate_plans,code',
            'room_type_id' => 'nullable|exists:room_types,id',
            'base_price' => 'required|numeric|min:0',
            'pricing_model' => 'required|in:flat,occupancy_based,seasonal',
            'cancellation_policy' => 'required|string',
            'meal_plan' => 'required|string',
        ]);

        RatePlan::create([
            'hotel_id' => $hotelId,
            'room_type_id' => $request->room_type_id,
            'name' => $request->name,
            'code' => $request->code,
            'base_price' => $request->base_price,
            'pricing_model' => $request->pricing_model,
            'cancellation_policy' => $request->cancellation_policy,
            'meal_plan' => $request->meal_plan,
            'is_active' => true,
        ]);

        return back()->with('success', 'Rate plan created successfully.');
    }

    /**
     * Trigger fresh Rate Parity scan across connected channels.
     */
    public function scanParity(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        $date = Carbon::today()->addDays(3)->toDateString();

        foreach ($roomTypes as $type) {
            $this->rateEngineService->recordParityObservations($hotelId, $type->id, $date, $type->base_price);
        }

        return back()->with('success', "Rate parity scan completed across all active channels for target date {$date}.");
    }
}
