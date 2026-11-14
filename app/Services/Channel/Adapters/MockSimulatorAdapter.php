<?php

namespace App\Services\Channel\Adapters;

class MockSimulatorAdapter extends BaseChannelAdapter
{
    protected string $channelCode = 'ota_simulator';
    protected string $partnerName = 'Demo Channel Partner Simulator';
    protected string $protocol = 'OpenChannel_Mock_v1';
}
