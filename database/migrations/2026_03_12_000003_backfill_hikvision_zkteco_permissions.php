<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $guard = 'web';

        $new_permissions = [
            'view hikvision',
            'manage hikvision',
            'view zkteco',
            'manage zkteco',
            'manage zkteco settings',
        ];

        foreach ($new_permissions as $permission_name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission_name, 'guard_name' => $guard],
                ['name' => $permission_name, 'guard_name' => $guard],
            );
        }

        if (!Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permission_ids = DB::table('permissions')
            ->where('guard_name', $guard)
            ->pluck('id', 'name');

        $view_seed_ids = [];
        foreach (['view attendance', 'view access logs', 'view access devices', 'manage access devices', 'manage access identities'] as $name) {
            if (isset($permission_ids[$name])) {
                $view_seed_ids[] = (int) $permission_ids[$name];
            }
        }

        $manage_seed_ids = [];
        foreach (['manage access devices', 'manage access identities'] as $name) {
            if (isset($permission_ids[$name])) {
                $manage_seed_ids[] = (int) $permission_ids[$name];
            }
        }

        $role_ids_to_view = DB::table('role_has_permissions')
            ->whereIn('permission_id', $view_seed_ids)
            ->pluck('role_id')
            ->unique()
            ->values()
            ->all();

        $role_ids_to_manage = DB::table('role_has_permissions')
            ->whereIn('permission_id', $manage_seed_ids)
            ->pluck('role_id')
            ->unique()
            ->values()
            ->all();

        $view_target_permissions = [
            'view hikvision',
            'view zkteco',
        ];

        $manage_target_permissions = [
            'manage hikvision',
            'manage zkteco',
            'manage zkteco settings',
        ];

        foreach ($role_ids_to_view as $role_id) {
            foreach ($view_target_permissions as $permission_name) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permission_ids[$permission_name],
                    'role_id' => (int) $role_id,
                ]);
            }
        }

        foreach ($role_ids_to_manage as $role_id) {
            foreach ($manage_target_permissions as $permission_name) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permission_ids[$permission_name],
                    'role_id' => (int) $role_id,
                ]);
            }
        }

        if (!Schema::hasTable('model_has_permissions')) {
            return;
        }

        $model_rows_to_view = DB::table('model_has_permissions')
            ->whereIn('permission_id', $view_seed_ids)
            ->get();

        $model_rows_to_manage = DB::table('model_has_permissions')
            ->whereIn('permission_id', $manage_seed_ids)
            ->get();

        foreach ($model_rows_to_view as $row) {
            foreach ($view_target_permissions as $permission_name) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permission_ids[$permission_name],
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ]);
            }
        }

        foreach ($model_rows_to_manage as $row) {
            foreach ($manage_target_permissions as $permission_name) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permission_ids[$permission_name],
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $guard = 'web';
        $permission_names = [
            'view hikvision',
            'manage hikvision',
            'view zkteco',
            'manage zkteco',
            'manage zkteco settings',
        ];

        $permission_ids = DB::table('permissions')
            ->where('guard_name', $guard)
            ->whereIn('name', $permission_names)
            ->pluck('id')
            ->all();

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permission_ids)
                ->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->whereIn('permission_id', $permission_ids)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permission_ids)
            ->delete();
    }
};

