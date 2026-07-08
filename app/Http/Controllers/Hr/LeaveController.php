<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->orderByDesc('start_date');

        if ($request->status) $query->where('status', $request->status);
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);

        $requests = $query->paginate(30)->through(fn ($r) => [
            'id'           => $r->id,
            'employee'     => $r->employee->full_name,
            'employee_code'=> $r->employee->code,
            'leave_type'   => $r->leaveType->name,
            'is_paid'      => $r->leaveType->is_paid,
            'start_date'   => $r->start_date->format('d/m/Y'),
            'end_date'     => $r->end_date->format('d/m/Y'),
            'days_count'   => $r->days_count,
            'reason'       => $r->reason,
            'status'       => $r->status->value,
            'status_label' => $r->status->label(),
            'status_color' => $r->status->color(),
        ]);

        return Inertia::render('Hr/Leaves/Index', [
            'requests'   => $requests,
            'leaveTypes' => LeaveType::where('is_active', true)->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'days_per_year' => $t->days_per_year]),
            'employees'  => Employee::where('is_active', true)->orderBy('full_name')->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'filters'    => $request->only(['status', 'employee_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'days_count'    => 'required|integer|min:1',
            'reason'        => 'nullable|string|max:500',
        ]);

        LeaveRequest::create([...$data, 'status' => 'pending']);

        return back()->with('success', 'Đã tạo đơn nghỉ phép.');
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('employees.manage');

        if ($leaveRequest->status->value !== 'pending') {
            return back()->with('error', 'Chỉ có thể duyệt đơn đang chờ.');
        }

        $leaveRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Đã duyệt đơn nghỉ phép.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('employees.manage');

        if ($leaveRequest->status->value !== 'pending') {
            return back()->with('error', 'Chỉ có thể từ chối đơn đang chờ.');
        }

        $data = $request->validate(['notes' => 'nullable|string|max:500']);
        $leaveRequest->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'notes' => $data['notes'] ?? null]);

        return back()->with('success', 'Đã từ chối đơn nghỉ phép.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('employees.manage');
        if ($leaveRequest->status->value !== 'pending') {
            return back()->with('error', 'Chỉ có thể xóa đơn đang chờ.');
        }
        $leaveRequest->delete();
        return back()->with('success', 'Đã xóa đơn nghỉ phép.');
    }

    public function storeType(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');
        $data = $request->validate(['name' => 'required|string|max:100', 'days_per_year' => 'required|integer|min:1', 'is_paid' => 'boolean']);
        LeaveType::create($data);
        return back()->with('success', 'Đã tạo loại nghỉ phép.');
    }
}
