<?php

namespace App\Http\Controllers;

use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Get real-time status of the 7-step hotel setup onboarding checklist.
     */
    public function status(Request $request): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;

        $hasProperty = Hotel::where('id', $hotelId)->exists();
        $hasRoomTypes = RoomType::where('hotel_id', $hotelId)->exists();
        $hasInventory = CentralInventory::where('hotel_id', $hotelId)->exists();
        $hasRatePlans = RatePlan::where('hotel_id', $hotelId)->exists();
        $hasChannels = Channel::where('is_active', true)->exists();
        $hasReservations = Reservation::where('hotel_id', $hotelId)->exists();
        $hasAnalytics = session('analytics_reviewed', true);

        $steps = [
            [
                'id' => 1,
                'title' => 'Confirm Property Settings (施設基本設定)',
                'description' => 'Verify hotel address, currency (JPY), and master details.',
                'url' => route('overview.index'),
                'is_completed' => $hasProperty,
                'icon' => 'fa-hotel',
            ],
            [
                'id' => 2,
                'title' => 'Configure Room Categories (客室タイプ設定)',
                'description' => 'Set up Deluxe, King, and Suite room definitions with base pricing.',
                'url' => route('board.index'),
                'is_completed' => $hasRoomTypes,
                'icon' => 'fa-door-open',
            ],
            [
                'id' => 3,
                'title' => 'Set Initial Room Inventory (初期在庫の設定)',
                'description' => 'Seed central inventory across 90-day forward availability calendar.',
                'url' => route('bulk_editor.index'),
                'is_completed' => $hasInventory,
                'icon' => 'fa-layer-group',
            ],
            [
                'id' => 4,
                'title' => 'Review Rate Plans & Parity (料金プラン設定)',
                'description' => 'Configure standard and seasonal rate strategies.',
                'url' => route('rates.index'),
                'is_completed' => $hasRatePlans,
                'icon' => 'fa-tags',
            ],
            [
                'id' => 5,
                'title' => 'Configure Channel Connections (OTAチャネル連携)',
                'description' => 'Connect Booking.com, Agoda, and Expedia sandbox adapters.',
                'url' => route('channels.index'),
                'is_completed' => $hasChannels,
                'icon' => 'fa-globe',
            ],
            [
                'id' => 6,
                'title' => 'Create First Reservation (初期予約の登録)',
                'description' => 'Confirm pessimistic concurrency locking on guest bookings.',
                'url' => route('reservations.index'),
                'is_completed' => $hasReservations,
                'icon' => 'fa-calendar-check',
            ],
            [
                'id' => 7,
                'title' => 'Inspect Booking Analytics & BA+ (経営分析の確認)',
                'description' => 'Review occupancy curve, ADR, RevPAR, and pacing reports.',
                'url' => route('booking_analytics.index'),
                'is_completed' => $hasAnalytics,
                'icon' => 'fa-chart-line',
            ],
        ];

        $completedCount = count(array_filter($steps, fn($s) => $s['is_completed']));
        $isDismissed = session('onboarding_dismissed', false);

        $nextStep = null;
        foreach ($steps as $s) {
            if (!$s['is_completed']) {
                $nextStep = $s;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'completed_count' => $completedCount,
            'total_count' => count($steps),
            'progress_percent' => round(($completedCount / count($steps)) * 100),
            'is_dismissed' => $isDismissed,
            'next_step' => $nextStep,
            'steps' => $steps,
        ]);
    }

    /**
     * Dismiss onboarding widget.
     */
    public function dismiss(Request $request): JsonResponse
    {
        session(['onboarding_dismissed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding dismissed.',
        ]);
    }

    /**
     * Reset onboarding widget.
     */
    public function reset(Request $request): JsonResponse
    {
        session(['onboarding_dismissed' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reset.',
        ]);
    }
}
