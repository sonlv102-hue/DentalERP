<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeContractController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $query = EmployeeContract::with('employee')->orderByDesc('start_date');
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);

        $contracts = $query->paginate(30)->through(fn ($c) => [
            'id'           => $c->id,
            'employee'     => $c->employee->full_name,
            'employee_id'  => $c->employee_id,
            'employee_code'=> $c->employee->code,
            'type'         => $c->type->value,
            'type_label'   => $c->type->label(),
            'type_color'   => $c->type->color(),
            'start_date'   => $c->start_date->format('d/m/Y'),
            'end_date'     => $c->end_date?->format('d/m/Y'),
            'base_salary'  => $c->base_salary,
            'is_active'    => $c->isActive(),
            'notes'        => $c->notes,
        ]);

        return Inertia::render('Hr/Contracts/Index', [
            'contracts' => $contracts,
            'employees' => Employee::where('is_active', true)->orderBy('full_name')->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'code' => $e->code]),
            'filters'   => $request->only(['employee_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'type'         => 'required|in:probation,full_time,part_time,contractor',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after:start_date',
            'base_salary'  => 'required|integer|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        EmployeeContract::create([...$data, 'created_by' => auth()->id()]);

        return back()->with('success', 'Đã tạo hợp đồng lao động.');
    }

    public function destroy(EmployeeContract $employeeContract): RedirectResponse
    {
        $this->authorize('employees.manage');
        $employeeContract->delete();
        return back()->with('success', 'Đã xóa hợp đồng.');
    }
}
