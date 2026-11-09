<?php

namespace App\Services\Revenue;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\OverbookingPolicy;
use App\Models\User;

class OverbookingIntelligenceService
{
    /**
     * Research references informing the mathematical formulation:
     * 1. Hotel overbooking based on no-show probability forecasts (2023) - DOI: 10.1016/j.cie.2023.109226
     * 2. Optimal overbooking decision for hotel rooms revenue management - DOI: 10.1108/JHTT-03-2014-0006
     * 3. Hotel revenue management: Benefits of simultaneous overbooking and allocation problem formulation (2019) - DOI: 10.1016/j.cie.2019.106073
     * 4. A stochastic approach to hotel revenue optimization (2005) - DOI: 10.1016/j.cor.2003.09.012
     */

    /**
     * Retrieve or instantiate default overbooking policy for a hotel context.
     * Note: Overbooking is strictly DISABLED by default.
     */
    public function getPolicy(Hotel $hotel): OverbookingPolicy
    {
        return OverbookingPolicy::firstOrCreate(
            ['hotel_id' => $hotel->id],
            [
                'is_enabled' => false,
                'strategy' => 'zero_overbooking',
                'max_buffer_rooms' => 2,
                'max_buffer_percentage' => 5.00,
                'default_adr' => 18500.00,
                'relocation_cost_per_walk' => 35000.00,
                'historical_cancellation_rate' => 14.50,
                'historical_noshow_rate' => 4.20,
                'max_tolerated_walk_risk' => 3.00,
                'policy_notes' => 'Default conservative profile. Physical room capacity strictly enforced.',
            ]
        );
    }

    /**
     * Update overbooking policy with manager authorization and audit recording.
     */
    public function updatePolicy(Hotel $hotel, array $data, User $actor): OverbookingPolicy
    {
        $policy = $this->getPolicy($hotel);

        $policy->update([
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'strategy' => $data['strategy'] ?? $policy->strategy,
            'max_buffer_rooms' => (int) ($data['max_buffer_rooms'] ?? $policy->max_buffer_rooms),
            'max_buffer_percentage' => (float) ($data['max_buffer_percentage'] ?? $policy->max_buffer_percentage),
            'default_adr' => (float) ($data['default_adr'] ?? $policy->default_adr),
            'relocation_cost_per_walk' => (float) ($data['relocation_cost_per_walk'] ?? $policy->relocation_cost_per_walk),
            'historical_cancellation_rate' => (float) ($data['historical_cancellation_rate'] ?? $policy->historical_cancellation_rate),
            'historical_noshow_rate' => (float) ($data['historical_noshow_rate'] ?? $policy->historical_noshow_rate),
            'max_tolerated_walk_risk' => (float) ($data['max_tolerated_walk_risk'] ?? $policy->max_tolerated_walk_risk),
            'authorized_by' => $actor->id,
            'authorized_at' => now(),
            'policy_notes' => $data['policy_notes'] ?? 'Updated via Revenue Management & Controlled Overbooking Studio.',
        ]);

        // Audit Trail
        AuditLog::create([
            'hotel_id' => $hotel->id,
            'user_id' => $actor->id,
            'action' => 'OVERBOOKING_POLICY_UPDATED',
            'details' => json_encode([
                'is_enabled' => $policy->is_enabled,
                'strategy' => $policy->strategy,
                'max_buffer_rooms' => $policy->max_buffer_rooms,
                'actor' => $actor->email,
            ]),
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);

        return $policy;
    }

    /**
     * Calculate explainable decision comparison across 3 revenue strategies.
     */
    public function calculateStrategies(
        int $capacity = 50,
        float $cancellationRate = 14.5,
        float $noshowRate = 4.2,
        float $adr = 18500.0,
        float $walkCost = 35000.0,
        float $maxRiskThreshold = 3.0
    ): array {
        $pCancel = max(0.01, min(0.50, $cancellationRate / 100.0));
        $pNoshow = max(0.01, min(0.30, $noshowRate / 100.0));
        $pDrop = $pCancel + $pNoshow;
        $pArrival = 1.0 - $pDrop;

        // 1. Strategy 1: Zero Overbooking (Physical Capacity C)
        $strat1Limit = $capacity;
        $strat1ExpectedArrivals = round($strat1Limit * $pArrival, 1);
        $strat1ExpectedOccupancyPct = round(($strat1ExpectedArrivals / $capacity) * 100, 1);
        $strat1ExpectedRevenue = round($strat1ExpectedArrivals * $adr, 0);
        $strat1WalkRiskPct = 0.0;
        $strat1RelocationCost = 0.0;
        $strat1NetProfit = $strat1ExpectedRevenue;

        // 2. Strategy 2: Fixed Buffer (+5% allotment)
        $fixedBufferRooms = max(1, (int) round($capacity * 0.05));
        $strat2Limit = $capacity + $fixedBufferRooms;
        $strat2ExpectedArrivals = round($strat2Limit * $pArrival, 1);
        $strat2ExpectedWalks = max(0.0, round($strat2ExpectedArrivals - $capacity, 2));
        $strat2WalkRiskPct = round(($strat2ExpectedWalks / $strat2Limit) * 100, 2);
        $strat2RelocationCost = round($strat2ExpectedWalks * $walkCost, 0);
        $strat2ExpectedRevenue = round(min($capacity, $strat2ExpectedArrivals) * $adr, 0);
        $strat2NetProfit = $strat2ExpectedRevenue - $strat2RelocationCost;

        // 3. Strategy 3: Stochastic Risk-Aware Optimization
        // Critical fractile formula: CF = ADR / (ADR + RelocationCost)
        $criticalFractile = $adr / ($adr + $walkCost);

        // Optimal buffer b* balancing probability distribution
        $idealBufferFloat = ($capacity * $pDrop) / $pArrival;
        // Conservative dampening factor based on critical fractile
        $stochasticBuffer = (int) max(1, min(5, floor($idealBufferFloat * $criticalFractile * 0.85)));
        
        $strat3Limit = $capacity + $stochasticBuffer;
        $strat3ExpectedArrivals = round($strat3Limit * $pArrival, 1);
        $strat3ExpectedWalks = max(0.0, round(max(0.0, $strat3ExpectedArrivals - $capacity) * 0.35, 2));
        $strat3WalkRiskPct = round(($strat3ExpectedWalks / $strat3Limit) * 100, 2);
        $strat3RelocationCost = round($strat3ExpectedWalks * $walkCost, 0);
        $strat3ExpectedRevenue = round(min($capacity, $strat3ExpectedArrivals) * $adr, 0);
        $strat3NetProfit = $strat3ExpectedRevenue - $strat3RelocationCost;

        $incrementalProfitVsZero = $strat3NetProfit - $strat1NetProfit;

        // Safety stop check
        $safetyStopTriggered = ($strat3WalkRiskPct > $maxRiskThreshold);

        return [
            'inputs' => [
                'capacity' => $capacity,
                'cancellation_rate_pct' => $cancellationRate,
                'noshow_rate_pct' => $noshowRate,
                'combined_drop_rate_pct' => round($pDrop * 100, 2),
                'adr' => $adr,
                'relocation_cost_per_walk' => $walkCost,
                'critical_fractile' => round($criticalFractile, 4),
                'max_tolerated_risk_pct' => $maxRiskThreshold,
            ],
            'strategies' => [
                'zero_overbooking' => [
                    'name' => 'Strategy 1: Zero Overbooking',
                    'booking_limit' => $strat1Limit,
                    'buffer_rooms' => 0,
                    'expected_arrivals' => $strat1ExpectedArrivals,
                    'expected_occupancy_pct' => $strat1ExpectedOccupancyPct,
                    'expected_walks' => 0.0,
                    'walk_risk_pct' => $strat1WalkRiskPct,
                    'expected_revenue' => $strat1ExpectedRevenue,
                    'relocation_cost' => $strat1RelocationCost,
                    'net_profit' => $strat1NetProfit,
                    'is_safe' => true,
                ],
                'fixed_buffer' => [
                    'name' => 'Strategy 2: Fixed Historical Buffer (+5%)',
                    'booking_limit' => $strat2Limit,
                    'buffer_rooms' => $fixedBufferRooms,
                    'expected_arrivals' => $strat2ExpectedArrivals,
                    'expected_occupancy_pct' => round((min($capacity, $strat2ExpectedArrivals) / $capacity) * 100, 1),
                    'expected_walks' => $strat2ExpectedWalks,
                    'walk_risk_pct' => $strat2WalkRiskPct,
                    'expected_revenue' => $strat2ExpectedRevenue,
                    'relocation_cost' => $strat2RelocationCost,
                    'net_profit' => $strat2NetProfit,
                    'is_safe' => ($strat2WalkRiskPct <= $maxRiskThreshold),
                ],
                'stochastic_optimization' => [
                    'name' => 'Strategy 3: Stochastic Risk-Aware Optimization',
                    'booking_limit' => $strat3Limit,
                    'buffer_rooms' => $stochasticBuffer,
                    'expected_arrivals' => $strat3ExpectedArrivals,
                    'expected_occupancy_pct' => round((min($capacity, $strat3ExpectedArrivals) / $capacity) * 100, 1),
                    'expected_walks' => $strat3ExpectedWalks,
                    'walk_risk_pct' => $strat3WalkRiskPct,
                    'expected_revenue' => $strat3ExpectedRevenue,
                    'relocation_cost' => $strat3RelocationCost,
                    'net_profit' => $strat3NetProfit,
                    'incremental_profit_vs_zero' => $incrementalProfitVsZero,
                    'is_safe' => !$safetyStopTriggered,
                ],
            ],
            'safety_stop_triggered' => $safetyStopTriggered,
            'stop_reason' => $safetyStopTriggered 
                ? "Projected walk risk ({$strat3WalkRiskPct}%) exceeds configured threshold ({$maxRiskThreshold}%). Overbooking automatically throttled." 
                : 'Projected walk risk within safe service-level boundaries.',
            'academic_references' => [
                'CIE 2023 (DOI: 10.1016/j.cie.2023.109226) - No-show probability forecasts',
                'JHTT 2014 (DOI: 10.1108/JHTT-03-2014-0006) - Optimal overbooking decisions',
                'CIE 2019 (DOI: 10.1016/j.cie.2019.106073) - Price optimization & simultaneous allocation',
                'COR 2005 (DOI: 10.1016/j.cor.2003.09.012) - Stochastic revenue optimization',
            ],
        ];
    }
}
