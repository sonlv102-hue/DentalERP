<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $period   = $request->period ?? now()->format('Y-m');
        $branchId = $request->branch_id;

        [$year, $month] = explode('-', $period);
        $from = "{$year}-{$month}-01";
        $to   = date('Y-m-t', strtotime($from));

        $query = Timesheet::with(['employee', 'shift'])
            ->whereBetween('work_date', [$from, $to])
            ->orderBy('work_date')
            ->orderBy('employee_id');

        if ($branchId) $query->where('branch_id', $branchId);

        $timesheets = $query->get()->map(fn ($t) => [
            'id'           => $t->id,
            'employee_id'  => $t->employee_id,
            'employee'     => $t->employee->full_name,
            'employee_code'=> $t->employee->code,
            'work_date'    => $t->work_date->format('Y-m-d'),
            'shift'        => $t->shift?->name,
            'check_in'     => $t->check_in?->format('H:i'),
            'check_out'    => $t->check_out?->format('H:i'),
            'hours_worked' => $t->hoursWorked(),
            'ot_hours'     => $t->ot_hours,
            'status'       => $t->status->value,
            'status_label' => $t->status->label(),
            'status_color' => $t->status->color(),
            'notes'        => $t->notes,
        ]);

        return Inertia::render('Hr/Timesheets/Index', [
            'timesheets' => $timesheets,
            'shifts'     => WorkShift::where('is_active', true)->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'employees'  => Employee::where('is_active', true)->orderBy('full_name')->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'branches'   => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'    => ['period' => $period, 'branch_id' => $branchId],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'branch_id'   => 'required|exists:branches,id',
            'shift_id'    => 'nullable|exists:work_shifts,id',
            'work_date'   => 'required|date',
            'check_in'    => 'nullable|date_format:H:i',
            'check_out'   => 'nullable|date_format:H:i',
            'ot_hours'    => 'nullable|numeric|min:0|max:12',
            'notes'       => 'nullable|string|max:500',
        ]);

        $today = $data['work_date'];
        $checkIn  = isset($data['check_in'])  ? "{$today} {$data['check_in']}:00" : null;
        $checkOut = isset($data['check_out']) ? "{$today} {$data['check_out']}:00" : null;

        Timesheet::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'work_date' => $today],
            [...$data, 'check_in' => $checkIn, 'check_out' => $checkOut, 'created_by' => auth()->id()]
        );

        return back()->with('success', 'Đã lưu chấm công.');
    }

    public function approve(Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('employees.manage');
        $timesheet->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Đã duyệt chấm công.');
    }

    public function destroy(Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('employees.manage');
        $timesheet->delete();
        return back()->with('success', 'Đã xóa.');
    }
}
