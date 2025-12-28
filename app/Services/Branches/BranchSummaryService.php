<?php

namespace App\Services\Branches;

use App\Models\AccessLog;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PaymentTransaction;
use App\Models\PosSale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchSummaryService
{
    /**
     * Get lightweight row metrics for branch listing.
     * Returns an array keyed by branch_id with counts and revenue.
     *
     * @param Collection<int, int>|array<int> $branch_ids
     * @return array<int, array{active_members_count: int, active_subscriptions_count: int, membership_revenue_this_month: float}>
     */
    public function getBranchRowMetrics(Collection|array $branch_ids): array
    {
        if ($branch_ids instanceof Collection) {
            $branch_ids = $branch_ids->toArray();
        }

        if (empty($branch_ids)) {
            return [];
        }

        $today = Carbon::today();
        $month_start = Carbon::now()->startOfMonth();
        $month_end = Carbon::now()->endOfMonth();

        // Get active members count per branch
        $active_members = Member::query()
            ->whereIn('branch_id', $branch_ids)
            ->where('status', 'active')
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'branch_id')
            ->toArray();

        // Get active subscriptions count per branch
        $active_subscriptions = MemberSubscription::query()
            ->whereIn('branch_id', $branch_ids)
            ->where('status', 'active')
            ->where('end_date', '>=', $today)
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'branch_id')
            ->toArray();

        // Get membership revenue this month per branch
        $membership_revenue = PaymentTransaction::query()
            ->whereIn('branch_id', $branch_ids)
            ->where('status', 'paid')
            ->where('revenue_type', 'membership')
            ->whereBetween('paid_at', [$month_start, $month_end])
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'branch_id')
            ->toArray();

        // Build result array keyed by branch_id
        $result = [];
        foreach ($branch_ids as $branch_id) {
            $result[$branch_id] = [
                'active_members_count' => $active_members[$branch_id] ?? 0,
                'active_subscriptions_count' => $active_subscriptions[$branch_id] ?? 0,
                'membership_revenue_this_month' => (float) ($membership_revenue[$branch_id] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Get comprehensive branch overview KPIs.
     *
     * @param int $branch_id
     * @return array<string, mixed>
     */
    public function getBranchOverview(int $branch_id): array
    {
        $today = Carbon::today();
        $month_start = Carbon::now()->startOfMonth();
        $month_end = Carbon::now()->endOfMonth();
        $seven_days_from_now = Carbon::now()->addDays(7);

        // Active members count
        $active_members_count = Member::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'active')
            ->count();

        // Active subscriptions count
        $active_subscriptions_count = MemberSubscription::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'active')
            ->where('end_date', '>=', $today)
            ->count();

        // Expiring soon (next 7 days)
        $expiring_soon_count = MemberSubscription::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'active')
            ->whereBetween('end_date', [$today, $seven_days_from_now])
            ->count();

        // Membership revenue this month
        $membership_revenue_this_month = PaymentTransaction::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'paid')
            ->where('revenue_type', 'membership')
            ->whereBetween('paid_at', [$month_start, $month_end])
            ->sum('amount');

        // Attendance today - count distinct member entries
        $attendance_today = AccessLog::query()
            ->where('branch_id', $branch_id)
            ->where('subject_type', 'member')
            ->where('direction', 'in')
            ->whereDate('event_timestamp', $today)
            ->distinct('subject_id')
            ->count('subject_id');

        // Staff count
        $staff_count = \App\Models\User::query()
            ->where('branch_id', $branch_id)
            ->count();

        // Total members
        $total_members_count = Member::query()
            ->where('branch_id', $branch_id)
            ->count();

        // New members this month
        $new_members_this_month = Member::query()
            ->where('branch_id', $branch_id)
            ->whereBetween('created_at', [$month_start, $month_end])
            ->count();

        return [
            'active_members_count' => $active_members_count,
            'total_members_count' => $total_members_count,
            'new_members_this_month' => $new_members_this_month,
            'active_subscriptions_count' => $active_subscriptions_count,
            'expiring_soon_count' => $expiring_soon_count,
            'membership_revenue_this_month' => (float) $membership_revenue_this_month,
            'attendance_today' => $attendance_today,
            'staff_count' => $staff_count,
        ];
    }

    /**
     * Get upcoming schedule items (classes + events) for a branch.
     *
     * @param int $branch_id
     * @param Carbon $from
     * @param Carbon $to
     * @return array<int, array{type: string, title: string, datetime: Carbon, location: ?string}>
     */
    public function getUpcomingSchedule(int $branch_id, Carbon $from, Carbon $to): array
    {
        $items = [];

        // Get upcoming events
        $events = Event::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'scheduled')
            ->whereBetween('start_datetime', [$from, $to])
            ->orderBy('start_datetime')
            ->limit(10)
            ->get(['id', 'title', 'start_datetime', 'location']);

        foreach ($events as $event) {
            $items[] = [
                'type' => 'event',
                'id' => $event->id,
                'title' => $event->title,
                'datetime' => $event->start_datetime,
                'location' => $event->location,
            ];
        }

        // Get class sessions within the date range
        // For recurring sessions, check day_of_week for each day in range
        // For specific date sessions, check specific_date
        $class_sessions = ClassSession::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'active')
            ->with('classType:id,name', 'location:id,name')
            ->get();

        $current_date = $from->copy();
        while ($current_date <= $to) {
            $day_of_week = $current_date->dayOfWeek; // 0 = Sunday, 6 = Saturday

            foreach ($class_sessions as $session) {
                $include = false;

                if ($session->is_recurring && $session->day_of_week === $day_of_week) {
                    $include = true;
                } elseif (!$session->is_recurring && $session->specific_date?->isSameDay($current_date)) {
                    $include = true;
                }

                if ($include) {
                    $session_datetime = $current_date->copy();
                    if ($session->start_time) {
                        $session_datetime->setTimeFromTimeString($session->start_time->format('H:i:s'));
                    }

                    // Only include if the datetime is in the future or today
                    if ($session_datetime >= $from) {
                        $items[] = [
                            'type' => 'class',
                            'id' => $session->id,
                            'title' => $session->classType?->name ?? 'Class Session',
                            'datetime' => $session_datetime,
                            'location' => $session->location?->name,
                        ];
                    }
                }
            }

            $current_date->addDay();
        }

        // Sort by datetime
        usort($items, fn($a, $b) => $a['datetime']->timestamp <=> $b['datetime']->timestamp);

        // Limit to 10 items
        return array_slice($items, 0, 10);
    }

    /**
     * Get financial summary for a branch.
     *
     * @param int $branch_id
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array<string, mixed>
     */
    public function getFinanceSummary(int $branch_id, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? Carbon::now()->startOfMonth();
        $to = $to ?? Carbon::now()->endOfMonth();

        // Membership revenue
        $membership_revenue = PaymentTransaction::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'paid')
            ->where('revenue_type', 'membership')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // POS revenue
        $pos_revenue = PosSale::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        // Class/Event revenue
        $other_revenue = PaymentTransaction::query()
            ->where('branch_id', $branch_id)
            ->where('status', 'paid')
            ->whereIn('revenue_type', ['class_booking', 'event'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // Total expenses
        $total_expenses = Expense::query()
            ->where('branch_id', $branch_id)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $total_revenue = (float) $membership_revenue + (float) $pos_revenue + (float) $other_revenue;

        return [
            'membership_revenue' => (float) $membership_revenue,
            'pos_revenue' => (float) $pos_revenue,
            'other_revenue' => (float) $other_revenue,
            'total_revenue' => $total_revenue,
            'total_expenses' => (float) $total_expenses,
            'net_income' => $total_revenue - (float) $total_expenses,
            'period_from' => $from,
            'period_to' => $to,
        ];
    }

    /**
     * Get operations summary for a branch.
     *
     * @param int $branch_id
     * @return array<string, mixed>
     */
    public function getOperationsSummary(int $branch_id): array
    {
        $locations_count = \App\Models\Location::query()
            ->where('branch_id', $branch_id)
            ->count();

        $equipment_allocations_count = \App\Models\EquipmentAllocation::query()
            ->where('branch_id', $branch_id)
            ->count();

        $access_devices_count = \App\Models\AccessControlDevice::query()
            ->where('branch_id', $branch_id)
            ->count();

        $class_types_count = \App\Models\ClassType::query()
            ->where('branch_id', $branch_id)
            ->count();

        return [
            'locations_count' => $locations_count,
            'equipment_allocations_count' => $equipment_allocations_count,
            'access_devices_count' => $access_devices_count,
            'class_types_count' => $class_types_count,
        ];
    }
}

