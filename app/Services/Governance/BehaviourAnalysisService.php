<?php

namespace App\Services\Governance;

use App\Models\AuditLog;
use App\Models\ReservationChange;
use App\Models\SecurityIncident;
use App\Models\User;

class BehaviourAnalysisService
{
    /**
     * Inspect recent activities of a user and detect behavioural anomalies.
     */
    public function detectAnomalies(User $user): array
    {
        $hotelId = $user->hotel_id;
        $anomalies = [];

        // 1. Check for rapid reservation cancellations in the last 15 minutes
        $recentCancellations = ReservationChange::where('user_id', $user->id)
            ->where('reason', 'like', '%cancel%')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCancellations >= 3) {
            $anomalies[] = [
                'type' => 'rapid_cancellations',
                'description' => "User executed {$recentCancellations} reservation cancellations within 15 minutes.",
                'risk_points' => 35,
                'severity' => 'high',
            ];
        }

        // 2. Check for cross-hotel access attempts recorded in security incidents
        $crossHotelAttempts = SecurityIncident::where('event_type', 'like', '%cross_hotel%')
            ->where('created_at', '>=', now()->subHours(1))
            ->where('payload->user_id', $user->id)
            ->count();

        if ($crossHotelAttempts >= 1) {
            $anomalies[] = [
                'type' => 'cross_hotel_attempt',
                'description' => "Detected {$crossHotelAttempts} cross-property tenant isolation boundary violations.",
                'risk_points' => 45,
                'severity' => 'critical',
            ];
        }

        // 3. Check for repeated failed login attempts
        if ($user->failed_login_attempts >= 3) {
            $anomalies[] = [
                'type' => 'failed_logins',
                'description' => "Account has {$user->failed_login_attempts} consecutive failed authentication strikes.",
                'risk_points' => 25,
                'severity' => 'medium',
            ];
        }

        return $anomalies;
    }
}
