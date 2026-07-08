<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\PayrollSheetExport;
use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Models\Branch;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayrollPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PayrollController extends Controller
{
    public function __construct(private PayrollPeriodService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('accounting.manage');

        $query = Payroll::with(['creator', 'branch'])
            ->when($request->year,   fn ($q, $v) => $q->where('year', $v))
            ->when($request->month,  fn ($q, $v) => $q->where('month', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('year')->orderByDesc('month');

        return Inertia::render('Accounting/Payrolls/Index', [
            'payrolls'         => $query->paginate(20)->through(fn ($p) => $this->listDto($p)),
            'filters'          => $request->only(['year', 'month', 'status']),
            'branches'         => Branch::where('is_active', true)->orderBy('name')
                ->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'attendancePeriods'=> AttendancePeriod::where('status', 'locked')
                ->orderByDesc('year')->orderByDesc('month')
                ->get()->map(fn ($ap) => ['id' => $ap->id, 'label' => $ap->periodLabel()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'month'               => 'required|integer|between:1,12',
            'year'                => 'required|integer|min:2020|max:2100',
            'branch_id'           => 'nullable|exists:branches,id',
            'attendance_period_id'=> 'nullable|exists:attendance_periods,id',
            'note'                => 'nullable|string|max:500',
        ]);

        try {
            $payroll = $this->service->create(
                (int) $data['month'],
                (int) $data['year'],
                auth()->id(),
                $data['branch_id'] ?? null,
                $data['attendance_period_id'] ?? null,
                $data['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.payrolls.show', $payroll)
            ->with('success', "Đã tạo bảng lương {$payroll->code}.");
    }

    public function show(Payroll $payroll): Response
    {
        $this->authorize('accounting.view');

        $payroll->load(['creator', 'confirmedBy', 'lockedBy', 'attendancePeriod', 'branch']);

        $items = $payroll->items()
            ->with('employee')
            ->orderBy('department_name')
            ->orderBy('employee_name')
            ->get();

        // Group by department
        $grouped = $items->groupBy('department_name')
            ->map(fn ($grpItems, $deptName) => [
                'department' => $deptName ?: 'Không xác định',
                'items'      => $grpItems->map(fn ($i) => $this->itemDto($i))->values()->all(),
            ])->values()->all();

        return Inertia::render('Accounting/Payrolls/Show', [
            'payroll' => $this->detailDto($payroll),
            'grouped' => $grouped,
        ]);
    }

    public function confirm(Payroll $payroll): RedirectResponse
    {
        $this->authorize('accounting.manage');
        try {
            $this->service->confirm($payroll, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Đã xác nhận bảng lương.');
    }

    public function unconfirm(Payroll $payroll): RedirectResponse
    {
        $this->authorize('accounting.manage');
        try {
            $this->service->unconfirm($payroll, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Đã hủy xác nhận bảng lương.');
    }

    public function lock(Payroll $payroll): RedirectResponse
    {
        $this->authorize('accounting.manage');
        try {
            $this->service->lock($payroll, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Đã khóa bảng lương.');
    }

    public function unlock(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('accounting.manage');
        $data = $request->validate(['reason' => 'required|string|max:500']);
        try {
            $this->service->unlock($payroll, $data['reason'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Đã mở khóa bảng lương.');
    }

    public function export(Payroll $payroll): BinaryFileResponse
    {
        $this->authorize('accounting.view');
        return Excel::download(new PayrollSheetExport($payroll), "{$payroll->code}.xlsx");
    }

    // ─── DTOs ─────────────────────────────────────────────────────────────────

    private function listDto(Payroll $p): array
    {
        return [
            'id'           => $p->id,
            'code'         => $p->code,
            'month'        => $p->month,
            'year'         => $p->year,
            'period_label' => $p->periodLabel(),
            'branch'       => $p->branch?->name,
            'status'       => $p->status->value,
            'status_label' => $p->status->label(),
            'status_color' => $p->status->color(),
            'total_net_salary'   => $p->total_net_salary,
            'total_gross_income' => $p->total_gross_income,
            'created_by'   => $p->creator?->name,
            'created_at'   => $p->created_at->format('d/m/Y'),
            'confirmed_at' => $p->confirmed_at?->format('d/m/Y H:i'),
            'locked_at'    => $p->locked_at?->format('d/m/Y H:i'),
        ];
    }

    private function detailDto(Payroll $p): array
    {
        return [
            ...$this->listDto($p),
            'attendance_period_id'   => $p->attendance_period_id,
            'attendance_period_label'=> $p->attendancePeriod?->periodLabel(),
            'union_fee_confirmed'    => $p->union_fee_confirmed,
            'total_base_salary'      => $p->total_base_salary,
            'total_allowances'       => $p->total_allowances,
            'total_company_insurance'=> $p->total_company_insurance,
            'total_employee_insurance'=> $p->total_employee_insurance,
            'total_personal_income_tax'=> $p->total_personal_income_tax,
            'total_union_fee'        => $p->total_union_fee,
            'total_deductions'       => $p->total_deductions,
            'note'                   => $p->note,
            'confirmed_by'           => $p->confirmedBy?->name,
            'locked_by'              => $p->lockedBy?->name,
            'can_edit'               => $p->status->canEdit(),
            'can_confirm'            => $p->status->canConfirm(),
            'can_unconfirm'          => $p->status->canUnconfirm(),
            'can_lock'               => $p->status->canLock(),
            'can_unlock'             => $p->status->canUnlock(),
        ];
    }

    private function itemDto(PayrollItem $i): array
    {
        return [
            'id'                          => $i->id,
            'employee_id'                 => $i->employee_id,
            'employee_code'               => $i->employee_code,
            'employee_name'               => $i->employee_name,
            'position_name'               => $i->position_name,
            'department_name'             => $i->department_name,
            'standard_working_days'       => $i->standard_working_days,
            'actual_working_days'         => (float) $i->actual_working_days,
            'workday_ratio'               => (float) $i->workday_ratio,
            'base_salary'                 => $i->base_salary,
            'insurance_salary'            => $i->insurance_salary,
            'salary_by_workday'           => $i->salary_by_workday,
            'fixed_allowance'             => $i->fixed_allowance,
            'responsibility_allowance'    => $i->responsibility_allowance,
            'lunch_allowance'             => $i->lunch_allowance,
            'phone_allowance'             => $i->phone_allowance,
            'travel_allowance'            => $i->travel_allowance,
            'performance_kpi_amount'      => $i->performance_kpi_amount,
            'other_allowance'             => $i->other_allowance,
            'company_social_insurance'    => $i->company_social_insurance,
            'company_health_insurance'    => $i->company_health_insurance,
            'company_unemployment_insurance' => $i->company_unemployment_insurance,
            'total_company_insurance'     => $i->total_company_insurance,
            'employee_social_insurance'   => $i->employee_social_insurance,
            'employee_health_insurance'   => $i->employee_health_insurance,
            'employee_unemployment_insurance' => $i->employee_unemployment_insurance,
            'total_employee_insurance'    => $i->total_employee_insurance,
            'taxable_income'              => $i->taxable_income,
            'dependents_count'            => $i->dependents_count,
            'family_deduction'            => $i->family_deduction,
            'dependent_deduction'         => $i->dependent_deduction,
            'personal_income_tax'         => $i->personal_income_tax,
            'pit_manual_override'         => $i->pit_manual_override,
            'pit_manual_amount'           => $i->pit_manual_amount,
            'union_fee_amount'            => $i->union_fee_amount,
            'union_fee_confirmed'         => $i->union_fee_confirmed,
            'gross_income'                => $i->gross_income,
            'total_deductions'            => $i->total_deductions,
            'net_salary'                  => $i->net_salary,
            'social_insurance_enabled'    => $i->social_insurance_enabled,
            'insurance_manual_override'   => $i->insurance_manual_override,
            'kpi_source_type'             => $i->kpi_source_type->value,
            'note'                        => $i->note,
        ];
    }
}
