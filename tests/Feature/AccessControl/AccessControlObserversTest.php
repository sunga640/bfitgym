<?php

namespace Tests\Feature\AccessControl;

use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\Insurer;
use App\Models\Member;
use App\Models\MemberInsurance;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccessControlObserversTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveDeviceForBranch(int $branch_id): AccessControlDevice
    {
        return AccessControlDevice::create([
            'branch_id' => $branch_id,
            'name' => 'Test Device',
            'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
            'device_type' => AccessControlDevice::TYPE_ENTRY,
            'serial_number' => 'TEST-SN-' . uniqid(),
            'ip_address' => null,
            'status' => AccessControlDevice::STATUS_ACTIVE,
            'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
            'auto_sync_enabled' => false,
            'sync_interval_minutes' => 5,
            'supports_face_recognition' => true,
            'supports_fingerprint' => true,
            'supports_card' => true,
        ]);
    }

    public function test_member_status_change_enqueues_commands(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);
        $this->makeActiveDeviceForBranch($member->branch_id);

        // No subscription/insurance -> not allowed; status change should enqueue disable
        $member->update(['status' => 'inactive']);

        $this->assertDatabaseHas('access_control_device_commands', [
            'branch_id' => $member->branch_id,
            'subject_type' => 'member',
            'subject_id' => $member->id,
            'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }

    public function test_subscription_saved_enqueues_allowed_commands(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);
        $this->makeActiveDeviceForBranch($member->branch_id);

        $package = MembershipPackage::factory()->active()->create(['branch_id' => $member->branch_id]);

        MemberSubscription::create([
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'membership_package_id' => $package->id,
            'renewed_from_id' => null,
            'start_date' => $today->copy()->subDay()->toDateString(),
            'end_date' => $today->copy()->addDays(10)->toDateString(),
            'status' => 'active',
            'auto_renew' => false,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('access_control_device_commands', [
            'branch_id' => $member->branch_id,
            'subject_type' => 'member',
            'subject_id' => $member->id,
            'type' => AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('access_control_device_commands', [
            'branch_id' => $member->branch_id,
            'subject_type' => 'member',
            'subject_id' => $member->id,
            'type' => AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }

    public function test_insurance_saved_enqueues_allowed_commands(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);
        $this->makeActiveDeviceForBranch($member->branch_id);

        $insurer = Insurer::create([
            'name' => 'Test Insurer',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'status' => 'active',
        ]);

        MemberInsurance::create([
            'member_id' => $member->id,
            'insurer_id' => $insurer->id,
            'policy_number' => 'POL-OBS',
            'coverage_type' => null,
            'start_date' => $today->copy()->subDay()->toDateString(),
            'end_date' => null,
            'status' => 'active',
            'notes' => null,
        ]);

        $this->assertDatabaseHas('access_control_device_commands', [
            'branch_id' => $member->branch_id,
            'subject_type' => 'member',
            'subject_id' => $member->id,
            'type' => AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('access_control_device_commands', [
            'branch_id' => $member->branch_id,
            'subject_type' => 'member',
            'subject_id' => $member->id,
            'type' => AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }
}
