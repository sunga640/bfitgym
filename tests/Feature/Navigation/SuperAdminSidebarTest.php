<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('shows expense links in sidebar for super admin users', function () {
    $role = Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Memberships');
    $response->assertSee('Expenses');
    $response->assertSee('Expense Categories');
});

