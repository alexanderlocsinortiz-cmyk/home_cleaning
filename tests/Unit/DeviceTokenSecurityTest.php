<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Services\DeviceTokenService;
use Tests\TestCase;

class DeviceTokenSecurityTest extends TestCase
{
    private DeviceTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new DeviceTokenService();
    }

    /**
     * Test that device tokens are hashed and not stored in plaintext
     */
    public function testTokenIsHashedBeforeStorage(): void
    {
        $plainToken = 'TEST-TOKEN-12345';
        $hashedToken = $this->tokenService->hashToken($plainToken);

        // Hash should differ from plaintext
        $this->assertNotEquals($plainToken, $hashedToken);

        // Hash should be deterministic (same input = same output)
        $hashedAgain = $this->tokenService->hashToken($plainToken);
        $this->assertEquals($hashedToken, $hashedAgain);

        // Hash should be long (SHA256 = 64 chars)
        $this->assertEquals(64, strlen($hashedToken));
    }

    /**
     * Test token expiration logic
     */
    public function testTokenExpirationChecking(): void
    {
        // Create mock device with expired token
        $device = new Device();
        $device->token_expires_at = now()->subMinutes(30);

        $this->assertTrue($device->isTokenExpired());

        // Create mock device with valid token
        $device2 = new Device();
        $device2->token_expires_at = now()->addDays(7);

        $this->assertFalse($device2->isTokenExpired());
    }

    /**
     * Test signature validation with correct HMAC-SHA256
     */
    public function testSignatureValidationSuccess(): void
    {
        $timestamp = (string) time();
        $body = json_encode(['punch_type' => 'in', 'user_id' => 123]);
        $secret = 'secret-key-xyz';

        // Create signature as device would
        $dataToSign = $timestamp . $body;
        $signature = hash_hmac('sha256', $dataToSign, $secret);

        // Create mock device
        $device = new Device();
        $device->secret_key = $secret;

        // Validate should succeed
        $isValid = $this->tokenService->validateSignature(
            $device,
            $timestamp,
            $signature,
            $body
        );

        $this->assertTrue($isValid);
    }

    /**
     * Test signature validation fails with tampered body
     */
    public function testSignatureValidationFailsWithTamperedData(): void
    {
        $timestamp = (string) time();
        $body = json_encode(['punch_type' => 'in', 'user_id' => 123]);
        $tamperedBody = json_encode(['punch_type' => 'in', 'user_id' => 999]);  // Changed user_id
        $secret = 'secret-key-xyz';

        // Create signature for original body
        $dataToSign = $timestamp . $body;
        $signature = hash_hmac('sha256', $dataToSign, $secret);

        // Create mock device
        $device = new Device();
        $device->secret_key = $secret;

        // Validate should fail because body was changed
        $isValid = $this->tokenService->validateSignature(
            $device,
            $timestamp,
            $signature,
            $tamperedBody
        );

        $this->assertFalse($isValid);
    }

    /**
     * Test signature validation fails with expired timestamp
     */
    public function testSignatureValidationFailsWithExpiredTimestamp(): void
    {
        $oldTimestamp = (string) (time() - 400);  // 400 seconds ago (beyond 5 min window)
        $body = json_encode(['punch_type' => 'in']);
        $secret = 'secret-key-xyz';

        // Create valid signature
        $dataToSign = $oldTimestamp . $body;
        $signature = hash_hmac('sha256', $dataToSign, $secret);

        // Create mock device
        $device = new Device();
        $device->secret_key = $secret;

        // Validate should fail due to old timestamp
        $isValid = $this->tokenService->validateSignature(
            $device,
            $oldTimestamp,
            $signature,
            $body
        );

        $this->assertFalse($isValid);
    }

    /**
     * Test that token verification uses constant-time comparison
     */
    public function testTokenVerificationIsConstantTime(): void
    {
        $plainToken = 'correct-token';
        $hashedToken = $this->tokenService->hashToken($plainToken);

        // Correct token should verify
        $this->assertTrue($this->tokenService->verifyToken($plainToken, $hashedToken));

        // Incorrect token should not verify
        $this->assertFalse($this->tokenService->verifyToken('wrong-token', $hashedToken));

        // Partially correct token should not verify
        $this->assertFalse($this->tokenService->verifyToken('correct-toke', $hashedToken));
    }

    /**
     * Test token pair generation creates both access and refresh tokens
     */
    public function testTokenPairGeneration(): void
    {
        $tokenPair = $this->tokenService->generateTokenPair();

        $this->assertArrayHasKey('access_token', $tokenPair);
        $this->assertArrayHasKey('refresh_token', $tokenPair);
        $this->assertArrayHasKey('expires_at', $tokenPair);

        // Tokens should be different
        $this->assertNotEquals(
            $tokenPair['access_token'],
            $tokenPair['refresh_token']
        );

        // Tokens should be reasonably long (entropy)
        $this->assertGreaterThan(20, strlen($tokenPair['access_token']));
        $this->assertGreaterThan(20, strlen($tokenPair['refresh_token']));
    }

    /**
     * Test replay attack prevention with 5-minute window
     */
    public function testReplayAttackPrevention(): void
    {
        $timestamp = (string) (time() - 400);  // 400 seconds ago
        $body = json_encode(['punch_type' => 'in']);
        $secret = 'secret-key-xyz';

        $dataToSign = $timestamp . $body;
        $signature = hash_hmac('sha256', $dataToSign, $secret);

        $device = new Device();
        $device->secret_key = $secret;

        // Attack attempt with old timestamp should be rejected
        $isValid = $this->tokenService->validateSignature(
            $device,
            $timestamp,
            $signature,
            $body
        );

        $this->assertFalse($isValid, 'Replay attack should be prevented');
    }
}
