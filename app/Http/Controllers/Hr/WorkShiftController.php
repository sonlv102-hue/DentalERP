<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkShiftController extends Controller
{
    public function index(): Response
    {
        $this->authorize('employees.manage');

        $shifts = WorkShift::with('branch')->orderBy('name')->get()->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'branch'       => $s->branch?->name,
            'branch_id'    => $s->branch_id,
            'start_time'   => $s->start_time,
            'end_time'     => $s->end_time,
            'days_of_week' => $s->days_of_week ?? [],
            'is_active'    => $s->is_active,
        ]);

        return Inertia::render('Hr/WorkShifts/Index', [
            'shifts'   => $shifts,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:1,7',
        ]);

        WorkShift::create($data);

        return back()->with('success', 'Đã tạo ca làm việc.');
    }

    public function update(Request $request, WorkShift $workShift): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:1,7',
            'is_active'    => 'boolean',
        ]);

        $workShift->update($data);

        return back()->with('success', 'Đã cập nhật ca làm việc.');
    }

    public function destroy(WorkShift $workShift): RedirectResponse
    {
        $this->authorize('employees.manage');
        $workShift->update(['is_active' => false]);
        return back()->with('success', 'Đã tắt ca làm việc.');
    }
}
