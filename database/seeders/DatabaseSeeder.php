<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect(RoleCode::cases())->mapWithKeys(function (RoleCode $roleCode) {
            $role = Role::firstOrCreate(
                ['code' => $roleCode->value],
                [
                    'name' => str_replace('_', ' ', $roleCode->value),
                    'description' => 'Seeded system role',
                ],
            );

            return [$roleCode->value => $role];
        });

        $user = User::firstOrCreate(
            ['email' => 'developer@example.com'],
            [
                'auth_user_id' => 'auth-dev-' . uniqid(),
                'full_name' => 'Developer Access',
                'password' => Hash::make('password'),
                'status' => \App\Enums\UserStatus::ACTIVE,
            ],
        );

        $user->roles()->syncWithoutDetaching([
            $roles[RoleCode::DEVELOPER->value]->id,
            $roles[RoleCode::SUPER_ADMIN->value]->id,
        ]);
    }
}
