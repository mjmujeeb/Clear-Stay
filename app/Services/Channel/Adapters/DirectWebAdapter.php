<?php

namespace App\Services\Channel\Adapters;

class DirectWebAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'direct_web';
    protected string $partnerName = 'Direct Booking Engine — SIMULATED';
    protected string $protocol = 'ClearStay_Direct_v1.0';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_direct_guest_loyalty' => true,
            'supports_instant_confirmation' => true,
            'zero_commission' => true,
        ]);
    }
}
