<?php

namespace Tests\Feature;

use App\Models\PasswordCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthJwtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => 'test-secret']);
        config(['jwt.ttl_minutes' => 15]);
        config(['jwt.refresh_ttl_days' => 30]);
    }

    public function test_login_and_me()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        PasswordCredential::query()->create([
            'user_id' => $user->getKey(),
            'password_hash' => Hash::make('Password123!'),
            'failed_login_count' => 0,
            'locked_until' => null,
            'password_changed_at' => now(),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $res->assertOk();
        $this->assertIsString($res->json('access_token'));
        $this->assertIsString($res->json('refresh_token'));

        $me = $this->withHeader('Authorization', 'Bearer '.$res->json('access_token'))
            ->getJson('/api/v1/auth/me');

        $me->assertOk();
        $me->assertJsonPath('email', 'test@example.com');
    }

    public function test_refresh_rotates_refresh_token()
    {
        $user = User::factory()->create(['email' => 'test2@example.com']);
        PasswordCredential::query()->create([
            'user_id' => $user->getKey(),
            'password_hash' => Hash::make('Password123!'),
            'failed_login_count' => 0,
            'locked_until' => null,
            'password_changed_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'test2@example.com',
            'password' => 'Password123!',
        ])->assertOk();

        $refreshToken = $login->json('refresh_token');

        $refresh = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk();

        $this->assertNotSame($login->json('access_token'), $refresh->json('access_token'));
        $this->assertNotSame($refreshToken, $refresh->json('refresh_token'));

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(401);
    }
}

