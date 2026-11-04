<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        $kpis = $hotelId ? $this->analyticsService->getKpis($hotelId) : [];

        return view('analytics.index', compact('hotel', 'kpis'));
    }

    public function occupancyData(Request $request): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $daysBack = $request->integer('days_back', 7);
        $daysForward = $request->integer('days_forward', 7);

        $data = $this->analyticsService->getOccupancyTrend($hotelId, $daysBack, $daysForward);

        return response()->json($data);
    }

    public function revenueData(Request $request): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $months = $request->integer('months', 6);

        $data = $this->analyticsService->getMonthlyRevenue($hotelId, $months);

        return response()->json($data);
    }

    public function distributionData(): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $data = $this->analyticsService->getRoomTypeDistribution($hotelId);

        return response()->json($data);
    }
}
