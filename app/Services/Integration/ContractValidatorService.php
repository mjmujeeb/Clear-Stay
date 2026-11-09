<?php

namespace App\Services\Integration;

use App\Models\PartnerContract;

class ContractValidatorService
{
    /**
     * Validate an incoming JSON or structured payload against a partner's contract.
     */
    public function validatePayload(PartnerContract $contract, array $payload): array
    {
        $spec = json_decode($contract->spec_content, true);
        if (!$spec) {
            return [
                'is_valid' => false,
                'errors' => ['Contract specification is not valid JSON'],
            ];
        }

        $errors = [];
        $requiredFields = $contract->validation_rules['required_fields'] ?? ['reservation_id', 'hotel_code', 'check_in', 'check_out'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || empty($payload[$field])) {
                $errors[] = "Missing mandatory field: '{$field}' required by contract version {$contract->version}.";
            }
        }

        // Validate date formats if present
        foreach (['check_in', 'check_out', 'date'] as $dateField) {
            if (isset($payload[$dateField])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload[$dateField])) {
                    $errors[] = "Field '{$dateField}' value '{$payload[$dateField]}' does not comply with ISO-8601 YYYY-MM-DD schema.";
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'contract_version' => $contract->version,
            'errors' => $errors,
            'rules_checked' => count($requiredFields) + 2,
        ];
    }

    /**
     * Compare two contract versions and identify breaking schema changes.
     */
    public function diffContracts(PartnerContract $v1, PartnerContract $v2): array
    {
        $spec1 = json_decode($v1->spec_content, true) ?? [];
        $spec2 = json_decode($v2->spec_content, true) ?? [];

        $breakingChanges = [];
        $addedFields = [];

        $req1 = $v1->validation_rules['required_fields'] ?? [];
        $req2 = $v2->validation_rules['required_fields'] ?? [];

        // Check newly mandatory fields (breaking for legacy consumers)
        $newRequired = array_diff($req2, $req1);
        foreach ($newRequired as $field) {
            $breakingChanges[] = "New required field '{$field}' introduced in version {$v2->version}.";
        }

        // Check removed fields
        $removed = array_diff($req1, $req2);
        foreach ($removed as $field) {
            $breakingChanges[] = "Field '{$field}' was removed or deprecated in version {$v2->version}.";
        }

        return [
            'has_breaking_changes' => !empty($breakingChanges),
            'breaking_changes' => $breakingChanges,
            'added_fields' => array_values($newRequired),
            'removed_fields' => array_values($removed),
            'compatibility' => empty($breakingChanges) ? 'BACKWARD_COMPATIBLE' : 'BREAKING_CHANGE',
        ];
    }
}
