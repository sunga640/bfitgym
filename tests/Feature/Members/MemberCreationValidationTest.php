<?php

use App\Livewire\Members\Form as MemberForm;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['create members', 'view members', 'switch branches'] as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }

    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->user->givePermissionTo(['create members', 'view members', 'switch branches']);
    $this->actingAs($this->user);
});

it('normalizes member phone to 255 format on create', function (string $raw_phone, string $expected_phone) {
    $email_local_part = preg_replace('/\D+/', '', $raw_phone) ?: 'member';

    Livewire::test(MemberForm::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('gender', 'female')
        ->set('phone', $raw_phone)
        ->set('email', "JANE.DOE.{$email_local_part}@Example.com")
        ->call('save')
        ->assertHasNoErrors();

    $member = Member::query()->latest('id')->first();

    expect($member)->not->toBeNull();
    expect($member->phone)->toBe($expected_phone);
    expect($member->email)->toBe(strtolower("JANE.DOE.{$email_local_part}@Example.com"));
})->with([
    ['0755667788', '255755667788'],
    ['0655667788', '255655667788'],
    ['+255755667799', '255755667799'],
]);

it('rejects duplicate phone when formatted differently', function () {
    Member::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => '+255755667788',
        'email' => 'first@example.com',
    ]);

    Livewire::test(MemberForm::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Smith')
        ->set('gender', 'male')
        ->set('phone', '0755667788')
        ->set('email', 'second@example.com')
        ->call('save')
        ->assertHasErrors(['phone' => 'unique']);
});

it('rejects duplicate email regardless of letter case', function () {
    Member::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => '0755000001',
        'email' => 'member@example.com',
    ]);

    Livewire::test(MemberForm::class)
        ->set('first_name', 'Alice')
        ->set('last_name', 'Johnson')
        ->set('gender', 'female')
        ->set('phone', '0755000002')
        ->set('email', 'MEMBER@EXAMPLE.COM')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

it('requires gender when creating a member', function () {
    Livewire::test(MemberForm::class)
        ->set('first_name', 'No')
        ->set('last_name', 'Gender')
        ->set('phone', '0755000010')
        ->set('email', 'nogender@example.com')
        ->set('gender', '')
        ->call('save')
        ->assertHasErrors(['gender' => 'required']);
});

it('switches to the created member branch so the redirected list shows it', function () {
    $target_branch = Branch::factory()->create();
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $this->user->assignRole('super-admin');

    Livewire::test(MemberForm::class)
        ->set('branch_id', $target_branch->id)
        ->set('first_name', 'Visible')
        ->set('last_name', 'Member')
        ->set('gender', 'female')
        ->set('phone', '0755000011')
        ->call('save')
        ->assertHasNoErrors();

    expect(Session::get('current_branch_id'))->toBe($target_branch->id);
});
