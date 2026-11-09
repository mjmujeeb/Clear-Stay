<?php

namespace App\Services\Channel\Contracts;

use App\Models\ChannelConnection;

interface ChannelAdapterInterface
{
    /**
     * Get unique channel code (e.g. booking_com, agoda, expedia, rakuten).
     */
    public function getChannelCode(): string;

    /**
     * Get supported capabilities (e.g. inventory_push, rate_push, cta_ctd, min_stay).
     */
    public function getCapabilities(): array;

    /**
     * Test connection credentials against the channel API (or certified simulator).
     */
    public function testConnection(ChannelConnection $connection): array;

    /**
     * Push inventory availability update to the channel.
     */
    public function pushInventory(ChannelConnection $connection, array $inventoryData): array;

    /**
     * Push rate & restriction updates to the channel.
     */
    public function pushRates(ChannelConnection $connection, array $rateData): array;

    /**
     * Reconcile inventory state between ClearStay central and the channel.
     */
    public function reconcile(ChannelConnection $connection, string $dateFrom, string $dateTo): array;
}
