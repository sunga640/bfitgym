<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $main_branch = Branch::where('code', 'DSM-MAIN')->first();
        $mikocheni_branch = Branch::where('code', 'DSM-MIK')->first();
        $arusha_branch = Branch::where('code', 'ARU-MAIN')->first();

        // Super Admin (no branch - can access all)
        $super_admin = User::firstOrCreate(
            ['email' => 'superadmin@gymmanagement.co.tz'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => 'SuperAdmin@2024!',
                'phone' => '+255 754 000 001',
                'role_hint' => 'super-admin',
                'branch_id' => null,
            ]
        );
        $super_admin->assignRole('super-admin');

        // Main Branch Users
        $main_branch_admin = User::firstOrCreate(
            ['email' => 'admin.main@gymmanagement.co.tz'],
            [
                'name' => 'John Mwamba',
                'email_verified_at' => now(),
                'password' => 'BranchAdmin@2024!',
                'phone' => '+255 754 100 001',
                'role_hint' => 'branch-admin',
                'branch_id' => $main_branch?->id,
            ]
        );
        $main_branch_admin->assignRole('branch-admin');

        $main_manager = User::firstOrCreate(
            ['email' => 'manager.main@gymmanagement.co.tz'],
            [
                'name' => 'Grace Kimaro',
                'email_verified_at' => now(),
                'password' => 'Manager@2024!',
                'phone' => '+255 754 100 002',
                'role_hint' => 'manager',
                'branch_id' => $main_branch?->id,
            ]
        );
        $main_manager->assignRole('manager');

        $main_trainer = User::firstOrCreate(
            ['email' => 'trainer.main@gymmanagement.co.tz'],
            [
                'name' => 'Peter Massawe',
                'email_verified_at' => now(),
                'password' => 'Trainer@2024!',
                'phone' => '+255 754 100 003',
                'role_hint' => 'trainer',
                'branch_id' => $main_branch?->id,
            ]
        );
        $main_trainer->assignRole('trainer');

        $main_receptionist = User::firstOrCreate(
            ['email' => 'reception.main@gymmanagement.co.tz'],
            [
                'name' => 'Amina Salum',
                'email_verified_at' => now(),
                'password' => 'Reception@2024!',
                'phone' => '+255 754 100 004',
                'role_hint' => 'receptionist',
                'branch_id' => $main_branch?->id,
            ]
        );
        $main_receptionist->assignRole('receptionist');

        $main_accountant = User::firstOrCreate(
            ['email' => 'accounts.main@gymmanagement.co.tz'],
            [
                'name' => 'Hassan Mwinyi',
                'email_verified_at' => now(),
                'password' => 'Accountant@2024!',
                'phone' => '+255 754 100 005',
                'role_hint' => 'accountant',
                'branch_id' => $main_branch?->id,
            ]
        );
        $main_accountant->assignRole('accountant');

        // Mikocheni Branch Users
        $mikocheni_branch_admin = User::firstOrCreate(
            ['email' => 'admin.mikocheni@gymmanagement.co.tz'],
            [
                'name' => 'Sarah Mushi',
                'email_verified_at' => now(),
                'password' => 'BranchAdmin@2024!',
                'phone' => '+255 754 200 001',
                'role_hint' => 'branch-admin',
                'branch_id' => $mikocheni_branch?->id,
            ]
        );
        $mikocheni_branch_admin->assignRole('branch-admin');

        $mikocheni_trainer = User::firstOrCreate(
            ['email' => 'trainer.mikocheni@gymmanagement.co.tz'],
            [
                'name' => 'David Lyimo',
                'email_verified_at' => now(),
                'password' => 'Trainer@2024!',
                'phone' => '+255 754 200 002',
                'role_hint' => 'trainer',
                'branch_id' => $mikocheni_branch?->id,
            ]
        );
        $mikocheni_trainer->assignRole('trainer');

        $mikocheni_receptionist = User::firstOrCreate(
            ['email' => 'reception.mikocheni@gymmanagement.co.tz'],
            [
                'name' => 'Fatma Said',
                'email_verified_at' => now(),
                'password' => 'Reception@2024!',
                'phone' => '+255 754 200 003',
                'role_hint' => 'receptionist',
                'branch_id' => $mikocheni_branch?->id,
            ]
        );
        $mikocheni_receptionist->assignRole('receptionist');

        // Arusha Branch Users
        $arusha_branch_admin = User::firstOrCreate(
            ['email' => 'admin.arusha@gymmanagement.co.tz'],
            [
                'name' => 'Michael Mollel',
                'email_verified_at' => now(),
                'password' => 'BranchAdmin@2024!',
                'phone' => '+255 754 300 001',
                'role_hint' => 'branch-admin',
                'branch_id' => $arusha_branch?->id,
            ]
        );
        $arusha_branch_admin->assignRole('branch-admin');

        $arusha_trainer = User::firstOrCreate(
            ['email' => 'trainer.arusha@gymmanagement.co.tz'],
            [
                'name' => 'Emmanuel Shirima',
                'email_verified_at' => now(),
                'password' => 'Trainer@2024!',
                'phone' => '+255 754 300 002',
                'role_hint' => 'trainer',
                'branch_id' => $arusha_branch?->id,
            ]
        );
        $arusha_trainer->assignRole('trainer');

        $arusha_receptionist = User::firstOrCreate(
            ['email' => 'reception.arusha@gymmanagement.co.tz'],
            [
                'name' => 'Anna Kessy',
                'email_verified_at' => now(),
                'password' => 'Reception@2024!',
                'phone' => '+255 754 300 003',
                'role_hint' => 'receptionist',
                'branch_id' => $arusha_branch?->id,
            ]
        );
        $arusha_receptionist->assignRole('receptionist');
    }
}






