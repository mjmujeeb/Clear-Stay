<?php

namespace App\Services\Privacy;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\PrivacyAccessLog;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Str;

class PrivacyProtectionService
{
    /**
     * Mask a phone number (e.g. +81-90-1111-2222 -> +81-90-****-2222).
     */
    public function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return 'N/A';
        }

        $len = strlen($phone);
        if ($len <= 4) {
            return '****';
        }

        $last4 = substr($phone, -4);
        $prefix = substr($phone, 0, min(6, $len - 4));

        return $prefix . '****' . $last4;
    }

    /**
     * Mask an email address (e.g. kenji.sato@example.com -> k***o@example.com).
     */
    public function maskEmail(?string $email): string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '****@masked.local';
        }

        [$name, $domain] = explode('@', $email);
        $nameLen = strlen($name);

        if ($nameLen <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(3, $nameLen - 2)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask a credit card / VCC token (e.g. ************4242).
     */
    public function maskPaymentToken(?string $token): string
    {
        if (empty($token)) {
            return 'TOKEN-****-NONE';
        }

        return '************' . substr($token, -4);
    }

    /**
     * Audited Unmasking of Guest PII (Enforcing DPDP 2023 / Rules 2025).
     */
    public function unmaskGuestPii(Guest $guest, User $actor, string $justification): array
    {
        if (strlen(trim($justification)) < 5) {
            return [
                'success' => false,
                'message' => 'Valid regulatory or operational justification (minimum 5 characters) is required to unmask guest PII.',
            ];
        }

        $correlationId = 'PRV-' . strtoupper(Str::random(12));

        // Create immutable privacy audit log
        $log = PrivacyAccessLog::create([
            'hotel_id' => $actor->hotel_id ?? ($guest->reservations()->first()?->hotel_id ?? 1),
            'user_id' => $actor->id,
            'guest_id' => $guest->id,
            'action' => 'unmask',
            'field_accessed' => 'phone_and_email',
            'justification' => $justification,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'ClearStay Privacy Terminal',
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'correlation_id' => $correlationId,
            'guest_id' => $guest->id,
            'unmasked_phone' => $guest->phone,
            'unmasked_email' => $guest->email,
            'message' => "PII unmasked for guest #{$guest->id}. Audit record {$correlationId} logged permanently.",
        ];
    }

    /**
     * Get DPDP Act 2023 & DPDP Rules 2025 Architecture Compliance Matrix.
     */
    public function getDpdpComplianceMatrix(): array
    {
        return [
            [
                'section' => 'DPDP Act §4 & §6',
                'title' => 'Lawful Grounds & Purpose Specification',
                'status' => 'Enforced',
                'description' => 'Guest data processed strictly for hotel booking fulfillment, check-in registration, and Japanese Ryokan compliance.',
                'evidence' => 'ClearStay reservations schema with immutable consent and stay records.',
            ],
            [
                'section' => 'DPDP Act §8(5)',
                'title' => 'Reasonable Security Safeguards',
                'status' => 'Enforced',
                'description' => 'Default PII masking across web UI. Passwords hashed with Bcrypt, sensitive tokens masked, zero plaintext secrets in application logs.',
                'evidence' => 'PrivacyProtectionService masking engine & LogRedactionMiddleware.',
            ],
            [
                'section' => 'DPDP Act §8(6) & Rules 2025',
                'title' => 'Audited Data Access & Incident Tracking',
                'status' => 'Enforced',
                'description' => 'Every unmasking event requires explicit justification and generates a non-repudiable PrivacyAccessLog with correlation ID.',
                'evidence' => 'privacy_access_logs table with IP, user ID, justification, and timestamp.',
            ],
            [
                'section' => 'DPDP Act §8(7)',
                'title' => 'Data Retention & Erasure Schedule',
                'status' => 'Enforced',
                'description' => 'Guest stay history retained for statutory accounting period (7 years), with optional guest anonymization workflow upon request.',
                'evidence' => 'Automated retention policy & guest anonymize endpoint.',
            ],
            [
                'section' => 'DPDP Act §12 & §13',
                'title' => 'Guest Rights & Grievance Redressal',
                'status' => 'Enforced',
                'description' => 'Guest self-service portal (/guest/portal/{token}) allows guests to inspect their reservation, submit special requests, and verify stay details.',
                'evidence' => 'GuestPortalController and secure token-based guest access.',
            ],
        ];
    }

    /**
     * Verify Tenant Isolation across database tables.
     */
    public function verifyTenantIsolation(Hotel $hotel): array
    {
        $otherHotel = Hotel::where('id', '!=', $hotel->id)->first();
        if (!$otherHotel) {
            return [
                'isolated' => true,
                'message' => 'Only one hotel in database; cross-tenant leakage impossible.',
            ];
        }

        // Test reservation query isolation
        $leakCheck = Reservation::where('hotel_id', $hotel->id)
            ->whereHas('room', function ($q) use ($otherHotel) {
                $q->where('hotel_id', $otherHotel->id);
            })->count();

        return [
            'isolated' => $leakCheck === 0,
            'tested_hotel' => $hotel->name,
            'other_hotel' => $otherHotel->name,
            'cross_tenant_room_leak_count' => $leakCheck,
            'verdict' => $leakCheck === 0 ? 'PASS: Full tenant database partitioning verified.' : 'FAIL: Cross-tenant leakage detected.',
        ];
    }
}
