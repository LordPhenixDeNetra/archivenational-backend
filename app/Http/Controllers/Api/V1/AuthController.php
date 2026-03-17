<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PasswordCredential;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\AuditService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, JwtService $jwt, AuditService $audit)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $email = mb_strtolower($data['email']);
        $rateKey = $request->ip().'|'.$email;
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            return response()->json(['message' => 'Too Many Attempts'], 429);
        }

        $user = User::query()->where('email', $email)->first();
        if (!$user || $user->status !== 'ACTIVE') {
            RateLimiter::hit($rateKey, 60);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $credential = PasswordCredential::query()->where('user_id', $user->getKey())->first();
        if (!$credential) {
            RateLimiter::hit($rateKey, 60);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($credential->locked_until && $credential->locked_until->isFuture()) {
            return response()->json(['message' => 'Account locked'], 423);
        }

        if (!Hash::check($data['password'], $credential->password_hash)) {
            RateLimiter::hit($rateKey, 60);

            $credential->failed_login_count = (int) $credential->failed_login_count + 1;
            if ($credential->failed_login_count >= 5) {
                $credential->locked_until = Carbon::now()->addMinutes(15);
            }
            $credential->save();

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        RateLimiter::clear($rateKey);

        $credential->failed_login_count = 0;
        $credential->locked_until = null;
        $credential->password_changed_at = $credential->password_changed_at ?? Carbon::now();
        $credential->save();

        $user->last_login_at = Carbon::now();
        $user->save();

        $access = $jwt->issueAccessToken($user);
        [$refreshPlain, $refresh] = $this->issueRefreshToken($user, $request);

        $audit->record($user, 'auth.login', 'User', $user->getKey(), [], $request);

        return response()->json([
            'access_token' => $access['token'],
            'access_token_expires_in' => $access['expires_in'],
            'refresh_token' => $refreshPlain,
            'refresh_token_expires_in' => Carbon::parse($refresh->expires_at)->diffInSeconds(Carbon::now()),
            'token_type' => 'Bearer',
        ]);
    }

    public function refresh(Request $request, JwtService $jwt, AuditService $audit)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        $existing = RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if (!$existing || Carbon::parse($existing->expires_at)->isPast()) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = User::query()->find($existing->user_id);
        if (!$user || $user->status !== 'ACTIVE') {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $existing->revoked_at = Carbon::now();
        $existing->save();

        $access = $jwt->issueAccessToken($user);
        [$refreshPlain, $refresh] = $this->issueRefreshToken($user, $request);

        $audit->record($user, 'auth.refresh', 'User', $user->getKey(), [], $request);

        return response()->json([
            'access_token' => $access['token'],
            'access_token_expires_in' => $access['expires_in'],
            'refresh_token' => $refreshPlain,
            'refresh_token_expires_in' => Carbon::parse($refresh->expires_at)->diffInSeconds(Carbon::now()),
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);

        $audit->record($request->user(), 'auth.logout', 'User', $request->user()?->getKey(), [], $request);

        return response()->noContent();
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id' => $user->getKey(),
            'email' => $user->email,
            'phone' => $user->phone,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'status' => $user->status,
            'last_login_at' => $user->last_login_at,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return response()->json(['message' => 'If the account exists, a reset link will be sent.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        return response()->json(['message' => 'If the token is valid, the password will be updated.']);
    }

    private function issueRefreshToken(User $user, Request $request): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $hash = hash('sha256', $plain);

        $refresh = RefreshToken::query()->create([
            'user_id' => $user->getKey(),
            'token_hash' => $hash,
            'expires_at' => Carbon::now()->addDays((int) config('jwt.refresh_ttl_days', 30)),
            'revoked_at' => null,
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'ip' => $request->ip(),
            'created_at' => Carbon::now(),
        ]);

        return [$plain, $refresh];
    }
}

