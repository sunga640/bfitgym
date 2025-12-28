<?php

namespace App\Services\AccessControl;

use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\AccessLog;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccessLogImporter
{
    /**
     * Import raw events fetched from a Hikvision device and persist as access_logs.
     *
     * @param  AccessControlDevice  $device
     * @param  array  $events
     * @return int Number of logs persisted
     */
    public function importEvents(AccessControlDevice $device, array $events): int
    {
        $branch_id = $device->branch_id;
        $persisted = 0;

        foreach (array_chunk($events, 200) as $chunk) {
            DB::transaction(function () use ($chunk, $device, $branch_id, &$persisted) {
                foreach ($chunk as $event) {
                    $normalized = $this->normalizeEvent($event);
                    if (!$normalized) {
                        continue;
                    }

                    $event_time = $normalized['occurred_at'];

                    // Find identity by device_user_id or card number
                    $identity = AccessIdentity::query()
                        ->where('branch_id', $branch_id)
                        ->when($normalized['device_user_id'], fn($q) => $q->where('device_user_id', $normalized['device_user_id']))
                        ->when(!$normalized['device_user_id'] && $normalized['card_number'], fn($q) => $q->where('card_number', $normalized['card_number']))
                        ->active()
                        ->first();

                    if (!$identity) {
                        Log::warning('Access log skipped: no matching identity', [
                            'device_id' => $device->id,
                            'branch_id' => $branch_id,
                            'payload' => $event,
                        ]);
                        continue;
                    }

                    $subject_type = $identity->subject_type;
                    $subject_id = $identity->subject_id;

                    // Enforce active subscription or insurance for members
                    if ($subject_type === AccessIdentity::SUBJECT_MEMBER) {
                        $member = Member::find($subject_id);
                        if (!$member) {
                            continue;
                        }

                        $eligibility = app(AccessEligibilityService::class);
                        if (!$eligibility->is_member_allowed($member, $event_time)) {
                            Log::info('Access denied: no active subscription/insurance', [
                                'member_id' => $member->id,
                                'device_id' => $device->id,
                                'event_time' => $event_time,
                            ]);
                            continue;
                        }
                    } elseif ($subject_type === AccessIdentity::SUBJECT_STAFF) {
                        if (!User::find($subject_id)) {
                            continue;
                        }
                    }

                    // Prevent duplicates by device + identity + timestamp
                    $exists = AccessLog::query()
                        ->where('access_control_device_id', $device->id)
                        ->where('access_identity_id', $identity->id)
                        ->where('event_timestamp', $event_time)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    AccessLog::create([
                        'branch_id' => $branch_id,
                        'access_control_device_id' => $device->id,
                        'access_identity_id' => $identity->id,
                        'subject_type' => $subject_type,
                        'subject_id' => $subject_id,
                        'direction' => $normalized['direction'],
                        'event_timestamp' => $event_time,
                        'raw_payload' => $event,
                    ]);

                    $persisted++;
                }
            });
        }

        if ($persisted > 0) {
            $device->recordSync();
        }

        return $persisted;
    }

    /**
     * Normalize raw Hikvision event into a consistent structure.
     * 
     * Hikvision JSON format uses 'time' key directly in the event object.
     */
    protected function normalizeEvent(array $event): ?array
    {
        // Hikvision JSON uses 'time' directly, fallback to other formats
        $occur_time = $event['time'] ?? $event['occurTime'] ?? $event['AcsEvent']['time'] ?? null;
        if (!$occur_time) {
            Log::debug('Event skipped: no time field', ['event_keys' => array_keys($event)]);
            return null;
        }

        $occurred_at = Carbon::parse($occur_time);

        // Employee number can be string or int
        $device_user_id = $event['employeeNoString'] ?? null;
        if ($device_user_id === null && isset($event['employeeNo'])) {
            $device_user_id = (string) $event['employeeNo'];
        }

        $card_number = $event['cardNo'] ?? $event['cardNumber'] ?? null;

        // Skip events without any user identifier
        if (empty($device_user_id) && empty($card_number)) {
            // This is a system event or failed auth - skip silently
            return null;
        }

        $direction = AccessLog::DIRECTION_IN;
        $door_no = $event['doorNo'] ?? null;
        if (isset($event['statusValue'])) {
            // 1=open, 0=close - keep unknown for now
            $direction = AccessLog::DIRECTION_UNKNOWN;
        }

        return [
            'occurred_at' => $occurred_at,
            'device_user_id' => $device_user_id,
            'card_number' => $card_number,
            'door_no' => $door_no,
            'direction' => $direction,
        ];
    }
}
