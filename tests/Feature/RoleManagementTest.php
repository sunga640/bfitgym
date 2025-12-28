<?php

use App\Livewire\Roles\Form as RoleForm;
use App\Livewire\Roles\Index as RoleIndex;
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
        'view members',
        'create members',
        'edit members',
        'delete members',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create system roles
    $this->super_admin_role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $this->super_admin_role->syncPermissions(Permission::all());

    $this->branch_admin_role = Role::firstOrCreate(['name' => 'branch-admin', 'guard_name' => 'web']);
    $this->branch_admin_role->syncPermissions(['view users', 'view members']);

    // Create custom role
    $this->custom_role = Role::firstOrCreate(['name' => 'custom-role', 'guard_name' => 'web']);
    $this->custom_role->syncPermissions(['view users']);

    // Create super admin user
    $this->super_admin = User::factory()->create([
        'name' => 'Super Admin',
        'email' => 'superadmin@test.com',
    ]);
    $this->super_admin->assignRole('super-admin');
});

describe('Role Index', function () {
    it('renders for super admin', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleIndex::class)
            ->assertStatus(200)
            ->assertSee('Super Admin')
            ->assertSee('Branch Admin')
            ->assertSee('Custom Role');
    });

    it('can search roles', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleIndex::class)
            ->set('search', 'custom')
            ->assertSee('Custom Role')
            ->assertDontSee('Super Admin');
    });

    it('shows user and permission counts', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleIndex::class)
            ->assertSee('1 user'); // super-admin has 1 user
    });

    it('can delete custom roles', function () {
        $this->actingAs($this->super_admin);

        $role_to_delete = Role::create(['name' => 'delete-me', 'guard_name' => 'web']);

        Livewire::test(RoleIndex::class)
            ->call('deleteRole', $role_to_delete->id)
            ->assertHasNoErrors();

        expect(Role::find($role_to_delete->id))->toBeNull();
    });

    it('cannot delete system roles', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleIndex::class)
            ->call('deleteRole', $this->super_admin_role->id);

        expect(Role::find($this->super_admin_role->id))->not->toBeNull();
    });

    it('cannot delete roles with assigned users', function () {
        $this->actingAs($this->super_admin);

        // Create a user with custom role
        $user = User::factory()->create();
        $user->assignRole($this->custom_role);

        Livewire::test(RoleIndex::class)
            ->call('deleteRole', $this->custom_role->id);

        expect(Role::find($this->custom_role->id))->not->toBeNull();
    });
});

describe('Role Form - Create', function () {
    it('renders create form', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->assertStatus(200)
            ->assertSee('Create Role');
    });

    it('validates required fields', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->set('name', '')
            ->set('selected_permissions', [])
            ->call('save')
            ->assertHasErrors(['name', 'selected_permissions']);
    });

    it('validates role name format', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'Invalid Name With Spaces')
            ->set('selected_permissions', ['view users'])
            ->call('save')
            ->assertHasErrors(['name']);
    });

    it('validates unique role name', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'super-admin')
            ->set('selected_permissions', ['view users'])
            ->call('save')
            ->assertHasErrors(['name']);
    });

    it('can create a new role', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'new-role')
            ->set('selected_permissions', ['view users', 'view members'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('roles.index'));

        $new_role = Role::where('name', 'new-role')->first();
        expect($new_role)->not->toBeNull();
        expect($new_role->permissions->pluck('name')->toArray())->toContain('view users');
        expect($new_role->permissions->pluck('name')->toArray())->toContain('view members');
    });
});

describe('Role Form - Edit', function () {
    it('renders edit form with existing data', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class, ['role' => $this->custom_role])
            ->assertStatus(200)
            ->assertSet('name', 'custom-role')
            ->assertSet('is_editing', true);
    });

    it('can update role name for custom roles', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class, ['role' => $this->custom_role])
            ->set('name', 'updated-role')
            ->set('selected_permissions', ['view users'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('roles.index'));

        $this->custom_role->refresh();
        expect($this->custom_role->name)->toBe('updated-role');
    });

    it('cannot update system role name', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class, ['role' => $this->super_admin_role])
            ->assertSet('is_system_role', true);

        // The name field should be disabled for system roles
        // Only permissions can be updated
    });

    it('can update role permissions', function () {
        $this->actingAs($this->super_admin);

        expect($this->custom_role->hasPermissionTo('create members'))->toBeFalse();

        Livewire::test(RoleForm::class, ['role' => $this->custom_role])
            ->set('selected_permissions', ['view users', 'create members'])
            ->call('save')
            ->assertHasNoErrors();

        $this->custom_role->refresh();
        expect($this->custom_role->hasPermissionTo('view users'))->toBeTrue();
        expect($this->custom_role->hasPermissionTo('create members'))->toBeTrue();
    });
});

describe('Role Form - Bulk Selection', function () {
    it('can select all permissions', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->assertSet('selected_permissions', [])
            ->call('selectAll')
            ->assertSet('selected_permissions', Permission::pluck('name')->toArray());
    });

    it('can deselect all permissions', function () {
        $this->actingAs($this->super_admin);

        Livewire::test(RoleForm::class)
            ->set('selected_permissions', ['view users', 'view members'])
            ->call('deselectAll')
            ->assertSet('selected_permissions', []);
    });
});
