<?php

namespace App\Services\Channel\Adapters;

class AgodaAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'agoda';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'Agoda_YCS_JSON_v3',
            'supports_flash_sales' => true,
        ]);
    }
}
