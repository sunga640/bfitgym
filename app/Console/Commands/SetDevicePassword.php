<?php

namespace App\Console\Commands;

use App\Models\AccessControlDevice;
use Illuminate\Console\Command;

class SetDevicePassword extends Command
{
    protected $signature = 'device:set-password {device_id} {--password=}';

    protected $description = 'Set or update the password for an access control device';

    public function handle(): int
    {
        $device_id = $this->argument('device_id');

        $device = AccessControlDevice::withoutGlobalScopes()->find($device_id);

        if (!$device) {
            $this->error("Device with ID {$device_id} not found.");
            return self::FAILURE;
        }

        $this->info("Device: {$device->name}");
        $this->info("IP: {$device->ip_address}:{$device->port}");
        $this->info("Current username: {$device->username}");
        $this->info("Password currently set: " . ($device->password ? 'Yes' : 'No'));
        $this->newLine();

        $password = $this->option('password');
        
        if (empty($password)) {
            $password = $this->secret('Enter device password');
        }
        
        if (empty($password)) {
            $this->error('Password is required. Use --password=YOUR_PASSWORD option.');
            return self::FAILURE;
        }

        $device->password = $password;
        $device->save();

        $this->info('✓ Password updated successfully!');
        $this->newLine();
        $this->info('Cloud does not test or poll devices directly.');
        $this->info('To verify connectivity and sync logs, use the Local Agent (it will pull commands and talk to the device over LAN).');

        return self::SUCCESS;
    }
}

