<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);

    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertStatus(200);
});
