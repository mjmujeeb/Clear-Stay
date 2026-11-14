<?php

namespace App\Services\Integration;

use App\Models\AuditLog;
use App\Models\ChannelConnection;
use App\Models\IntegrationMode;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class IntegrationModeService
{
    public const PARTNERS = [
        'booking_com' => 'Booking.com — SIMULATED',
        'agoda' => 'Agoda — SIMULATED',
        'expedia' => 'Expedia — SIMULATED',
        'rakuten' => 'Rakuten Travel (楽天トラベル) — SIMULATED',
        'jalan' => 'Jalan.net (じゃらんnet) — SIMULATED',
        'direct_web' => 'Direct Booking Engine — SIMULATED',
        'sakura_pms' => 'Sakura PMS Connector — SIMULATED',
    ];

    /**
     * Get active mode for a specific partner or global default.
     */
    public function getMode(?string $partnerCode = null): string
    {
        if ($partnerCode) {
            $record = IntegrationMode::where('partner_code', $partnerCode)->first();
            if ($record) {
                return $record->mode;
            }
        }

        $global = IntegrationMode::whereNull('partner_code')->first();
        return $global ? $global->mode : IntegrationMode::MODE_SIMULATOR;
    }

    /**
     * Check if a specific partner (or global) is in SIMULATOR mode.
     */
    public function isSimulator(?string $partnerCode = null): bool
    {
        return $this->getMode($partnerCode) === IntegrationMode::MODE_SIMULATOR;
    }

    /**
     * Check if a specific partner is in SANDBOX mode.
     */
    public function isSandbox(?string $partnerCode = null): bool
    {
        return $this->getMode($partnerCode) === IntegrationMode::MODE_SANDBOX;
    }

    /**
     * Check if a specific partner is in LIVE PRODUCTION mode.
     */
    public function isLive(?string $partnerCode = null): bool
    {
        return $this->getMode($partnerCode) === IntegrationMode::MODE_LIVE;
    }

    /**
     * Determine if Live Production mode can be legally activated.
     * Enforces that live production mode cannot be activated without real, approved credentials.
     */
    public function canActivateLive(?string $partnerCode = null): bool
    {
        // Enforce that live mode requires explicit environment variable AND non-empty credentials
        if (!config('services.channels.allow_live_mode', false)) {
            return false;
        }

        if ($partnerCode) {
            $conn = ChannelConnection::whereHas('channel', fn($q) => $q->where('code', $partnerCode))->first();
            $creds = $conn?->credentials;
            if (empty($creds) || empty($creds['api_key']) || empty($creds['property_id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Safely switch integration mode.
     * Prevents accidental or fraudulent activation of Live mode without authorized credentials.
     */
    public function setMode(string $mode, ?string $partnerCode = null, ?int $userId = null, ?string $notes = null): IntegrationMode
    {
        $validModes = [
            IntegrationMode::MODE_SIMULATOR,
            IntegrationMode::MODE_SANDBOX,
            IntegrationMode::MODE_LIVE,
        ];

        if (!in_array($mode, $validModes, true)) {
            throw new RuntimeException("Invalid integration mode: {$mode}");
        }

        if ($mode === IntegrationMode::MODE_LIVE && !$this->canActivateLive($partnerCode)) {
            throw new RuntimeException("Cannot activate Live Production mode for [{$partnerCode}]: No authorized production credentials configured. Keep in SIMULATOR or SANDBOX mode.");
        }

        $record = IntegrationMode::updateOrCreate(
            ['partner_code' => $partnerCode],
            [
                'mode' => $mode,
                'activated_by' => $userId,
                'credentials_verified_at' => $mode === IntegrationMode::MODE_LIVE ? now() : null,
                'notes' => $notes ?? "Switched to {$mode} by authorized operator.",
            ]
        );

        AuditLog::record('integrations.mode_changed', null, null, [
            'partner_code' => $partnerCode ?? 'global',
            'mode' => $mode,
            'user_id' => $userId,
        ]);

        return $record;
    }

    /**
     * Get display UI badge metadata for current mode.
     */
    public function getModeBadge(?string $partnerCode = null): array
    {
        $mode = $this->getMode($partnerCode);

        return match ($mode) {
            IntegrationMode::MODE_LIVE => [
                'mode' => 'live',
                'label' => 'LIVE PRODUCTION',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300',
                'dot' => 'bg-emerald-500',
                'description' => 'Real external endpoints with verified partner credentials.',
            ],
            IntegrationMode::MODE_SANDBOX => [
                'mode' => 'sandbox',
                'label' => 'SANDBOX TESTBED',
                'class' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300',
                'dot' => 'bg-amber-500',
                'description' => 'Official partner testbed environment with staging credentials.',
            ],
            default => [
                'mode' => 'simulator',
                'label' => 'SIMULATOR (LOCAL)',
                'class' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950 dark:text-blue-300',
                'dot' => 'bg-blue-500',
                'description' => 'Fully local, stateful simulation with isolated remote database tables.',
            ],
        };
    }

    /**
     * Get status overview for all 7 simulated partners.
     */
    public function getAllPartnerModes(): array
    {
        $modes = [];
        foreach (self::PARTNERS as $code => $name) {
            $mode = $this->getMode($code);
            $modes[$code] = [
                'code' => $code,
                'name' => $name,
                'mode' => $mode,
                'badge' => $this->getModeBadge($code),
                'can_activate_live' => $this->canActivateLive($code),
            ];
        }
        return $modes;
    }
}
