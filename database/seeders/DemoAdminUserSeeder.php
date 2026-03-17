<?php

namespace Database\Seeders;

use App\Models\PasswordCredential;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoAdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (!$email || !$password) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'phone' => null,
                'first_name' => env('SEED_ADMIN_FIRST_NAME', 'Admin'),
                'last_name' => env('SEED_ADMIN_LAST_NAME', 'User'),
                'display_name' => null,
                'status' => 'ACTIVE',
                'last_login_at' => null,
            ]
        );

        PasswordCredential::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'password_hash' => Hash::make($password),
                'failed_login_count' => 0,
                'locked_until' => null,
                'password_changed_at' => Carbon::now(),
            ]
        );

        $adminRole = Role::query()->where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([
                $adminRole->getKey() => [
                    'assigned_at' => Carbon::now(),
                    'assigned_by' => $user->getKey(),
                ],
            ]);
        }
    }
}

