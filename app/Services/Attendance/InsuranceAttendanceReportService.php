<?php

namespace App\Services\Attendance;

use App\Models\AccessLog;
use App\Models\MemberInsurance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InsuranceAttendanceReportService
{
    /**
     * Summary per insurer for date range.
     */
    public function summarizeByInsurer(Carbon $from, Carbon $to, ?int $branch_id = null): Collection
    {
        $query = AccessLog::query()
            ->select([
                'member_insurances.insurer_id',
                DB::raw('COUNT(DISTINCT access_logs.id) as visits'),
                DB::raw('COUNT(DISTINCT access_logs.subject_id) as unique_members'),
            ])
            ->join('member_insurances', function ($join) use ($from, $to) {
                $join->on('member_insurances.member_id', '=', 'access_logs.subject_id')
                    ->where('member_insurances.status', 'active')
                    ->whereDate('member_insurances.start_date', '<=', $to)
                    ->whereDate('member_insurances.end_date', '>=', $from);
            })
            ->where('access_logs.subject_type', AccessLog::SUBJECT_MEMBER)
            ->whereBetween('access_logs.event_timestamp', [$from, $to])
            ->when($branch_id, fn($q) => $q->where('access_logs.branch_id', $branch_id))
            ->groupBy('member_insurances.insurer_id')
            ->with('insurer');

        return $query->get();
    }

    /**
     * Member-level history for an insurer.
     */
    public function memberVisits(int $insurer_id, Carbon $from, Carbon $to, ?int $branch_id = null): Collection
    {
        return AccessLog::query()
            ->select([
                'access_logs.subject_id',
                DB::raw('COUNT(access_logs.id) as visits'),
                DB::raw('MIN(access_logs.event_timestamp) as first_visit'),
                DB::raw('MAX(access_logs.event_timestamp) as last_visit'),
            ])
            ->join('member_insurances', function ($join) use ($from, $to, $insurer_id) {
                $join->on('member_insurances.member_id', '=', 'access_logs.subject_id')
                    ->where('member_insurances.insurer_id', $insurer_id)
                    ->where('member_insurances.status', 'active')
                    ->whereDate('member_insurances.start_date', '<=', $to)
                    ->whereDate('member_insurances.end_date', '>=', $from);
            })
            ->where('access_logs.subject_type', AccessLog::SUBJECT_MEMBER)
            ->whereBetween('access_logs.event_timestamp', [$from, $to])
            ->when($branch_id, fn($q) => $q->where('access_logs.branch_id', $branch_id))
            ->groupBy('access_logs.subject_id')
            ->with('member')
            ->get();
    }
}

