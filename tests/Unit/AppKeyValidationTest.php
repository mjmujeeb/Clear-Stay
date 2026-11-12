<?php

namespace Tests\Unit;

use Tests\TestCase;

class AppKeyValidationTest extends TestCase
{
    protected string $scriptPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scriptPath = dirname(__DIR__, 2) . '/devops/scripts/validate-app-key.php';
    }

    /**
     * Helper to invoke the validator script in an isolated sub-process.
     */
    protected function runValidator(?string $appKey): array
    {
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->scriptPath);
        $baseDir = dirname(__DIR__, 2);
        $env = array_filter(array_merge($_SERVER, $_ENV), fn ($v) => is_scalar($v));
        if ($appKey !== null) {
            $env['APP_KEY'] = $appKey;
        } else {
            unset($env['APP_KEY']);
        }

        $process = proc_open($cmd, $descriptor, $pipes, $baseDir, $env);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    public function test_valid_base64_app_key_passes(): void
    {
        $validKey = 'base64:' . base64_encode(random_bytes(32));
        $result = $this->runValidator($validKey);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame($validKey, $result['stdout']);
        $this->assertStringContainsString('[OK]', $result['stderr']);
    }

    public function test_valid_quoted_base64_app_key_passes_and_trims_quotes(): void
    {
        $rawKey = 'base64:' . base64_encode(random_bytes(32));
        $quotedKey = '"' . $rawKey . '"';
        $result = $this->runValidator($quotedKey);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame($rawKey, $result['stdout']);
        $this->assertStringContainsString('[OK]', $result['stderr']);
    }

    public function test_valid_raw_32_character_key_passes(): void
    {
        $raw32 = '12345678901234567890123456789012';
        $result = $this->runValidator($raw32);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame($raw32, $result['stdout']);
        $this->assertStringContainsString('[OK]', $result['stderr']);
    }

    public function test_valid_64_character_hex_key_converts_to_base64(): void
    {
        $bytes = random_bytes(32);
        $hexKey = bin2hex($bytes);
        $result = $this->runValidator($hexKey);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame('base64:' . base64_encode($bytes), $result['stdout']);
        $this->assertStringContainsString('[OK]', $result['stderr']);
    }

    public function test_missing_app_key_fails_safely(): void
    {
        $result = $this->runValidator(null);

        $this->assertSame(1, $result['exit_code']);
        $this->assertEmpty($result['stdout']);
        $this->assertStringContainsString('[ERROR] APP_KEY is missing or uninitialized', $result['stderr']);
    }

    public function test_placeholder_generate_value_fails_safely(): void
    {
        $result = $this->runValidator('generateValue');

        $this->assertSame(1, $result['exit_code']);
        $this->assertEmpty($result['stdout']);
        $this->assertStringContainsString('[ERROR] APP_KEY is missing or uninitialized', $result['stderr']);
    }

    public function test_invalid_short_key_fails_safely_without_leaking_key(): void
    {
        $shortKey = 'secret_password_123';
        $result = $this->runValidator($shortKey);

        $this->assertSame(1, $result['exit_code']);
        $this->assertEmpty($result['stdout']);
        $this->assertStringContainsString('[ERROR] Invalid APP_KEY format or byte length.', $result['stderr']);
        $this->assertStringNotContainsString($shortKey, $result['stderr']);
    }
}
