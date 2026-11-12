<?php

/**
 * ClearStay Enterprise - Production APP_KEY Validator & Normalizer
 *
 * Validates the injected APP_KEY environment variable for Laravel AES-256-CBC encryption.
 * Requirements:
 *  1. Never assumes a .env file exists.
 *  2. Never prints the secret key to STDERR or logs.
 *  3. Verifies strict 32-byte key length (or decoded base64 length).
 *  4. Performs encrypter self-test using Illuminate\Encryption\Encrypter.
 *  5. Outputs normalized key to STDOUT on success.
 *  6. Emits actionable guidance to STDERR and exits with code 1 on failure.
 */

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$rawKey = getenv('APP_KEY');
if ($rawKey === false || $rawKey === null) {
    $rawKey = '';
}

// 1. Clean whitespace and accidental surrounding quotes/slashes
$cleanKey = trim((string) $rawKey, "\\\"' \t\n\r");

// 2. Reject empty or uninitialized placeholder values
if ($cleanKey === '' || $cleanKey === 'generateValue') {
    fwrite(STDERR, "=================================================================\n");
    fwrite(STDERR, "[ERROR] APP_KEY is missing or uninitialized in environment.\n");
    fwrite(STDERR, "=================================================================\n");
    fwrite(STDERR, "ClearStay requires a valid 32-byte encryption key for production.\n");
    fwrite(STDERR, "To resolve this in Render / Vercel Project Settings:\n");
    fwrite(STDERR, "  1. Generate a secure key locally: php artisan key:generate --show\n");
    fwrite(STDERR, "  2. Add the environment variable: APP_KEY=base64:...\n");
    fwrite(STDERR, "=================================================================\n");
    exit(1);
}

// 3. Resolve key byte representation
$keyBytes = null;
$normalizedKey = $cleanKey;

if (str_starts_with($cleanKey, 'base64:')) {
    $b64Payload = substr($cleanKey, 7);
    $decoded = base64_decode($b64Payload, true);
    if ($decoded === false) {
        fwrite(STDERR, "=================================================================\n");
        fwrite(STDERR, "[ERROR] APP_KEY has 'base64:' prefix but contains invalid base64 characters.\n");
        fwrite(STDERR, "=================================================================\n");
        exit(1);
    }
    $keyBytes = $decoded;
} elseif (strlen($cleanKey) === 32) {
    // Standard 32-character ASCII / binary key
    $keyBytes = $cleanKey;
} elseif (ctype_xdigit($cleanKey) && strlen($cleanKey) === 64) {
    // 64-character hex string (common from raw secret generators) -> 32 raw bytes
    $keyBytes = hex2bin($cleanKey);
    $normalizedKey = 'base64:' . base64_encode($keyBytes);
} else {
    $actualLength = strlen($cleanKey);
    fwrite(STDERR, "=================================================================\n");
    fwrite(STDERR, "[ERROR] Invalid APP_KEY format or byte length.\n");
    fwrite(STDERR, "=================================================================\n");
    fwrite(STDERR, "Detected string length: {$actualLength} bytes.\n");
    fwrite(STDERR, "Laravel AES-256-CBC cipher requires exactly 32 bytes (or base64:<32-bytes>).\n");
    fwrite(STDERR, "Please generate a valid key using: php artisan key:generate --show\n");
    fwrite(STDERR, "=================================================================\n");
    exit(1);
}

// 4. Verify exact 32-byte length for AES-256-CBC
$byteLen = strlen($keyBytes);
if ($byteLen !== 32) {
    fwrite(STDERR, "=================================================================\n");
    fwrite(STDERR, "[ERROR] Decoded APP_KEY length mismatch: {$byteLen} bytes (expected 32 bytes).\n");
    fwrite(STDERR, "=================================================================\n");
    exit(1);
}

// 5. Functional encryption self-test
if (class_exists(\Illuminate\Encryption\Encrypter::class)) {
    try {
        $enc = new \Illuminate\Encryption\Encrypter($keyBytes, 'AES-256-CBC');
        $probe = 'clearstay_integrity_check';
        $encrypted = $enc->encryptString($probe);
        if ($enc->decryptString($encrypted) !== $probe) {
            throw new \RuntimeException('Encryption/Decryption parity mismatch.');
        }
    } catch (\Throwable $e) {
        fwrite(STDERR, "[ERROR] Encryption subsystem validation failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

// 6. Success: Inform log on STDERR (without secret) and print normalized key to STDOUT
fwrite(STDERR, "[OK] Application encryption key verified successfully (AES-256-CBC, 32 bytes).\n");
echo $normalizedKey;
exit(0);
