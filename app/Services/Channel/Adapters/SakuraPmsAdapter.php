<?php

namespace App\Services\Channel\Adapters;

class SakuraPmsAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'sakura_pms';
    protected string $partnerName = 'Sakura PMS Connector — SIMULATED';
    protected string $protocol = 'Sakura_PMS_OpenExchange_v2.1';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'supports_folio_sync' => true,
            'supports_housekeeping_link' => true,
            'supports_guest_profiles' => true,
        ]);
    }
}
