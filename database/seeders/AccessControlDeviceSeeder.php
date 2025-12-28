<?php

namespace Database\Seeders;

use App\Models\AccessControlDevice;
use Illuminate\Database\Seeder;

class AccessControlDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AccessControlDevice::create([
            'branch_id'         => 2,
            'name'              => 'DS-K1T808MFWX',
            'device_model'      => AccessControlDevice::MODEL_DS_K1T808MFWX,
            'device_type'       => AccessControlDevice::TYPE_ENTRY,

            // 🔹 REQUIRED FIELD
            'serial_number'     => 'DS-K1T808MFWX-001', // put the real serial if you have it

            'ip_address'        => '192.168.1.111',
            'port'              => 80,

            'username'          => 'admin',
            // IMPORTANT: use the virtual password attribute
            'password'          => 'd0wehavetO!@#',

            'status'            => AccessControlDevice::STATUS_ACTIVE,
            'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,

            'auto_sync_enabled'     => true,
            'sync_interval_minutes' => 5,

            'supports_face_recognition' => true,
            'supports_fingerprint'      => true,
            'supports_card'             => true,
        ]);
    }
}
