<?php

namespace App\Livewire\Reports;

use App\Models\Insurer;
use App\Services\Attendance\InsuranceAttendanceReportService;
use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Insurance Attendance'])]
#[Title('Insurance Attendance')]
class InsuranceAttendance extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $insurer_id = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render(InsuranceAttendanceReportService $reportService): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $from = Carbon::parse($this->from);
        $to = Carbon::parse($this->to)->endOfDay();

        $summary = $reportService->summarizeByInsurer($from, $to, $branch_id);
        $members = [];

        if ($this->insurer_id) {
            $members = $reportService->memberVisits((int) $this->insurer_id, $from, $to, $branch_id);
        }

        $insurers = Insurer::orderBy('name')->get(['id', 'name']);

        return view('livewire.reports.insurance-attendance', [
            'summary' => $summary,
            'members' => $members,
            'insurers' => $insurers,
        ]);
    }
}

