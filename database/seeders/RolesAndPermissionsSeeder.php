<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions grouped by domain
        $permissions = [
            // Branch management
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',
            'switch branches',

            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            // Member management
            'view members',
            'create members',
            'edit members',
            'delete members',
            'export members',

            // Membership Packages
            'view membership-packages',
            'create membership-packages',
            'edit membership-packages',
            'delete membership-packages',

            // Membership & Subscriptions
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            'renew subscriptions',

            // Payments
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            'refund payments',
            'export payments',

            // Classes
            'view classes',
            'create classes',
            'edit classes',
            'delete classes',
            'manage class sessions',
            'view class bookings',
            'create class bookings',
            'cancel class bookings',

            // Workout plans
            'view workout plans',
            'create workout plans',
            'edit workout plans',
            'delete workout plans',
            'assign workout plans',

            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            'manage event registrations',

            // Access control integrations
            'view attendance',
            'view access logs',
            'view access devices',
            'manage access devices',
            'manage access identities',
            'export attendance',
            'view hikvision',
            'manage hikvision',
            'view zkteco',
            'manage zkteco',
            'manage zkteco settings',

            // Insurance
            'view insurers',
            'manage insurers',
            'view insurance reports',
            'export insurance reports',

            // POS & Sales
            'view pos',
            'create pos sales',
            'void pos sales',
            'refund pos sales',
            'view pos reports',
            'export pos reports',

            // Inventory & Stock
            'view inventory',
            'manage inventory',
            'view stock adjustments',
            'create stock adjustments',
            'view purchase orders',
            'create purchase orders',
            'edit purchase orders',
            'delete purchase orders',
            'receive purchase orders',
            'approve purchase orders',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Expenses
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'approve expenses',
            'view expense reports',

            // Equipment
            'view equipment',
            'manage equipment',
            'view equipment allocations',
            'manage equipment allocations',

            // Reports & Analytics
            'view reports',
            'view financial reports',
            'view attendance reports',
            'view membership reports',
            'export reports',

            // Settings
            'view settings',
            'manage settings',
            'manage integrations',

            // Dashboard
            'view dashboard analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $this->createSuperAdminRole();
        $this->createBranchAdminRole();
        $this->createManagerRole();
        $this->createTrainerRole();
        $this->createReceptionistRole();
        $this->createAccountantRole();
    }

    /**
     * Create super-admin role with all permissions.
     */
    private function createSuperAdminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::all());
    }

    /**
     * Create branch-admin role with branch-level permissions.
     */
    private function createBranchAdminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'branch-admin', 'guard_name' => 'web']);
        $role->syncPermissions([
            // Users (limited)
            'view users',
            'create users',
            'edit users',

            // Members
            'view members',
            'create members',
            'edit members',
            'delete members',
            'export members',

            // Membership Packages
            'view membership-packages',
            'create membership-packages',
            'edit membership-packages',
            'delete membership-packages',

            // Subscriptions
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            'renew subscriptions',

            // Payments
            'view payments',
            'create payments',
            'edit payments',
            'refund payments',
            'export payments',

            // Classes
            'view classes',
            'create classes',
            'edit classes',
            'delete classes',
            'manage class sessions',
            'view class bookings',
            'create class bookings',
            'cancel class bookings',

            // Workout plans
            'view workout plans',
            'create workout plans',
            'edit workout plans',
            'delete workout plans',
            'assign workout plans',

            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            'manage event registrations',

            // Access & Attendance
            'view attendance',
            'view access logs',
            'manage access devices',
            'manage access identities',
            'export attendance',
            'view hikvision',
            'manage hikvision',
            'view zkteco',
            'manage zkteco',
            'manage zkteco settings',

            // Insurance
            'view insurers',
            'view insurance reports',
            'export insurance reports',

            // POS
            'view pos',
            'create pos sales',
            'void pos sales',
            'view pos reports',
            'export pos reports',

            // Inventory
            'view inventory',
            'manage inventory',
            'view stock adjustments',
            'create stock adjustments',
            'view purchase orders',
            'create purchase orders',
            'approve purchase orders',

            // Products
            'view products',
            'create products',
            'edit products',

            // Expenses
            'view expenses',
            'create expenses',
            'edit expenses',
            'approve expenses',
            'view expense reports',

            // Equipment
            'view equipment',
            'manage equipment',
            'view equipment allocations',
            'manage equipment allocations',

            // Reports
            'view reports',
            'view financial reports',
            'view attendance reports',
            'view membership reports',
            'export reports',

            // Settings (limited)
            'view settings',

            // Dashboard
            'view dashboard analytics',
        ]);
    }

    /**
     * Create manager role.
     */
    private function createManagerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $role->syncPermissions([
            // Members
            'view members',
            'create members',
            'edit members',
            'export members',

            // Membership Packages
            'view membership-packages',

            // Subscriptions
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'renew subscriptions',

            // Payments
            'view payments',
            'create payments',
            'export payments',

            // Classes
            'view classes',
            'manage class sessions',
            'view class bookings',
            'create class bookings',
            'cancel class bookings',

            // Workout plans
            'view workout plans',
            'assign workout plans',

            // Events
            'view events',
            'manage event registrations',

            // Access & Attendance
            'view attendance',
            'view access logs',
            'export attendance',
            'view hikvision',
            'view zkteco',

            // Insurance
            'view insurance reports',

            // POS
            'view pos',
            'create pos sales',
            'view pos reports',

            // Inventory
            'view inventory',
            'view stock adjustments',
            'create stock adjustments',

            // Expenses
            'view expenses',
            'create expenses',
            'view expense reports',

            // Equipment
            'view equipment',
            'view equipment allocations',

            // Reports
            'view reports',
            'view attendance reports',
            'view membership reports',
        ]);
    }

    /**
     * Create trainer role.
     */
    private function createTrainerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
        $role->syncPermissions([
            // Members (view only)
            'view members',

            // Classes
            'view classes',
            'manage class sessions',
            'view class bookings',

            // Workout plans
            'view workout plans',
            'create workout plans',
            'edit workout plans',
            'assign workout plans',

            // Attendance
            'view attendance',
            'view hikvision',

            // Equipment
            'view equipment',
            'view equipment allocations',
        ]);
    }

    /**
     * Create receptionist role.
     */
    private function createReceptionistRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->syncPermissions([
            // Members
            'view members',
            'create members',
            'edit members',

            // Membership Packages
            'view membership-packages',

            // Subscriptions
            'view subscriptions',
            'create subscriptions',
            'renew subscriptions',

            // Payments
            'view payments',
            'create payments',

            // Classes
            'view classes',
            'view class bookings',
            'create class bookings',
            'cancel class bookings',

            // Events
            'view events',
            'manage event registrations',

            // Attendance
            'view attendance',
            'view access logs',
            'view hikvision',

            // POS
            'view pos',
            'create pos sales',

            // Products (view only)
            'view products',
        ]);
    }

    /**
     * Create accountant role.
     */
    private function createAccountantRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $role->syncPermissions([
            // Payments
            'view payments',
            'create payments',
            'edit payments',
            'export payments',

            // Subscriptions (view)
            'view subscriptions',

            // Insurance
            'view insurers',
            'view insurance reports',
            'export insurance reports',

            // POS
            'view pos',
            'view pos reports',
            'export pos reports',

            // Inventory
            'view inventory',
            'view stock adjustments',
            'view purchase orders',
            'create purchase orders',
            'approve purchase orders',

            // Expenses
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'approve expenses',
            'view expense reports',

            // Reports
            'view reports',
            'view financial reports',
            'export reports',
        ]);
    }
}
