<?php

namespace App\Http\Controllers\Hr;

use App\Enums\SalarySlipStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalarySlip;
use App\Services\SalarySlipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalarySlipController extends Controller
{
    public function __construct(private SalarySlipService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $query = SalarySlip::with('employee')
            ->when($request->period,      fn ($q, $v) => $q->where('period', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->status,      fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('period')->orderBy('employee_id');

        return Inertia::render('Hr/SalarySlips/Index', [
            'slips'     => $query->paginate(30)->through(fn ($s) => $this->listDto($s)),
            'filters'   => $request->only(['period', 'employee_id', 'status']),
            'employees' => Employee::where('is_active', true)->orderBy('full_name')
                ->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'code' => $e->code]),
            'statuses'  => collect(SalarySlipStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function show(SalarySlip $salarySlip): Response
    {
        $this->authorize('employees.manage');
        $salarySlip->load(['employee.branch', 'items']);

        return Inertia::render('Hr/SalarySlips/Show', [
            'slip' => $this->detailDto($salarySlip),
        ]);
    }

    /**
     * AJAX: Return auto-calculated salary preview for employee + period.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('employees.manage');

        $data     = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period'      => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $preview  = $this->service->preview($employee, $data['period']);

        return response()->json($preview);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period'      => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'base_salary' => 'required|integer|min:0',
            'ot_hours'    => 'nullable|numeric|min:0',
            'ot_rate'     => 'nullable|integer|min:0',
            'deductions'  => 'nullable|integer|min:0',
            'notes'       => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        $existing = SalarySlip::where('employee_id', $employee->id)
            ->where('period', $data['period'])
            ->whereIn('status', [SalarySlipStatus::Confirmed->value, SalarySlipStatus::Paid->value])
            ->first();

        if ($existing) {
            return back()->with('error', 'Đã tồn tại phiếu lương đã duyệt/thanh toán cho kỳ này.');
        }

        $slip = $this->service->generate(
            $employee,
            $data['period'],
            (int) $data['base_salary'],
            (int) ($data['deductions'] ?? 0),
            $data['notes'] ?? null,
            (float) ($data['ot_hours'] ?? 0),
            (int)   ($data['ot_rate']  ?? 0),
        );

        return redirect()->route('hr.salary-slips.show', $slip)->with('success', 'Đã tạo phiếu lương.');
    }

    public function confirm(SalarySlip $salarySlip): RedirectResponse
    {
        $this->authorize('employees.manage');

        try {
            $this->service->confirm($salarySlip);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã duyệt phiếu lương.');
    }

    public function markPaid(SalarySlip $salarySlip): RedirectResponse
    {
        $this->authorize('employees.manage');

        try {
            $this->service->markPaid($salarySlip);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã thanh toán và hạch toán chi phí lương.');
    }

    public function destroy(SalarySlip $salarySlip): RedirectResponse
    {
        $this->authorize('employees.manage');

        if ($salarySlip->status !== SalarySlipStatus::Draft) {
            return back()->with('error', 'Chỉ có thể xóa phiếu ở trạng thái Nháp.');
        }

        $salarySlip->delete();

        return redirect()->route('hr.salary-slips.index')->with('success', 'Đã xóa phiếu lương.');
    }

    // ─── DTOs ─────────────────────────────────────────────────────────────────

    private function listDto(SalarySlip $s): array
    {
        return [
            'id'               => $s->id,
            'period'           => $s->period,
            'employee'         => $s->employee->full_name,
            'employee_code'    => $s->employee->code,
            'work_days'        => $s->work_days,
            'base_salary'      => $s->base_salary,
            'ot_hours'         => (float) $s->ot_hours,
            'ot_amount'        => $s->ot_amount,
            'commission_total' => $s->commission_total,
            'deductions'       => $s->deductions,
            'net_salary'       => $s->net_salary,
            'status'           => $s->status->value,
            'status_label'     => $s->status->label(),
            'status_color'     => $s->status->color(),
        ];
    }

    private function detailDto(SalarySlip $s): array
    {
        return [
            ...$this->listDto($s),
            'employee_id' => $s->employee_id,
            'branch'      => $s->employee->branch?->name,
            'ot_rate'     => $s->ot_rate,
            'notes'       => $s->notes,
            'paid_at'     => $s->paid_at?->format('d/m/Y H:i'),
            'created_at'  => $s->created_at->format('d/m/Y H:i'),
            'items'       => $s->items->map(fn ($i) => [
                'type'        => $i->type,
                'description' => $i->description,
                'amount'      => $i->amount,
            ])->values()->all(),
        ];
    }
}
