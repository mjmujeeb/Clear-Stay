<?php

namespace App\Services\Channel\Adapters;

class JalanAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'jalan';
    protected string $partnerName = 'Jalan.net (じゃらんnet) — SIMULATED';
    protected string $protocol = 'Jalan_Web_API_v3.2';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_ponta_d_points' => true,
            'supports_ryokan_meal_plans' => true,
            'currency_restricted' => 'JPY',
        ]);
    }

    protected function validateInventoryPayload(array $inventoryData): ?array
    {
        $baseError = parent::validateInventoryPayload($inventoryData);
        if ($baseError) {
            return $baseError;
        }

        // Japanese OTA constraint: rate must be positive integer in JPY
        foreach ($inventoryData as $item) {
            $rate = $item['rate'] ?? $item['base_rate'] ?? null;
            if ($rate !== null && (!is_numeric($rate) || (float)$rate < 0 || fmod((float)$rate, 1) !== 0.0)) {
                return [
                    'success' => false,
                    'channel' => $this->channelCode,
                    'status_code' => 400,
                    'error_code' => 'INVALID_JPY_INTEGER',
                    'error' => "Jalan.net requires whole-yen (integer JPY) rates. Provided: {$rate}",
                    'simulated_failure' => true,
                ];
            }
        }

        return null;
    }
}
