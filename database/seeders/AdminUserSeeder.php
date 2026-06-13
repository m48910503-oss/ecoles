<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@ecoles.test'],
            [
                'name' => 'Administrateur système',
                'password' => Hash::make('Admin@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $administratorRole = Role::query()->where('slug', 'administrateur')->first();

        if ($administratorRole) {
            $adminUser->roles()->sync([
                $administratorRole->id => [
                    'assigned_at' => now(),
                    'assigned_by' => $adminUser->id,
                ],
            ]);
        }
    }
}
