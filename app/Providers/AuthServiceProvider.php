<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Services\JwtService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Document::class => DocumentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('jwt', function ($request) {
            $token = $request->bearerToken();
            if (!$token) {
                return null;
            }

            try {
                $payload = app(JwtService::class)->decodeAndValidate($token);
            } catch (\Throwable $e) {
                return null;
            }

            $sub = $payload['sub'] ?? null;
            if (!is_string($sub) || $sub === '') {
                return null;
            }

            return User::query()->find($sub);
        });
    }
}
