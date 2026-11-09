<?php

namespace App\Services\Channel\Adapters;

use App\Models\ChannelConnection;
use Illuminate\Support\Str;

class MockSimulatorAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'demo_ota';

    public function pushInventory(ChannelConnection $connection, array $inventoryData): array
    {
        // Check if simulation failure flag is passed in connection settings
        $settings = $connection->credentials ?? [];
        if (!empty($settings['simulate_failure'])) {
            return [
                'success' => false,
                'channel' => $this->channelCode,
                'status_code' => 500,
                'error' => 'SIMULATED_UPSTREAM_TIMEOUT: OTA Gateway failed to acknowledge lock on batch.',
                'latency_ms' => 1250,
            ];
        }

        return parent::pushInventory($connection, $inventoryData);
    }
}
