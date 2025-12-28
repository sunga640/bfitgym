<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $hq_user;
    protected User $branch_user;
    protected Branch $branch_a;
    protected Branch $branch_b;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view branches']);
        Permission::create(['name' => 'create branches']);
        Permission::create(['name' => 'edit branches']);
        Permission::create(['name' => 'delete branches']);
        Permission::create(['name' => 'manage branch status']);
        Permission::create(['name' => 'switch branches']);
        Permission::create(['name' => 'manage branches']);

        // Create roles
        $super_admin_role = Role::create(['name' => 'super-admin']);
        $hq_role = Role::create(['name' => 'hq-admin']);
        $hq_role->givePermissionTo([
            'view branches',
            'edit branches',
            'manage branch status',
            'switch branches',
        ]);

        $branch_admin_role = Role::create(['name' => 'branch-admin']);
        $branch_admin_role->givePermissionTo([
            'view branches',
            'edit branches',
        ]);

        // Create branches
        $this->branch_a = Branch::factory()->create([
            'name' => 'Branch A',
            'code' => 'BRA',
            'status' => 'active',
        ]);

        $this->branch_b = Branch::factory()->create([
            'name' => 'Branch B',
            'code' => 'BRB',
            'status' => 'active',
        ]);

        // Create HQ user (can switch branches)
        $this->hq_user = User::factory()->create([
            'branch_id' => $this->branch_a->id,
        ]);
        $this->hq_user->assignRole('hq-admin');

        // Create branch user (can only see their branch)
        $this->branch_user = User::factory()->create([
            'branch_id' => $this->branch_a->id,
        ]);
        $this->branch_user->assignRole('branch-admin');
    }

    public function test_branches_index_loads_for_hq_user(): void
    {
        $this->actingAs($this->hq_user);

        $response = $this->get(route('organization.branches.index'));

        $response->assertStatus(200);
        $response->assertSee('Branch A');
        $response->assertSee('Branch B');
    }

    public function test_branches_index_shows_only_own_branch_for_branch_user(): void
    {
        $this->actingAs($this->branch_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Index::class);

        $response->assertStatus(200);
        $response->assertSee('Branch A');
        $response->assertDontSee('Branch B');
    }

    public function test_branch_show_denies_access_for_non_authorized_user(): void
    {
        $unauthorized_user = User::factory()->create(['branch_id' => null]);

        $this->actingAs($unauthorized_user);

        $response = $this->get(route('organization.branches.show', $this->branch_a));

        $response->assertStatus(403);
    }

    public function test_branch_show_allows_access_for_hq_user(): void
    {
        $this->actingAs($this->hq_user);

        $response = $this->get(route('organization.branches.show', $this->branch_a));

        $response->assertStatus(200);
        $response->assertSee('Branch A');
    }

    public function test_branch_user_can_only_view_their_own_branch(): void
    {
        $this->actingAs($this->branch_user);

        // Can view own branch
        $response = $this->get(route('organization.branches.show', $this->branch_a));
        $response->assertStatus(200);

        // Cannot view other branch
        $response = $this->get(route('organization.branches.show', $this->branch_b));
        $response->assertStatus(403);
    }

    public function test_switch_branch_updates_session_and_redirects(): void
    {
        $this->actingAs($this->hq_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Show::class, [
            'branch' => $this->branch_b,
        ])->call('switchToBranch');

        $response->assertRedirect(route('dashboard'));

        // Check session was updated
        $branch_context = app(BranchContext::class);
        $this->assertEquals($this->branch_b->id, $branch_context->getCurrentBranchId());
    }

    public function test_branch_user_cannot_switch_branches(): void
    {
        $this->actingAs($this->branch_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Show::class, [
            'branch' => $this->branch_a,
        ]);

        // Should not see switch button
        $this->assertFalse($response->get('canSwitchToBranch'));
    }

    public function test_settings_update_validates_and_persists(): void
    {
        $this->actingAs($this->hq_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Settings::class, [
            'branch' => $this->branch_a,
        ])
            ->set('name', 'Updated Branch Name')
            ->set('city', 'New City')
            ->set('phone', '+255 123 456 789')
            ->call('save');

        $response->assertHasNoErrors();

        $this->branch_a->refresh();
        $this->assertEquals('Updated Branch Name', $this->branch_a->name);
        $this->assertEquals('New City', $this->branch_a->city);
        $this->assertEquals('+255 123 456 789', $this->branch_a->phone);
    }

    public function test_settings_validates_required_fields(): void
    {
        $this->actingAs($this->hq_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Settings::class, [
            'branch' => $this->branch_a,
        ])
            ->set('name', '')
            ->call('save');

        $response->assertHasErrors(['name' => 'required']);
    }

    public function test_settings_validates_unique_code(): void
    {
        $this->actingAs($this->hq_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Settings::class, [
            'branch' => $this->branch_a,
        ])
            ->set('code', 'BRB') // Branch B's code
            ->call('save');

        $response->assertHasErrors(['code' => 'unique']);
    }

    public function test_hq_user_can_deactivate_branch(): void
    {
        $this->actingAs($this->hq_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Settings::class, [
            'branch' => $this->branch_a,
        ])
            ->call('confirmDeactivate')
            ->call('deactivateBranch');

        $response->assertHasNoErrors();

        $this->branch_a->refresh();
        $this->assertEquals('inactive', $this->branch_a->status);
    }

    public function test_branch_user_cannot_deactivate_branch(): void
    {
        $this->actingAs($this->branch_user);

        $response = Livewire::test(\App\Livewire\Organization\Branches\Settings::class, [
            'branch' => $this->branch_a,
        ]);

        // Should not have manage status permission
        $this->assertFalse($response->get('canManageStatus'));
    }

    public function test_branch_context_initializes_on_login(): void
    {
        $branch_context = app(BranchContext::class);

        // Simulate login
        $this->actingAs($this->branch_user);
        $branch_context->initializeOnLogin($this->branch_user);

        $this->assertEquals($this->branch_a->id, $branch_context->getCurrentBranchId());
    }

    public function test_hq_user_sees_all_branches_in_dropdown(): void
    {
        $this->actingAs($this->hq_user);

        $branch_context = app(BranchContext::class);
        $accessible_branches = $branch_context->getAccessibleBranches($this->hq_user);

        $this->assertEquals(2, $accessible_branches->count());
        $this->assertTrue($accessible_branches->contains($this->branch_a));
        $this->assertTrue($accessible_branches->contains($this->branch_b));
    }

    public function test_branch_user_sees_only_own_branch_in_dropdown(): void
    {
        $this->actingAs($this->branch_user);

        $branch_context = app(BranchContext::class);
        $accessible_branches = $branch_context->getAccessibleBranches($this->branch_user);

        $this->assertEquals(1, $accessible_branches->count());
        $this->assertTrue($accessible_branches->contains($this->branch_a));
        $this->assertFalse($accessible_branches->contains($this->branch_b));
    }
}

