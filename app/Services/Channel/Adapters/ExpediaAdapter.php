<?php

namespace App\Services\Channel\Adapters;

class ExpediaAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'expedia';
    protected string $partnerName = 'Expedia — SIMULATED';
    protected string $protocol = 'Expedia_Partner_Central_v4.0';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_package_rates' => true,
            'supports_member_deals' => true,
        ]);
    }
}
