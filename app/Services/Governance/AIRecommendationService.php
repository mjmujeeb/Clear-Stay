<?php

namespace App\Services\Governance;

use App\Models\AiGovernanceRecommendation;
use App\Models\SecurityIncident;
use App\Models\SecurityRiskEvent;
use App\Models\User;

class AIRecommendationService
{
    /**
     * Inspect recent security events and generate advisory governance recommendations.
     */
    public function generateSecurityRecommendations(int $hotelId): array
    {
        $recommendations = [];

        // Check for users with excessive permissions (e.g. Front desk users having rate bulk edit or management permissions)
        $frontDeskUsers = User::where('hotel_id', $hotelId)
            ->whereIn('role', ['frontdesk', 'receptionist'])
            ->with('userPermissions.permission')
            ->get();

        foreach ($frontDeskUsers as $user) {
            $hasHighPrivilege = $user->userPermissions->filter(function ($up) {
                return $up->access_type === 'allow' && in_array($up->permission->name, ['rates.bulk_edit', 'governance.manage_employees']);
            })->first();

            if ($hasHighPrivilege) {
                $rec = AiGovernanceRecommendation::firstOrCreate(
                    [
                        'hotel_id' => $hotelId,
                        'user_id' => $user->id,
                        'category' => 'excessive_permissions',
                    ],
                    [
                        'title' => "Permission Audit Advisory: Excessive Privilege for {$user->name}",
                        'recommendation_text' => "Employee has role '{$user->role}' but holds active individual override for '{$hasHighPrivilege->permission->name}'. Recommend manager review and revoke if unneeded.",
                        'severity' => 'warning',
                        'status' => 'pending_review',
                        'metadata' => [
                            'permission' => $hasHighPrivilege->permission->name,
                            'role' => $user->role,
                        ],
                    ]
                );
                $recommendations[] = $rec;
            }
        }

        // Check for recent elevated risk events
        $highRiskEvents = SecurityRiskEvent::where('hotel_id', $hotelId)
            ->whereIn('risk_band', ['high', 'critical'])
            ->where('is_reviewed', false)
            ->latest()
            ->take(3)
            ->get();

        foreach ($highRiskEvents as $risk) {
            $rec = AiGovernanceRecommendation::firstOrCreate(
                [
                    'hotel_id' => $hotelId,
                    'user_id' => $risk->user_id,
                    'category' => 'risk_analysis',
                ],
                [
                    'title' => "Threat Advisory: Elevated Risk ({$risk->risk_score}/100) on '{$risk->event_type}'",
                    'recommendation_text' => "AI pattern evaluation flagged {$risk->risk_band} risk factors. Recommended action: verify user device registration and require re-authentication.",
                    'severity' => $risk->risk_band === 'critical' ? 'critical' : 'warning',
                    'status' => 'pending_review',
                    'metadata' => [
                        'risk_event_id' => $risk->id,
                        'score' => $risk->risk_score,
                    ],
                ]
            );
            $recommendations[] = $rec;
        }

        return $recommendations;
    }
}
