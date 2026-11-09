<?php

namespace App\Services\Channel\Adapters;

class DirectWebAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'direct_web';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'ClearStay_Native_Booking_Engine',
            'supports_loyalty_discount' => true,
            'realtime_instant_sync' => true,
        ]);
    }
}
