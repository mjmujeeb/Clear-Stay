<?php

namespace App\Services\Channel\Adapters;

class AgodaAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'agoda';
    protected string $partnerName = 'Agoda — SIMULATED';
    protected string $protocol = 'Agoda_YCS_v3.1_JSON';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_flash_sales' => true,
            'supports_vcc_settlement' => true,
            'supports_cash_at_hotel' => false,
        ]);
    }
}
