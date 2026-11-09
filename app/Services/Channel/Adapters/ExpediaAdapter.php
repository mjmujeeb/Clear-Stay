<?php

namespace App\Services\Channel\Adapters;

class ExpediaAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'expedia';

    public function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [
            'protocol' => 'Expedia_Partner_Central_v4',
            'supports_package_rates' => true,
        ]);
    }
}
