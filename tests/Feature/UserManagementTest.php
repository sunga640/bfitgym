<?php

use App\Livewire\Users\Form as UserForm;
use App\Livewire\Users\Index as UserIndex;
use App\Models\Branch;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create permissions
    $permissions = [
        'view users',
        'create users',
        'edit users',
        'delete users',
        'assign roles',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create roles
    $this->super_admin_role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $this->super_admin_role->syncPermissions(Permission::all());

    $this->manager_role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $this->manager_role->syncPermissions(['view users']);

    // Create a branch
    $this->branch = Branch::factory()->create(['name' => 'Test Branch', 'code' => 'TST']);

    // Create super admin user
    $this->super_admin = User::factory()->create([
        'name' => 'Super Admin',
        'email' => 'superadmin@test.com',
    ]);
    $this->super_admin->assignRole('super-admin');

    // Create regular user
    $this->regular_user = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'regular@test.com',
        'branch_id' => $this->branch->id,
    ]);
    $this->regular_user->assignRole('manager');
});

describe('User Index', function () {
    it('renders for authenticated user', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->assertStatus(200)
            ->assertSee('Super Admin')
            ->assertSee('Regular User');
    });

    it('can search users by name', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->set('search', 'Super')
            ->assertSee('Super Admin')
            ->assertDontSee('Regular User');
    });

    it('can search users by email', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->set('search', 'regular@test.com')
            ->assertDontSee('Super Admin')
            ->assertSee('Regular User');
    });

    it('can filter users by role', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->set('role_filter', 'super-admin')
            ->assertSee('Super Admin')
            ->assertDontSee('Regular User');
    });

    it('can filter users by branch', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->set('branch_filter', $this->branch->id)
            ->assertDontSee('Super Admin')
            ->assertSee('Regular User');
    });

    it('can delete a user', function () {
        $this->actingAs($this->super_admin);

        $user_to_delete = User::factory()->create(['name' => 'Delete Me']);

        Livewire::test(UserIndex::class)
            ->call('deleteUser', $user_to_delete->id)
            ->assertHasNoErrors();

        expect(User::find($user_to_delete->id))->toBeNull();
    });

    it('prevents self-deletion', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserIndex::class)
            ->call('deleteUser', $this->super_admin->id);

        expect(User::find($this->super_admin->id))->not->toBeNull();
    });
});

describe('User Form - Create', function () {
    it('renders create form for super admin', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserForm::class)
            ->assertStatus(200)
            ->assertSee('Create User');
    });

    it('validates required fields', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserForm::class)
            ->set('name', '')
            ->set('email', '')
            ->set('password', '')
            ->set('selected_roles', [])
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password', 'selected_roles']);
    });

    it('validates unique email', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'superadmin@test.com') // existing email
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('selected_roles', ['manager'])
            ->call('save')
            ->assertHasErrors(['email']);
    });

    it('can create a new user', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'newuser@test.com')
            ->set('phone', '+255754000000')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('branch_id', $this->branch->id)
            ->set('selected_roles', ['manager'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $new_user = User::where('email', 'newuser@test.com')->first();
        expect($new_user)->not->toBeNull();
        expect($new_user->name)->toBe('New User');
        expect($new_user->phone)->toBe('+255754000000');
        expect($new_user->branch_id)->toBe($this->branch->id);
        expect($new_user->hasRole('manager'))->toBeTrue();
    });
});

describe('User Form - Edit', function () {
    it('renders edit form with existing data', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(UserForm::class, ['user' => $this->regular_user])
            ->assertStatus(200)
            ->assertSet('name', 'Regular User')
            ->assertSet('email', 'regular@test.com')
            ->assertSet('is_editing', true);
    });

    it('can update user without changing password', function () {
        $this->actingAs($this->super_admin);

        $old_password = $this->regular_user->password;

        Livewire::test(UserForm::class, ['user' => $this->regular_user])
            ->set('name', 'Updated Name')
            ->set('email', 'updated@test.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->regular_user->refresh();
        expect($this->regular_user->name)->toBe('Updated Name');
        expect($this->regular_user->email)->toBe('updated@test.com');
        expect($this->regular_user->password)->toBe($old_password);
    });

    it('can update user password', function () {
        $this->actingAs($this->super_admin);

        $old_password = $this->regular_user->password;

        Livewire::test(UserForm::class, ['user' => $this->regular_user])
            ->set('password', 'NewPassword123!')
            ->set('password_confirmation', 'NewPassword123!')
            ->call('save')
            ->assertHasNoErrors();

        $this->regular_user->refresh();
        expect($this->regular_user->password)->not->toBe($old_password);
    });

    it('can update user roles', function () {
        $this->actingAs($this->super_admin);

        expect($this->regular_user->hasRole('manager'))->toBeTrue();
        expect($this->regular_user->hasRole('super-admin'))->toBeFalse();

        Livewire::test(UserForm::class, ['user' => $this->regular_user])
            ->set('selected_roles', ['super-admin'])
            ->call('save')
            ->assertHasNoErrors();

        $this->regular_user->refresh();
        expect($this->regular_user->hasRole('manager'))->toBeFalse();
        expect($this->regular_user->hasRole('super-admin'))->toBeTrue();
    });
});

describe('User Authorization', function () {
    it('regular user cannot access user creation', function () {
        $user = User::factory()->create();
        // No roles assigned

        $this->actingAs($user);

        Livewire::test(UserForm::class)
            ->assertForbidden();
    });

    it('user with permission can access users', function () {
        $this->actingAs($this->regular_user);

        Livewire::test(UserIndex::class)
            ->assertStatus(200);
    });
});
