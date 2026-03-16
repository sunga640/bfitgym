<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Auth Routes
use App\Livewire\Dashboard;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Models\MemberSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Calendar
    Route::get('calendar', \App\Livewire\Calendar\Index::class)->name('calendar.index');

    /*
    |--------------------------------------------------------------------------
    | Organization & Branch Management
    |--------------------------------------------------------------------------
    */

    // Branches (legacy routes - redirect to organization routes)
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::view('/', 'branches.index')->name('index');
        Route::view('/create', 'branches.create')->name('create');
        Route::view('/{branch}', 'branches.show')->name('show');
        Route::view('/{branch}/edit', 'branches.edit')->name('edit');
    });

    // Organization: Branches
    Route::prefix('organization/branches')->name('organization.branches.')->group(function () {
        Route::get('/', \App\Livewire\Organization\Branches\Index::class)->name('index');
        Route::get('/{branch}', \App\Livewire\Organization\Branches\Show::class)->name('show');
        Route::get('/{branch}/settings', \App\Livewire\Organization\Branches\Settings::class)->name('settings');
    });

    // Users / Staff
    Route::prefix('users')->name('users.')->group(function () {
        Route::view('/', 'users.index')->name('index');
        Route::view('/create', 'users.create')->name('create');
        Route::get('/{user}', fn(\App\Models\User $user) => view('users.show', compact('user')))->name('show');
        Route::view('/{user}/edit', 'users.edit')->name('edit');
    });

    // Roles & Permissions
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::view('/', 'roles.index')->name('index');
        Route::view('/create', 'roles.create')->name('create');
        Route::view('/{role}/edit', 'roles.edit')->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Members
    |--------------------------------------------------------------------------
    */

    Route::prefix('members')->name('members.')->group(function () {
        Route::view('/', 'members.index')->name('index');
        Route::view('/create', 'members.create')->name('create');
        Route::get('/{member}', fn(\App\Models\Member $member) => view('members.show', compact('member')))->name('show');
        Route::get('/{member}/edit', fn(\App\Models\Member $member) => view('members.edit', compact('member')))->name('edit');
    });

    // Insurers
    Route::prefix('insurers')->name('insurers.')->group(function () {
        Route::view('/', 'insurers.index')->name('index');
        Route::view('/create', 'insurers.create')->name('create');
        Route::get('/{insurer}', fn(\App\Models\Insurer $insurer) => view('insurers.show', compact('insurer')))->name('show');
        Route::get('/{insurer}/edit', fn(\App\Models\Insurer $insurer) => view('insurers.edit', compact('insurer')))->name('edit');
    });

    // Member Insurance Policies
    Route::prefix('member-insurances')->name('member-insurances.')->group(function () {
        Route::view('/', 'member-insurances.index')->name('index');
        Route::view('/create', 'member-insurances.create')->name('create');
        Route::get('/{memberInsurance}', \App\Livewire\MemberInsurances\Show::class)->name('show');
        Route::get('/{memberInsurance}/edit', fn(\App\Models\MemberInsurance $memberInsurance) => view('member-insurances.edit', compact('memberInsurance')))->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Memberships & Subscriptions
    |--------------------------------------------------------------------------
    */

    Route::prefix('membership-packages')->name('membership-packages.')->group(function () {
        Route::view('/', 'membership-packages.index')->name('index');
        Route::view('/create', 'membership-packages.create')->name('create');
        Route::view('/{membershipPackage}/edit', 'membership-packages.edit')->name('edit');
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::view('/', 'subscriptions.index')->name('index');
        Route::view('/create', 'subscriptions.create')->name('create');
        Route::get('/{subscription}/edit', function (MemberSubscription $subscription) {
            return view('subscriptions.edit', compact('subscription'));
        })->name('edit');
        Route::get('/{subscription}', function (MemberSubscription $subscription) {
            return view('subscriptions.show', compact('subscription'));
        })->name('show');
        Route::get('/{subscription}/renew', function (MemberSubscription $subscription) {
            return view('subscriptions.renew', compact('subscription'));
        })->name('renew');
    });

    /*
    |--------------------------------------------------------------------------
    | Classes & Bookings
    |--------------------------------------------------------------------------
    */

    Route::prefix('class-types')->name('class-types.')->group(function () {
        Route::view('/', 'class-types.index')->name('index');
        Route::view('/create', 'class-types.create')->name('create');
        Route::get('/{classType}/edit', fn(\App\Models\ClassType $classType) => view('class-types.edit', compact('classType')))->name('edit');
    });

    Route::prefix('class-sessions')->name('class-sessions.')->group(function () {
        Route::view('/', 'class-sessions.index')->name('index');
        Route::view('/create', 'class-sessions.create')->name('create');
        Route::get('/{classSession}', fn(\App\Models\ClassSession $classSession) => view('class-sessions.show', compact('classSession')))->name('show');
        Route::get('/{classSession}/edit', fn(\App\Models\ClassSession $classSession) => view('class-sessions.edit', compact('classSession')))->name('edit');
    });

    Route::prefix('class-bookings')->name('class-bookings.')->group(function () {
        Route::view('/', 'class-bookings.index')->name('index');
        Route::view('/create', 'class-bookings.create')->name('create');
    });

    // Equipment Allocations - update routes to pass models
    Route::prefix('equipment-allocations')->name('equipment-allocations.')->group(function () {
        Route::view('/', 'equipment-allocations.index')->name('index');
        Route::view('/create', 'equipment-allocations.create')->name('create');
        Route::get('/{equipmentAllocation}/edit', fn(\App\Models\EquipmentAllocation $equipmentAllocation) => view('equipment-allocations.edit', compact('equipmentAllocation')))->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Workouts & Training
    |--------------------------------------------------------------------------
    */

    Route::prefix('exercises')->name('exercises.')->group(function () {
        Route::view('/', 'exercises.index')->name('index');
        Route::view('/create', 'exercises.create')->name('create');
        Route::view('/{exercise}/edit', 'exercises.edit')->name('edit');
    });

    Route::prefix('workout-plans')->name('workout-plans.')->group(function () {
        Route::view('/', 'workout-plans.index')->name('index');
        Route::view('/create', 'workout-plans.create')->name('create');
        Route::get('/{workoutPlan}', fn(\App\Models\WorkoutPlan $workoutPlan) => view('workout-plans.show', compact('workoutPlan')))->name('show');
        Route::get('/{workoutPlan}/edit', fn(\App\Models\WorkoutPlan $workoutPlan) => view('workout-plans.edit', compact('workoutPlan')))->name('edit');
    });

    Route::prefix('member-workout-plans')->name('member-workout-plans.')->group(function () {
        Route::view('/', 'member-workout-plans.index')->name('index');
        Route::view('/assign', 'member-workout-plans.assign')->name('assign');
    });

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    Route::prefix('events')->name('events.')->group(function () {
        Route::view('/', 'events.index')->name('index');
        Route::view('/create', 'events.create')->name('create');
        Route::view('/{event}', 'events.show')->name('show');
        Route::view('/{event}/edit', 'events.edit')->name('edit');
    });

    Route::prefix('event-registrations')->name('event-registrations.')->group(function () {
        Route::view('/', 'event-registrations.index')->name('index');
        Route::view('/create', 'event-registrations.create')->name('create');
    });

    /*
    |--------------------------------------------------------------------------
    | POS & Inventory
    |--------------------------------------------------------------------------
    */

    // Point of Sale
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::view('/', 'pos.index')->name('index');
    });

    Route::prefix('pos-sales')->name('pos-sales.')->group(function () {
        Route::view('/', 'pos-sales.index')->name('index');
        Route::get('/{posSale}', fn(\App\Models\PosSale $posSale) => view('pos-sales.show', compact('posSale')))->name('show');
    });

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::view('/', 'products.index')->name('index');
        Route::view('/create', 'products.create')->name('create');
        Route::get('/{product}', fn(\App\Models\Product $product) => view('products.show', compact('product')))->name('show');
        Route::get('/{product}/edit', fn(\App\Models\Product $product) => view('products.edit', compact('product')))->name('edit');
    });

    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        Route::view('/', 'product-categories.index')->name('index');
    });

    // Branch Products / Stock
    Route::prefix('branch-products')->name('branch-products.')->group(function () {
        Route::view('/', 'branch-products.index')->name('index');
        Route::view('/{branchProduct}/edit', 'branch-products.edit')->name('edit');
    });

    Route::prefix('stock-adjustments')->name('stock-adjustments.')->group(function () {
        Route::view('/', 'stock-adjustments.index')->name('index');
        Route::view('/create', 'stock-adjustments.create')->name('create');
    });

    // Suppliers & Purchase Orders
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::view('/', 'suppliers.index')->name('index');
        Route::view('/create', 'suppliers.create')->name('create');
        Route::view('/{supplier}/edit', 'suppliers.edit')->name('edit');
    });

    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::view('/', 'purchase-orders.index')->name('index');
        Route::view('/create', 'purchase-orders.create')->name('create');
        Route::get('/{purchaseOrder}', fn(\App\Models\PurchaseOrder $purchaseOrder) => view('purchase-orders.show', compact('purchaseOrder')))->name('show');
        Route::get('/{purchaseOrder}/edit', fn(\App\Models\PurchaseOrder $purchaseOrder) => view('purchase-orders.edit', compact('purchaseOrder')))->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Access Control Integrations (HIKVision + ZKTeco)
    |--------------------------------------------------------------------------
    */

    // HIKVision (existing attendance/access-control domain)
    Route::prefix('hikvision')->name('hikvision.')->group(function () {
        Route::get('/', \App\Livewire\Hikvision\Overview::class)->name('overview');
        Route::get('/logs', \App\Livewire\Hikvision\Logs\Index::class)->name('logs.index');

        Route::get('/devices', \App\Livewire\AccessControl\Devices\Index::class)->name('devices.index');
        Route::get('/devices/create', \App\Livewire\AccessControl\Devices\Form::class)->name('devices.create');
        Route::get('/devices/{device}', \App\Livewire\AccessControl\Devices\Show::class)->name('devices.show');
        Route::get('/devices/{device}/edit', \App\Livewire\AccessControl\Devices\Form::class)->name('devices.edit');

        Route::get('/agents', \App\Livewire\AccessControl\Agents\Index::class)->name('agents.index');
        Route::get('/agents/{agent}', \App\Livewire\AccessControl\Agents\Show::class)->name('agents.show');

        Route::get('/enrollments', \App\Livewire\AccessControl\Enrollments\Index::class)->name('enrollments.index');

        Route::get('/identities', \App\Livewire\AccessIdentities\Index::class)->name('identities.index');
        Route::get('/identities/create', \App\Livewire\AccessIdentities\Form::class)->name('identities.create');
        Route::get('/identities/{identity}/edit', \App\Livewire\AccessIdentities\Form::class)->name('identities.edit');
    });

    // ZKTeco (ZKBio control-plane integration)
    Route::prefix('zkteco')->name('zkteco.')->group(function () {
        Route::get('/', \App\Livewire\Zkteco\Overview::class)->name('overview');
        Route::get('/logs', \App\Livewire\Zkteco\Logs\Index::class)->name('logs.index');
        Route::get('/events', \App\Livewire\Zkteco\Logs\Index::class)->name('events.index');
        Route::get('/settings', \App\Livewire\Zkteco\Settings::class)->name('settings');
    });

    /*
    |--------------------------------------------------------------------------
    | Legacy Integration Routes (Backward Compatibility)
    |--------------------------------------------------------------------------
    */

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::redirect('/', '/hikvision/logs')->name('index');
    });

    Route::prefix('access-devices')->name('access-devices.')->group(function () {
        Route::redirect('/', '/hikvision/devices')->name('index');
        Route::redirect('/create', '/hikvision/devices/create')->name('create');
        Route::get('/{accessControlDevice}/edit', fn(\App\Models\AccessControlDevice $accessControlDevice) => redirect()->route('hikvision.devices.edit', $accessControlDevice))->name('edit');
    });

    Route::prefix('access-identities')->name('access-identities.')->group(function () {
        Route::redirect('/', '/hikvision/identities')->name('index');
        Route::redirect('/create', '/hikvision/identities/create')->name('create');
        Route::get('/{accessIdentity}/edit', fn(\App\Models\AccessIdentity $accessIdentity) => redirect()->route('hikvision.identities.edit', $accessIdentity))->name('edit');
    });

    Route::prefix('access-control')->name('access-control.')->group(function () {
        Route::redirect('/devices', '/hikvision/devices')->name('devices.index');
        Route::redirect('/devices/create', '/hikvision/devices/create')->name('devices.create');
        Route::get('/devices/{device}', fn(\App\Models\AccessControlDevice $device) => redirect()->route('hikvision.devices.show', $device))->name('devices.show');
        Route::get('/devices/{device}/edit', fn(\App\Models\AccessControlDevice $device) => redirect()->route('hikvision.devices.edit', $device))->name('devices.edit');

        Route::redirect('/agents', '/hikvision/agents')->name('agents.index');
        Route::get('/agents/{agent}', fn(\App\Models\AccessControlAgent $agent) => redirect()->route('hikvision.agents.show', $agent))->name('agents.show');

        Route::redirect('/enrollments', '/hikvision/enrollments')->name('enrollments.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Locations & Equipment
    |--------------------------------------------------------------------------
    */

    Route::prefix('locations')->name('locations.')->group(function () {
        Route::view('/', 'locations.index')->name('index');
        Route::view('/create', 'locations.create')->name('create');
        Route::get('/{location}/edit', fn(\App\Models\Location $location) => view('locations.edit', compact('location')))->name('edit');
    });

    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::view('/', 'equipment.index')->name('index');
        Route::view('/create', 'equipment.create')->name('create');
        Route::get('/{equipment}/edit', fn(\App\Models\Equipment $equipment) => view('equipment.edit', compact('equipment')))->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Finances
    |--------------------------------------------------------------------------
    */

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::view('/', 'payments.index')->name('index');
        Route::view('/create', 'payments.create')->name('create');
        Route::view('/{paymentTransaction}', 'payments.show')->name('show');
    });

    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::view('/', 'expenses.index')->name('index');
        Route::view('/create', 'expenses.create')->name('create');
        Route::view('/{expense}/edit', 'expenses.edit')->name('edit');
    });

    Route::prefix('expense-categories')->name('expense-categories.')->group(function () {
        Route::view('/', 'expense-categories.index')->name('index');
        Route::view('/create', 'expense-categories.create')->name('create');
        Route::view('/{expenseCategory}/edit', 'expense-categories.edit')->name('edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::view('/revenue', 'reports.revenue')->name('revenue');
        Route::view('/memberships', 'reports.memberships')->name('memberships');
        Route::view('/attendance', 'reports.attendance')->name('attendance');
        Route::view('/insurance', 'reports.insurance')->name('insurance');
        Route::view('/pos', 'reports.pos')->name('pos');
        Route::view('/inventory', 'reports.inventory')->name('inventory');
    });

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::redirect('/', 'settings/profile');

        // User Settings
        Route::get('profile', Profile::class)->name('profile');
        Route::get('password', Password::class)->name('password');
        Route::get('appearance', Appearance::class)->name('appearance');

        Route::get('two-factor', TwoFactor::class)
            ->middleware(
                when(
                    Features::canManageTwoFactorAuthentication()
                        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                    ['password.confirm'],
                    [],
                ),
            )
            ->name('two-factor');

        // System Settings (admin only)
        Route::view('/general', 'settings.general')->name('general');
        Route::view('/branch', 'settings.branch')->name('branch');
        Route::view('/integrations', 'settings.integrations')->name('integrations');
    });
});

/*
|--------------------------------------------------------------------------
| Legacy Settings Routes (for backwards compatibility)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
