<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $period = $request->period ?? now()->format('Y-m');

        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();

        $kpis = EmployeeKpi::with('employee')
            ->where('period', $period)
            ->get()
            ->keyBy('employee_id');

        $rows = $employees->map(function ($emp) use ($kpis, $period) {
            $kpi = $kpis->get($emp->id);
            return [
                'employee_id'    => $emp->id,
                'employee'       => $emp->full_name,
                'employee_code'  => $emp->code,
                'role_type'      => $emp->role_type,
                'kpi_id'         => $kpi?->id,
                'revenue_target' => $kpi?->revenue_target ?? 0,
                'case_target'    => $kpi?->case_target ?? 0,
                'bonus_amount'   => $kpi?->bonus_amount ?? 0,
                'status'         => $kpi?->status ?? null,
                'status_label'   => $kpi?->statusLabel() ?? '—',
                'status_color'   => $kpi?->statusColor() ?? 'gray',
                'notes'          => $kpi?->notes,
            ];
        });

        return Inertia::render('Hr/Kpis/Index', [
            'rows'    => $rows,
            'period'  => $period,
            'filters' => ['period' => $period],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'period'         => 'required|date_format:Y-m',
            'revenue_target' => 'required|integer|min:0',
            'case_target'    => 'required|integer|min:0',
            'bonus_amount'   => 'nullable|integer|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        EmployeeKpi::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'period' => $data['period']],
            [...$data, 'status' => 'draft', 'created_by' => auth()->id()]
        );

        return back()->with('success', 'Đã lưu KPI.');
    }

    public function update(Request $request, EmployeeKpi $kpi): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'revenue_target' => 'required|integer|min:0',
            'case_target'    => 'required|integer|min:0',
            'bonus_amount'   => 'nullable|integer|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        $kpi->update($data);

        return back()->with('success', 'Đã cập nhật KPI.');
    }

    public function approve(EmployeeKpi $kpi): RedirectResponse
    {
        $this->authorize('employees.manage');
        $kpi->update(['status' => 'approved']);
        return back()->with('success', 'Đã duyệt KPI.');
    }

    public function destroy(EmployeeKpi $kpi): RedirectResponse
    {
        $this->authorize('employees.manage');
        $kpi->delete();
        return back()->with('success', 'Đã xóa KPI.');
    }
}
