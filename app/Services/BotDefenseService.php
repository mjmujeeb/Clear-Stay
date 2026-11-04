<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityIncident;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BotDefenseService
{
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('app.key', 'clearstay-default-defense-salt-key-9988');
    }

    /**
     * Generate an HMAC-signed time-lock token for form submission speed verification.
     */
    public function generateTimeLockToken(?string $formId = null): string
    {
        $timestamp = time();
        $randomSalt = Str::random(12);
        $payload = "{$timestamp}:{$randomSalt}:" . ($formId ?? 'general');
        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        return base64_encode("{$payload}:{$signature}");
    }

    /**
     * Generate an interactive Math / Cryptographic Challenge for human verification.
     */
    public function generateChallenge(): array
    {
        $operators = ['+', '-'];
        $op = $operators[array_rand($operators)];

        if ($op === '+') {
            $n1 = rand(3, 15);
            $n2 = rand(2, 12);
            $answer = $n1 + $n2;
            $question = "{$n1} + {$n2} = ?";
        } else {
            $n1 = rand(10, 20);
            $n2 = rand(2, 9);
            $answer = $n1 - $n2;
            $question = "{$n1} - {$n2} = ?";
        }

        $timestamp = time();
        $payload = "{$answer}:{$timestamp}";
        $token = base64_encode($payload . ':' . hash_hmac('sha256', $payload, $this->secretKey));

        return [
            'question' => $question,
            'token' => $token,
        ];
    }

    /**
     * Validate an interactive challenge answer.
     */
    public function validateChallenge(?string $userAnswer, ?string $challengeToken): bool
    {
        if (empty($challengeToken) || $userAnswer === null || trim($userAnswer) === '') {
            return false;
        }

        $decoded = base64_decode($challengeToken, true);
        if (!$decoded) {
            return false;
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        [$expectedAnswer, $timestamp, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', "{$expectedAnswer}:{$timestamp}", $this->secretKey);

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // Token valid for 30 minutes
        if ((time() - intval($timestamp)) > 1800) {
            return false;
        }

        return intval(trim($userAnswer)) === intval($expectedAnswer);
    }

    /**
     * Verify form submission against all bot vectors:
     * 1. Known Automated Scanners / Headless user agents
     * 2. Hidden Honeypot fields
     * 3. Sub-human Form speed (< 1.2 seconds) or Expired token (> 30 mins)
     * 4. Interactive Human Verification Challenge (if enabled/submitted)
     */
    public function verifyForm(Request $request, bool $requireChallenge = false): array
    {
        $ip = $request->ip();

        // 1. IP Firewall Check
        if (BlockedIp::isBlocked($ip)) {
            SecurityIncident::log('firewall.blocked_ip_access', 'critical', ['ip' => $ip], $request, true);
            return [
                'success' => false,
                'reason' => 'IP_BLOCKED',
                'message' => __('clearstay.security.ip_blocked_message'),
            ];
        }

        // 2. Automated Scanner / Suspicious User-Agent Detection
        $userAgent = strtolower($request->userAgent() ?? '');
        $badAgents = [
            'sqlmap', 'nikto', 'masscan', 'acunetix', 'havij', 'zaproxy',
            'python-requests', 'curl/', 'go-http-client', 'wget', 'headlesschrome', 'phantomjs', 'selenium'
        ];

        foreach ($badAgents as $bad) {
            if (str_contains($userAgent, $bad)) {
                SecurityIncident::log('bot.scanner_blocked', 'high', [
                    'tool' => $bad,
                    'user_agent' => $userAgent
                ], $request, true);

                BlockedIp::block($ip, "Automated vulnerability scanner or crawler detected: {$bad}", 'high', 120);

                return [
                    'success' => false,
                    'reason' => 'SCANNER_DETECTED',
                    'message' => __('clearstay.security.bot_detected_message'),
                ];
            }
        }

        // 3. Honeypot Verification
        // Decoy fields rendered in HTML with display:none/hidden that real humans never fill
        $hpToken = $request->input('_cs_hp_token');
        $hpWebsite = $request->input('_cs_hp_website');

        if (!empty($hpToken) || !empty($hpWebsite)) {
            SecurityIncident::log('bot.honeypot_triggered', 'high', [
                'honeypot_value_token' => $hpToken,
                'honeypot_value_website' => $hpWebsite,
            ], $request, true);

            // Increment bad bot strike on IP
            BlockedIp::block($ip, 'Automated form crawler triggered invisible honeypot trap', 'high', 60);

            return [
                'success' => false,
                'reason' => 'HONEYPOT_TRIGGERED',
                'message' => __('clearstay.security.bot_verification_failed'),
            ];
        }

        // 4. Time-Lock Speed Verification
        $timeToken = $request->input('_cs_timelock');
        if ($timeToken) {
            $decoded = base64_decode($timeToken, true);
            if ($decoded) {
                $parts = explode(':', $decoded);
                if (count($parts) === 4) {
                    [$timestamp, $salt, $formId, $signature] = $parts;
                    $expectedSig = hash_hmac('sha256', "{$timestamp}:{$salt}:{$formId}", $this->secretKey);

                    if (hash_equals($expectedSig, $signature)) {
                        $elapsed = time() - intval($timestamp);

                        // Form submitted in under 1.2 seconds = bot automated script
                        if ($elapsed < 1.2) {
                            SecurityIncident::log('bot.rapid_submission', 'medium', [
                                'elapsed_seconds' => $elapsed,
                                'form' => $formId,
                            ], $request, false);

                            return [
                                'success' => false,
                                'reason' => 'SUBMITTED_TOO_FAST',
                                'message' => __('clearstay.security.too_fast_warning'),
                            ];
                        }

                        // Expired token (> 45 minutes)
                        if ($elapsed > 2700) {
                            return [
                                'success' => false,
                                'reason' => 'TOKEN_EXPIRED',
                                'message' => __('clearstay.security.token_expired_warning'),
                            ];
                        }
                    }
                }
            }
        }

        // 5. Interactive Challenge Verification (if submitted or required)
        $challengeToken = $request->input('_cs_challenge_token');
        $challengeAnswer = $request->input('_cs_challenge_answer');

        if ($requireChallenge || (!empty($challengeToken) && $challengeAnswer !== null)) {
            if (!$this->validateChallenge($challengeAnswer, $challengeToken)) {
                SecurityIncident::log('bot.challenge_failed', 'medium', [
                    'submitted_answer' => $challengeAnswer,
                ], $request, false);

                return [
                    'success' => false,
                    'reason' => 'CHALLENGE_FAILED',
                    'message' => __('clearstay.security.incorrect_challenge_answer'),
                ];
            }
        }

        return [
            'success' => true,
            'reason' => null,
            'message' => 'Human verification passed successfully.',
        ];
    }
}
