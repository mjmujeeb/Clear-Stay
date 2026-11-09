<?php

namespace App\Services\Governance;

use App\Models\SecurityRiskEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;

class RiskAssessmentService
{
    protected BehaviourAnalysisService $behaviourService;

    public function __construct(BehaviourAnalysisService $behaviourService)
    {
        $this->behaviourService = $behaviourService;
    }

    /**
     * Compute a transparent, explainable 0-100 risk score for a requested action.
     */
    public function assessRequestRisk(User $user, string $action, ?Request $request = null): array
    {
        $score = 5; // baseline low risk for authenticated user
        $factors = [];

        // Signal 1: New / unverified device
        if ($request) {
            $ua = $request->userAgent() ?? '';
            $deviceIdentifier = hash('sha256', $user->id . ':' . $ua);
            $device = TrustedDevice::where('user_id', $user->id)->where('device_identifier', $deviceIdentifier)->first();

            if (!$device || $device->trust_status === 'new') {
                $score += 20;
                $factors[] = 'Accessing from an unrecognized/new browser device (+20 pts)';
            } elseif ($device->trust_status === 'revoked') {
                $score += 60;
                $factors[] = 'Device is in revoked/compromised state (+60 pts)';
            }
        }

        // Signal 2: Authentication strikes
        if ($user->failed_login_attempts > 0) {
            $penalty = min(30, $user->failed_login_attempts * 10);
            $score += $penalty;
            $factors[] = "Recent failed login attempts detected ({$user->failed_login_attempts} strikes: +{$penalty} pts)";
        }

        // Signal 3: Behavioural anomalies
        $anomalies = $this->behaviourService->detectAnomalies($user);
        foreach ($anomalies as $anomaly) {
            $score += $anomaly['risk_points'];
            $factors[] = "Behavioural Anomaly: {$anomaly['description']} (+{$anomaly['risk_points']} pts)";
        }

        // Cap at 100
        $finalScore = min(100, $score);

        // Determine band
        if ($finalScore >= 75) {
            $band = 'critical';
            $actionTaken = 'auto_block';
        } elseif ($finalScore >= 50) {
            $band = 'high';
            $actionTaken = 'require_approval';
        } elseif ($finalScore >= 25) {
            $band = 'medium';
            $actionTaken = 'require_mfa';
        } else {
            $band = 'low';
            $actionTaken = 'allow';
        }

        // Record risk event if elevated or anomalous
        if ($finalScore >= 25) {
            SecurityRiskEvent::create([
                'hotel_id' => $user->hotel_id,
                'user_id' => $user->id,
                'event_type' => $action,
                'risk_score' => $finalScore,
                'risk_band' => $band,
                'contributing_factors' => $factors,
                'action_taken' => $actionTaken,
                'is_reviewed' => false,
            ]);
        }

        return [
            'risk_score' => $finalScore,
            'risk_band' => $band,
            'contributing_factors' => $factors,
            'recommended_action' => $actionTaken,
        ];
    }
}
