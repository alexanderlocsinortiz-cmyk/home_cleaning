<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Str;

class DeviceTokenService
{
    /**
     * Create a new device with hashed token and secret key
     */
    public function createDeviceWithToken(array $data): array
    {
        // Generate token and secret
        ['token' => $plainToken, 'secret_key' => $secretKey] = Device::generateTokenPair();

        // Create device with hashed token
        $device = Device::create([
            ...$data,
            'api_token' => Device::hashToken($plainToken),
            'secret_key' => $secretKey,
            'token_expires_at' => now()->addDays(30),
            'last_token_rotated_at' => now(),
        ]);

        // Return plain tokens (only shown once)
        return [
            'device' => $device,
            'token' => $plainToken,
            'secret_key' => $secretKey,
            'message' => 'Save these credentials securely. You will not see them again.',
        ];
    }

    /**
     * Rotate device token
     */
    public function rotateToken(Device $device): array
    {
        ['token' => $plainToken, 'secret_key' => $secretKey] = Device::generateTokenPair();

        $device->update([
            'api_token' => Device::hashToken($plainToken),
            'secret_key' => $secretKey,
            'token_expires_at' => now()->addDays(30),
            'last_token_rotated_at' => now(),
            'is_active' => true,  // Reactivate if was expired
        ]);

        return [
            'token' => $plainToken,
            'secret_key' => $secretKey,
            'expires_at' => $device->token_expires_at,
        ];
    }

    /**
     * Validate request signature using HMAC-SHA256
     */
    public function validateSignature(Device $device, string $timestamp, string $signature, string $body): bool
    {
        // Check timestamp is recent (within 5 minutes) to prevent replay attacks
        $requestTime = intval($timestamp);
        $timeDifference = abs(time() - $requestTime);
        
        if ($timeDifference > 300) {  // 5 minute window
            return false;
        }

        // Recreate signature using device's secret key
        $dataToSign = $timestamp . $body;
        $expectedSignature = hash_hmac('sha256', $dataToSign, $device->secret_key);

        // Constant-time comparison (prevents timing attacks)
        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Hash a token using SHA256
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Verify a plain token against a hashed token
     */
    public function verifyToken(string $plainToken, string $hashedToken): bool
    {
        return hash_equals($this->hashToken($plainToken), $hashedToken);
    }

    /**
     * Generate a token pair (access and refresh tokens)
     */
    public function generateTokenPair(): array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ];
    }
}
