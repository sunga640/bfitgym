<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin_role = Role::query()->firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $user = User::query()->firstOrCreate(
            ['email' => 'abc@example.com'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => ('1234aBcD!'),
                'role_hint' => 'super-admin',
                'branch_id' => null,
            ]
        );

        if (! $user->hasRole($super_admin_role->name)) {
            $user->assignRole($super_admin_role);
        }
    }
}
