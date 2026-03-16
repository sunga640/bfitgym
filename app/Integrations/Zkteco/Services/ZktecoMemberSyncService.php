<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Repositories\ZktecoDeviceRepository;
use App\Integrations\Zkteco\ZktecoProviderManager;
use App\Models\Member;
use App\Models\User;
use App\Models\ZktecoConnection;
use App\Models\ZktecoMemberMap;
use App\Models\ZktecoSyncRun;
use App\Services\AccessControl\AccessEligibilityService;
use App\Support\AccessLogger;
use Illuminate\Support\Carbon;

class ZktecoMemberSyncService
{
    public function __construct(
        private readonly ZktecoProviderManager $providers,
        private readonly ZktecoConnectionRepository $connections,
        private readonly ZktecoDeviceRepository $devices,
        private readonly AccessEligibilityService $eligibility,
        private readonly ZktecoAccessSyncService $access_sync,
        private readonly AccessLogger $logger,
    ) {
    }

    /**
     * @return array{total:int, success:int, failed:int}
     */
    public function syncBranch(ZktecoConnection $connection, ?User $actor = null): array
    {
        $run = $this->connections->startSyncRun(
            connection: $connection,
            run_type: ZktecoSyncRun::TYPE_PERSONNEL_SYNC,
            triggered_by_user_id: $actor?->id,
        );

        $provider = $this->providers->resolve($connection);
        $remote_device_ids = $this->devices->mappedRemoteDeviceIds($connection->branch_id, $connection->id);

        $members = Member::query()
            ->withoutBranchScope()
            ->where('branch_id', $connection->branch_id)
            ->orderBy('id')
            ->get();

        $total = $members->count();
        $success = 0;
        $failed = 0;

        foreach ($members as $member) {
            $member_map = ZktecoMemberMap::query()
                ->withoutBranchScope()
                ->firstOrNew([
                    'branch_id' => $connection->branch_id,
                    'zkteco_connection_id' => $connection->id,
                    'member_id' => $member->id,
                ]);

            $is_allowed = $this->eligibility->isAllowed($member);

            if (!$is_allowed) {
                $this->revokeMemberAccess($connection, $member_map);
                $success++;
                continue;
            }

            if (empty($remote_device_ids)) {
                $member_map->fill([
                    'access_active' => false,
                    'last_synced_at' => now(),
                    'last_error' => 'No mapped ZKTeco devices found for this branch.',
                ])->save();

                $failed++;
                continue;
            }

            try {
                $personnel_result = $provider->upsertPersonnel($connection, $this->personnelPayload($member));

                $member_map->fill([
                    'remote_personnel_id' => $personnel_result['remote_personnel_id'] ?? null,
                    'remote_personnel_code' => $personnel_result['remote_personnel_code'] ?? $member->member_no,
                    'biometric_status' => $personnel_result['biometric_status'] ?? ZktecoMemberMap::BIOMETRIC_UNKNOWN,
                    'payload' => $personnel_result['raw'] ?? null,
                    'last_synced_at' => now(),
                    'last_error' => null,
                    'access_active' => true,
                ])->save();

                $allowed_until = $this->eligibility->allowedUntil($member);
                $valid_until = $allowed_until ? $allowed_until->copy()->endOfDay() : null;

                $this->access_sync->syncMemberAccess(
                    connection: $connection,
                    member_map: $member_map,
                    grant: true,
                    remote_device_ids: $remote_device_ids,
                    valid_until: $valid_until
                );

                $member_map->update([
                    'access_active' => true,
                    'last_error' => null,
                    'last_synced_at' => now(),
                ]);

                $success++;
            } catch (\Throwable $e) {
                $member_map->fill([
                    'access_active' => false,
                    'last_synced_at' => now(),
                    'last_error' => $e->getMessage(),
                ])->save();

                $failed++;
            }
        }

        $status = $this->resolveStatus($success, $failed);

        $connection->update([
            'last_personnel_sync_at' => $success > 0 ? now() : $connection->last_personnel_sync_at,
            'status' => $status === ZktecoSyncRun::STATUS_FAILED ? ZktecoConnection::STATUS_ERROR : ZktecoConnection::STATUS_CONNECTED,
            'last_error' => $failed > 0 ? "Personnel sync had {$failed} failure(s)." : null,
        ]);

        $this->connections->finishSyncRun(
            run: $run,
            status: $status,
            records_total: $total,
            records_success: $success,
            records_failed: $failed,
            error_message: $failed > 0 ? "Personnel sync had {$failed} failure(s)." : null
        );

        $this->logger->info('zkteco_personnel_sync_completed', [
            'branch_id' => $connection->branch_id,
            'connection_id' => $connection->id,
            'actor_id' => $actor?->id,
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
        ]);

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
        ];
    }

    private function revokeMemberAccess(ZktecoConnection $connection, ZktecoMemberMap $member_map): void
    {
        if (!$member_map->exists || empty($member_map->remote_personnel_id)) {
            return;
        }

        $remote_device_ids = $this->devices->mappedRemoteDeviceIds($connection->branch_id, $connection->id);

        $this->access_sync->syncMemberAccess(
            connection: $connection,
            member_map: $member_map,
            grant: false,
            remote_device_ids: $remote_device_ids,
            valid_until: Carbon::now()->subSecond()
        );

        $member_map->update([
            'access_active' => false,
            'last_error' => null,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function personnelPayload(Member $member): array
    {
        return [
            'personnel_code' => $member->member_no,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => trim($member->first_name . ' ' . $member->last_name),
            'phone' => $member->phone,
            'email' => $member->email,
            'status' => $member->status,
            'branch_id' => $member->branch_id,
            'metadata' => [
                'fithub_member_id' => $member->id,
            ],
        ];
    }

    private function resolveStatus(int $success, int $failed): string
    {
        if ($failed === 0) {
            return ZktecoSyncRun::STATUS_SUCCESS;
        }

        if ($success > 0) {
            return ZktecoSyncRun::STATUS_PARTIAL;
        }

        return ZktecoSyncRun::STATUS_FAILED;
    }
}

