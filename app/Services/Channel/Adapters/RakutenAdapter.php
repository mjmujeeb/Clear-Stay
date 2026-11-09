<?php

namespace App\Services\Channel\Adapters;

class RakutenAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'rakuten';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'Rakuten_Travel_API_v2_XML',
            'supports_point_multiplier' => true,
            'supports_onsen_tax_flag' => true,
        ]);
    }
}
