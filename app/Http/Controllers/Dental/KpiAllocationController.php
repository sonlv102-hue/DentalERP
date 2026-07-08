<?php

namespace App\Http\Controllers\Dental;

use App\Enums\KpiAllocationStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiAllocation;
use App\Models\TreatmentPlanItem;
use App\Services\Dental\DentalKpiService;
use App\Services\Dental\DentalQualityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiAllocationController extends Controller
{
    public function __construct(
        private DentalKpiService     $kpiService,
        private DentalQualityService $qualityService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('dental.kpi.view');

        $query = KpiAllocation::with(['employee', 'planItem.plan.patient', 'service'])
            ->orderByDesc('calculated_at');

        if ($employeeId = $request->employee_id) {
            $query->where('employee_id', $employeeId);
        }
        if ($period = $request->period) {
            $query->where('period', $period);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($role = $request->role) {
            $query->where('role', $role);
        }

        // Non-admin users only see their own KPI
        if (!auth()->user()->hasRole(['owner', 'admin', 'branch_manager', 'accountant'])) {
            $employee = Employee::where('user_id', auth()->id())->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            }
        }

        return Inertia::render('Dental/Kpi/Index', [
            'allocations' => $query->paginate(30)->through(fn ($a) => [
                'id'                => $a->id,
                'employee_name'     => $a->employee?->full_name,
                'employee_id'       => $a->employee_id,
                'role'              => $a->role,
                'patient_name'      => $a->planItem?->plan?->patient?->full_name,
                'service_name'      => $a->service?->name,
                'period'            => $a->period,
                'kpi_pool_amount'   => $a->kpi_pool_amount,
                'share_percent'     => $a->share_percent,
                'kpi_amount'        => $a->kpi_amount,
                'quality_factor'    => $a->quality_factor,
                'collection_factor' => $a->collection_factor,
                'final_kpi_amount'  => $a->final_kpi_amount,
                'calculation_base'  => $a->calculation_base,
                'status'            => $a->status->value,
                'status_label'      => $a->status->label(),
                'status_color'      => $a->status->color(),
                'reason'            => $a->reason,
                'calculated_at'     => $a->calculated_at?->format('d/m/Y'),
                'can_approve'       => $a->status->canApprove(),
                'can_hold'          => $a->status->canHold(),
                'can_reverse'       => $a->status->canReverse(),
            ]),
            'employees'   => Employee::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'statuses'    => collect(KpiAllocationStatus::cases())->map(fn ($s) => [
                'value' => $s->value, 'label' => $s->label(), 'color' => $s->color(),
            ]),
            'filters'     => $request->only(['employee_id', 'period', 'status', 'role']),
            'summary'     => $this->buildSummary($query->clone()),
        ]);
    }

    public function approve(KpiAllocation $allocation): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $this->kpiService->approve($allocation, auth()->id());

        return back()->with('success', 'Đã duyệt KPI.');
    }

    public function hold(Request $request, KpiAllocation $allocation): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $data = $request->validate(['reason' => 'required|string|max:500']);
        $this->kpiService->hold($allocation, $data['reason']);

        return back()->with('success', 'Đã treo KPI.');
    }

    public function release(KpiAllocation $allocation): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $this->qualityService->releaseHold($allocation);

        return back()->with('success', 'Đã mở treo KPI.');
    }

    public function reverse(Request $request, KpiAllocation $allocation): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $data = $request->validate(['reason' => 'required|string|max:500']);
        $this->kpiService->reverse($allocation, $data['reason']);

        return back()->with('success', 'Đã đảo KPI.');
    }

    public function markPaid(KpiAllocation $allocation): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $this->kpiService->markPaid($allocation);

        return back()->with('success', 'Đã đánh dấu đã trả KPI.');
    }

    public function submitForApproval(TreatmentPlanItem $item): RedirectResponse
    {
        $this->authorize('dental.kpi.manage');

        $this->kpiService->submitForApproval($item);

        return back()->with('success', 'Đã gửi KPI chờ duyệt.');
    }

    private function buildSummary($query): array
    {
        $rows = $query->get(['status', 'final_kpi_amount']);

        return [
            'accrued'          => $rows->where('status', KpiAllocationStatus::Accrued->value)->sum('final_kpi_amount'),
            'pending_approval' => $rows->where('status', KpiAllocationStatus::PendingApproval->value)->sum('final_kpi_amount'),
            'approved'         => $rows->where('status', KpiAllocationStatus::Approved->value)->sum('final_kpi_amount'),
            'paid'             => $rows->where('status', KpiAllocationStatus::Paid->value)->sum('final_kpi_amount'),
            'held'             => $rows->where('status', KpiAllocationStatus::Held->value)->sum('final_kpi_amount'),
            'reversed'         => $rows->where('status', KpiAllocationStatus::Reversed->value)->sum('final_kpi_amount'),
        ];
    }
}
