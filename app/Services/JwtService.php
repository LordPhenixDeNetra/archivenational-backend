<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    public function issueAccessToken(User $user): array
    {
        $secret = config('jwt.secret');
        if (!$secret) {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        $iat = Carbon::now()->timestamp;
        $ttlMinutes = (int) config('jwt.ttl_minutes', 15);
        $exp = Carbon::now()->addMinutes($ttlMinutes)->timestamp;

        $payload = [
            'sub' => $user->getKey(),
            'iat' => $iat,
            'exp' => $exp,
            'jti' => Str::uuid()->toString(),
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
        ];

        $token = $this->encode($payload, $secret);

        return [
            'token' => $token,
            'expires_in' => $exp - $iat,
        ];
    }

    public function decodeAndValidate(string $token): array
    {
        $secret = config('jwt.secret');
        if (!$secret) {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('Invalid token encoding.');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('Unsupported token algorithm.');
        }

        $signedPart = $encodedHeader.'.'.$encodedPayload;
        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $signedPart, $secret, true));
        if (!hash_equals($expectedSignature, $encodedSignature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        $now = Carbon::now()->timestamp;
        $exp = $payload['exp'] ?? null;
        if (!is_int($exp) && !ctype_digit((string) $exp)) {
            throw new RuntimeException('Invalid token exp.');
        }

        if ((int) $exp < $now) {
            throw new RuntimeException('Token expired.');
        }

        return $payload;
    }

    private function encode(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signedPart = $encodedHeader.'.'.$encodedPayload;
        $signature = hash_hmac('sha256', $signedPart, $secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return $signedPart.'.'.$encodedSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url encoding.');
        }

        return $decoded;
    }
}

