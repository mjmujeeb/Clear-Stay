<?php

namespace App\Services\Channel\Adapters;

class BookingComAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'booking_com';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'B.XML_REST_v2',
            'supports_promotions' => true,
            'supports_non_refundable' => true,
        ]);
    }
}
