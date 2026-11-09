<?php

namespace App\Services\Channel\Adapters;

class JalanAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'jalan';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'Jalan_Net_WebService_v1',
            'supports_seasonal_plans' => true,
        ]);
    }
}
