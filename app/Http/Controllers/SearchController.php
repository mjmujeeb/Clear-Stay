<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\IntegrationPartner;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Unified Global Command Palette Search (Ctrl+K / Cmd+K).
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => $this->getDefaultQuickLinks(),
            ]);
        }

        $results = [
            'pages' => [],
            'reservations' => [],
            'rooms' => [],
            'channels' => [],
            'partners' => [],
        ];

        // 1. Navigation Pages Search
        $allPages = [
            ['title' => 'Unified Overview (統合ダッシュボード)', 'url' => route('overview.index'), 'category' => 'Operations', 'icon' => 'fa-compass'],
            ['title' => 'Live Room Board (空室ボード)', 'url' => route('board.index'), 'category' => 'Operations', 'icon' => 'fa-border-all'],
            ['title' => 'Reservations Management (予約管理)', 'url' => route('reservations.index'), 'category' => 'Operations', 'icon' => 'fa-calendar-check'],
            ['title' => 'Channel Manager & Outbox (チャネル連携)', 'url' => route('channels.index'), 'category' => 'Distribution', 'icon' => 'fa-globe'],
            ['title' => 'Bulk Change Studio (一括更新スタジオ)', 'url' => route('bulk_editor.index'), 'category' => 'Distribution', 'icon' => 'fa-layer-group'],
            ['title' => 'Smart Rate Studio & Parity (料金管理)', 'url' => route('rates.index'), 'category' => 'Distribution', 'icon' => 'fa-tags'],
            ['title' => 'Responsive Experience Lab (レスポンシブ体験ラボ)', 'url' => route('experience_lab.index'), 'category' => 'QA & Testing', 'icon' => 'fa-microscope'],
            ['title' => 'Partner Assurance Lab (パートナー品質)', 'url' => route('partner_assurance.index'), 'category' => 'Partner Quality', 'icon' => 'fa-handshake'],
            ['title' => 'Partner Readiness Centre (接続準備レポート)', 'url' => route('readiness.index'), 'category' => 'Partner Quality', 'icon' => 'fa-certificate'],
            ['title' => 'EventTrace Hub (イベント追跡)', 'url' => route('event_trace.index'), 'category' => 'Observability', 'icon' => 'fa-wave-square'],
            ['title' => 'Security Intelligence & AI Governance', 'url' => route('security_governance.index'), 'category' => 'Security', 'icon' => 'fa-brain'],
            ['title' => 'Device Trust Hub (端末信頼性)', 'url' => route('device_trust.index'), 'category' => 'Security', 'icon' => 'fa-laptop-file'],
            ['title' => 'Staff Access Governance', 'url' => route('staff.index'), 'category' => 'Governance', 'icon' => 'fa-user-shield'],
            ['title' => 'Booking Analytics BA+ (予約分析)', 'url' => route('booking_analytics.index'), 'category' => 'Analytics', 'icon' => 'fa-chart-line'],
            ['title' => 'Housekeeping Operations', 'url' => route('housekeeping.index'), 'category' => 'Operations', 'icon' => 'fa-broom'],
            ['title' => 'System Health Check (稼働状態)', 'url' => route('health.index'), 'category' => 'System', 'icon' => 'fa-heart-pulse'],
        ];

        foreach ($allPages as $page) {
            if (stripos($page['title'], $query) !== false || stripos($page['category'], $query) !== false) {
                $results['pages'][] = $page;
            }
        }

        // 2. Reservations Search (Code, Guest Name, Email, Phone)
        $reservations = Reservation::with('guest')
            ->where('hotel_id', $hotelId)
            ->where(function ($q) use ($query) {
                $q->where('confirmation_code', 'LIKE', "%{$query}%")
                  ->orWhereHas('guest', function ($gq) use ($query) {
                      $gq->where('first_name', 'LIKE', "%{$query}%")
                         ->orWhere('last_name', 'LIKE', "%{$query}%")
                         ->orWhere('email', 'LIKE', "%{$query}%")
                         ->orWhere('phone', 'LIKE', "%{$query}%");
                  });
            })
            ->take(5)
            ->get();

        foreach ($reservations as $res) {
            $guestName = $res->guest ? ($res->guest->first_name . ' ' . $res->guest->last_name) : 'Guest';
            $results['reservations'][] = [
                'id' => $res->id,
                'title' => "{$res->confirmation_code} — {$guestName}",
                'subtitle' => "¥" . number_format($res->total_price) . " | {$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')} | Status: " . ucfirst($res->status),
                'url' => route('reservations.index') . "?search=" . urlencode($res->confirmation_code),
                'action' => "openReservationModal({$res->id})",
                'icon' => 'fa-user-tag',
            ];
        }

        // 3. Rooms Search
        $rooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->where('room_number', 'LIKE', "%{$query}%")
            ->take(5)
            ->get();

        foreach ($rooms as $room) {
            $typeName = $room->roomType ? $room->roomType->name : 'Standard';
            $results['rooms'][] = [
                'id' => $room->id,
                'title' => "Room {$room->room_number} ({$typeName})",
                'subtitle' => "Floor {$room->floor} | Status: {$room->status} | Occupied: " . ($room->is_occupied ? 'Yes' : 'No'),
                'url' => route('board.index'),
                'icon' => 'fa-door-open',
            ];
        }

        // 4. Channels Search
        $channels = Channel::where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->take(4)
            ->get();

        foreach ($channels as $chan) {
            $results['channels'][] = [
                'id' => $chan->id,
                'title' => $chan->name . " ({$chan->code})",
                'subtitle' => "Type: " . strtoupper($chan->type) . " | Status: " . ($chan->is_active ? 'Active' : 'Inactive'),
                'url' => route('channels.index'),
                'icon' => 'fa-network-wired',
            ];
        }

        // 5. Partners Search
        $partners = IntegrationPartner::where('name', 'LIKE', "%{$query}%")
            ->orWhere('company', 'LIKE', "%{$query}%")
            ->take(4)
            ->get();

        foreach ($partners as $partner) {
            $results['partners'][] = [
                'id' => $partner->id,
                'title' => $partner->name,
                'subtitle' => "Company: {$partner->company} | Category: {$partner->category} | Status: {$partner->status}",
                'url' => route('partner_assurance.show', $partner->id),
                'icon' => 'fa-handshake',
            ];
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Default quick links displayed when search query is empty.
     */
    protected function getDefaultQuickLinks(): array
    {
        return [
            'quick_actions' => [
                ['title' => 'Open Responsive Experience Lab', 'url' => route('experience_lab.index'), 'icon' => 'fa-microscope', 'badge' => 'NEW 3.0'],
                ['title' => 'Create New Reservation', 'url' => route('reservations.create'), 'icon' => 'fa-circle-plus', 'badge' => 'Action'],
                ['title' => 'Open Bulk Change Studio', 'url' => route('bulk_editor.index'), 'icon' => 'fa-layer-group', 'badge' => 'Studio'],
                ['title' => 'View Live Room Board', 'url' => route('board.index'), 'icon' => 'fa-border-all', 'badge' => 'Live'],
                ['title' => 'Partner Assurance & Certification', 'url' => route('partner_assurance.index'), 'icon' => 'fa-handshake', 'badge' => 'Lab'],
                ['title' => 'System Health Observability', 'url' => route('health.index'), 'icon' => 'fa-heart-pulse', 'badge' => 'Status'],
            ]
        ];
    }
}
