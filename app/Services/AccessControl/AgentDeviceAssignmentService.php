<?php

namespace App\Services\AccessControl;

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Models\User;
use App\Support\AccessLogger;
use Illuminate\Support\Facades\DB;

class AgentDeviceAssignmentService
{
    public function __construct(
        protected AccessLogger $logger
    ) {}

    /**
     * Assign devices to an agent.
     *
     * @param AccessControlAgent $agent The agent to assign devices to
     * @param array $device_ids Device IDs to assign
     * @param User $actor The user performing the action
     * @param bool $sync If true, sync (replace) assignments. If false, attach (add) to existing.
     *
     * @return array{assigned: array, failed: array}
     */
    public function assignDevices(
        AccessControlAgent $agent,
        array $device_ids,
        User $actor,
        bool $sync = true
    ): array {
        $branch_id = $agent->branch_id;

        // Validate devices belong to same branch
        $valid_devices = AccessControlDevice::query()
            ->where('branch_id', $branch_id)
            ->whereIn('id', $device_ids)
            ->get();

        $valid_device_ids = $valid_devices->pluck('id')->all();
        $failed_ids = array_diff($device_ids, $valid_device_ids);

        if (empty($valid_device_ids)) {
            return [
                'assigned' => [],
                'failed' => $device_ids,
            ];
        }

        DB::transaction(function () use ($agent, $valid_device_ids, $branch_id, $sync) {
            $pivot_data = [];
            foreach ($valid_device_ids as $device_id) {
                $pivot_data[$device_id] = ['branch_id' => $branch_id];
            }

            if ($sync) {
                $agent->devices()->sync($pivot_data);
            } else {
                $agent->devices()->syncWithoutDetaching($pivot_data);
            }
        });

        $this->logger->info('agent_devices_assigned', [
            'agent_id' => $agent->id,
            'agent_uuid' => $agent->uuid,
            'branch_id' => $branch_id,
            'device_ids' => $valid_device_ids,
            'sync_mode' => $sync,
            'actor_user_id' => $actor->id,
        ]);

        return [
            'assigned' => $valid_device_ids,
            'failed' => $failed_ids,
        ];
    }

    /**
     * Unassign a device from an agent.
     */
    public function unassignDevice(
        AccessControlAgent $agent,
        int $device_id,
        User $actor
    ): bool {
        $exists = $agent->devices()->where('access_control_devices.id', $device_id)->exists();

        if (!$exists) {
            return false;
        }

        $agent->devices()->detach($device_id);

        // If this agent is the primary agent for the device, clear that too
        AccessControlDevice::query()
            ->where('id', $device_id)
            ->where('access_control_agent_id', $agent->id)
            ->update(['access_control_agent_id' => null]);

        $this->logger->info('agent_device_unassigned', [
            'agent_id' => $agent->id,
            'agent_uuid' => $agent->uuid,
            'device_id' => $device_id,
            'branch_id' => $agent->branch_id,
            'actor_user_id' => $actor->id,
        ]);

        return true;
    }

    /**
     * Set the primary agent for a device.
     */
    public function setPrimaryAgent(
        AccessControlDevice $device,
        ?AccessControlAgent $agent,
        User $actor
    ): void {
        // Validate agent belongs to same branch (if provided)
        if ($agent && $agent->branch_id !== $device->branch_id) {
            throw new \InvalidArgumentException('Agent and device must belong to the same branch.');
        }

        $previous_agent_id = $device->access_control_agent_id;

        $device->update([
            'access_control_agent_id' => $agent?->id,
        ]);

        // Also ensure the agent is in the many-to-many relationship
        if ($agent) {
            $agent->devices()->syncWithoutDetaching([
                $device->id => ['branch_id' => $device->branch_id],
            ]);
        }

        $this->logger->info('device_primary_agent_changed', [
            'device_id' => $device->id,
            'device_serial' => $device->serial_number,
            'branch_id' => $device->branch_id,
            'previous_agent_id' => $previous_agent_id,
            'new_agent_id' => $agent?->id,
            'actor_user_id' => $actor->id,
        ]);
    }

    /**
     * Get unassigned devices for a branch.
     */
    public function getUnassignedDevices(int $branch_id): \Illuminate\Database\Eloquent\Collection
    {
        return AccessControlDevice::query()
            ->where('branch_id', $branch_id)
            ->whereDoesntHave('agents')
            ->get();
    }

    /**
     * Get devices available for assignment to a specific agent.
     */
    public function getAvailableDevicesForAgent(AccessControlAgent $agent): \Illuminate\Database\Eloquent\Collection
    {
        return AccessControlDevice::query()
            ->where('branch_id', $agent->branch_id)
            ->where('status', AccessControlDevice::STATUS_ACTIVE)
            ->get();
    }
}
