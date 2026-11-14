<?php

namespace App\Services\Channel\Adapters;

class RakutenAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'rakuten';
    protected string $partnerName = 'Rakuten Travel (楽天トラベル) — SIMULATED';
    protected string $protocol = 'Rakuten_NEOT_v2.0';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_points_redemption' => true,
            'supports_consumption_tax_calc' => true,
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
                    'error' => "Rakuten Travel requires whole-yen (integer JPY) rates. Provided: {$rate}",
                    'simulated_failure' => true,
                ];
            }
        }

        return null;
    }
}
