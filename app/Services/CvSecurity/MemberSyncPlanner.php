<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityConnection;
use App\Models\CvSecurityMemberSyncItem;
use App\Models\CvSecuritySyncState;
use App\Models\Member;
use App\Models\User;
use App\Services\AccessControl\AccessEligibilityService;
use Illuminate\Support\Carbon;

class MemberSyncPlanner
{
    public function __construct(
        private readonly AccessEligibilityService $eligibility_service,
        private readonly ActivityLogger $activity_logger,
    ) {
    }

    /**
     * @return array{created:int,skipped:int,total:int}
     */
    public function planForConnection(CvSecurityConnection $connection, ?User $actor = null): array
    {
        $created = 0;
        $skipped = 0;

        $members = Member::query()
            ->withoutBranchScope()
            ->where('branch_id', $connection->branch_id)
            ->orderBy('id')
            ->get();

        foreach ($members as $member) {
            $payload = $this->buildPayload($member);
            $dedupe_key = $this->buildDedupeKey($connection, $member, $payload);

            $exists = CvSecurityMemberSyncItem::query()
                ->where('cvsecurity_connection_id', $connection->id)
                ->where('dedupe_key', $dedupe_key)
                ->whereIn('status', [
                    CvSecurityMemberSyncItem::STATUS_PENDING,
                    CvSecurityMemberSyncItem::STATUS_PROCESSING,
                    CvSecurityMemberSyncItem::STATUS_RETRY,
                ])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $allowed = (bool) ($payload['active'] ?? false);
            $action = $allowed ? 'upsert' : 'disable';

            CvSecurityMemberSyncItem::query()->create([
                'cvsecurity_connection_id' => $connection->id,
                'branch_id' => $connection->branch_id,
                'member_id' => $member->id,
                'sync_action' => $action,
                'desired_state' => $allowed ? 'active' : 'inactive',
                'external_person_id' => $payload['external_person_id'],
                'status' => CvSecurityMemberSyncItem::STATUS_PENDING,
                'attempts' => 0,
                'dedupe_key' => $dedupe_key,
                'available_at' => now(),
                'payload' => $payload,
            ]);

            $created++;
        }

        $pending_count = CvSecurityMemberSyncItem::query()
            ->where('cvsecurity_connection_id', $connection->id)
            ->whereIn('status', [CvSecurityMemberSyncItem::STATUS_PENDING, CvSecurityMemberSyncItem::STATUS_RETRY])
            ->count();

        $failed_count = CvSecurityMemberSyncItem::query()
            ->where('cvsecurity_connection_id', $connection->id)
            ->where('status', CvSecurityMemberSyncItem::STATUS_FAILED)
            ->count();

        CvSecuritySyncState::query()->updateOrCreate(
            ['cvsecurity_connection_id' => $connection->id],
            [
                'branch_id' => $connection->branch_id,
                'last_member_sync_at' => now(),
                'pending_members_count' => $pending_count,
                'failed_members_count' => $failed_count,
            ]
        );

        $connection->update([
            'agent_sync_requested' => true,
        ]);

        $this->activity_logger->log(
            connection: $connection,
            level: 'info',
            event: 'member_sync_planned',
            message: 'Member sync items prepared for local agent.',
            context: [
                'created' => $created,
                'skipped' => $skipped,
                'total' => $members->count(),
                'actor_id' => $actor?->id,
            ],
        );

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total' => $members->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Member $member): array
    {
        $active = $this->eligibility_service->isAllowed($member);
        $allowed_until = $this->eligibility_service->allowedUntil($member);

        $valid_from = Carbon::now()->startOfDay();
        $valid_to = $allowed_until?->copy()->endOfDay();

        return [
            'member_id' => $member->id,
            'external_person_id' => $member->member_no ?: ('MEM-' . $member->id),
            'full_name' => trim($member->full_name),
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'phone' => $member->phone,
            'active' => $active,
            'member_status' => $member->status,
            'valid_from' => $active ? $valid_from->toIso8601String() : null,
            'valid_to' => $active ? $valid_to?->toIso8601String() : now()->subMinute()->toIso8601String(),
            'reason' => $active ? 'eligible' : 'ineligible',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildDedupeKey(CvSecurityConnection $connection, Member $member, array $payload): string
    {
        return hash('sha256', implode('|', [
            $connection->id,
            $member->id,
            (string) ($payload['active'] ? '1' : '0'),
            (string) ($payload['valid_to'] ?? ''),
            (string) ($payload['member_status'] ?? ''),
            (string) ($member->updated_at?->timestamp ?? 0),
        ]));
    }
}

