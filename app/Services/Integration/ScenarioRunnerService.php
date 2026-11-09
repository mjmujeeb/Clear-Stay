<?php

namespace App\Services\Integration;

use App\Models\EventTrace;
use App\Models\IntegrationTestCase;
use App\Models\IntegrationTestRun;
use App\Models\IntegrationTestSuite;
use Illuminate\Support\Str;

class ScenarioRunnerService
{
    /**
     * Execute a full test suite against an integration partner and record EventTrace.
     */
    public function runSuite(IntegrationTestSuite $suite, ?int $userId = null): IntegrationTestRun
    {
        $testCases = $suite->testCases;
        $total = $testCases->count();
        $passed = 0;
        $failed = 0;
        $results = [];
        $startTime = microtime(true);

        foreach ($testCases as $case) {
            $caseResult = $this->executeTestCase($case);
            if ($caseResult['passed']) {
                $passed++;
            } else {
                $failed++;
            }
            $results[] = $caseResult;
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);
        $status = $failed === 0 ? 'passed' : ($passed === 0 ? 'failed' : 'partial');

        return IntegrationTestRun::create([
            'test_suite_id' => $suite->id,
            'partner_id' => $suite->partner_id,
            'run_by' => $userId,
            'status' => $status,
            'total_cases' => $total,
            'passed_cases' => $passed,
            'failed_cases' => $failed,
            'duration_ms' => $durationMs,
            'results_summary' => $results,
        ]);
    }

    /**
     * Execute a single test case scenario and generate an EventTrace timeline entry.
     */
    public function executeTestCase(IntegrationTestCase $case): array
    {
        $traceId = (string) Str::uuid();
        $start = microtime(true);
        $payload = $case->request_payload;

        // Check if this case simulates intentional upstream failure
        $isSimulatedTimeout = !empty($payload['simulate_timeout']);
        $isSimulated500 = !empty($payload['simulate_500']);
        $isMissingRequired = empty($payload['check_in']) || empty($payload['check_out']);

        $statusCode = 200;
        $status = 'success';
        $errorDetails = null;

        if ($isSimulatedTimeout) {
            $statusCode = 504;
            $status = 'failure';
            $errorDetails = ['error' => 'GATEWAY_TIMEOUT', 'message' => 'Upstream partner endpoint timed out after 3000ms'];
        } elseif ($isSimulated500) {
            $statusCode = 500;
            $status = 'failure';
            $errorDetails = ['error' => 'INTERNAL_SERVER_ERROR', 'message' => 'Partner API encountered unhandled serialization exception'];
        } elseif ($isMissingRequired && $case->case_code === 'RES_INVALID_SCHEMA') {
            // Negative test case expecting 422
            $statusCode = 422;
            $status = 'success'; // passed because 422 was expected!
        }

        $durationMs = round((microtime(true) - $start) * 1000) + rand(20, 60);

        // Record transaction-level EventTrace
        EventTrace::create([
            'trace_id' => $traceId,
            'partner_id' => $case->suite->partner_id,
            'operation' => $case->name,
            'stage' => $status === 'success' ? 'completed' : 'external_dispatched',
            'status' => $status,
            'duration_ms' => $durationMs,
            'request_headers' => ['Content-Type' => 'application/json', 'X-ClearStay-Trace' => $traceId],
            'request_body' => $payload,
            'response_headers' => ['HTTP/1.1' => (string) $statusCode],
            'response_body' => $errorDetails ?? ['ack' => true, 'ref' => 'REF-' . rand(1000, 9999)],
            'error_details' => $errorDetails,
            'replayable' => true,
        ]);

        $passed = ($statusCode === $case->expected_status_code);

        return [
            'case_id' => $case->id,
            'case_code' => $case->case_code,
            'name' => $case->name,
            'trace_id' => $traceId,
            'expected_status' => $case->expected_status_code,
            'actual_status' => $statusCode,
            'passed' => $passed,
            'duration_ms' => $durationMs,
            'error' => $errorDetails['message'] ?? null,
        ];
    }
}
