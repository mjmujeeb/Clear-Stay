<?php

namespace App\Services\Channel\Adapters;

class BookingComAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'booking_com';
    protected string $partnerName = 'Booking.com — SIMULATED';
    protected string $protocol = 'B.XML_v2.4_JSON';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_promotions' => true,
            'supports_non_refundable' => true,
            'supports_genius_rates' => true,
        ]);
    }
}
