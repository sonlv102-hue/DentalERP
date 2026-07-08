<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('branches.manage');

        $departments = Department::with('branch')->orderBy('name')->get()
            ->map(fn ($d) => [
                'id'          => $d->id,
                'name'        => $d->name,
                'description' => $d->description,
                'branch'      => $d->branch?->name,
                'branch_id'   => $d->branch_id,
                'employee_count' => $d->employees()->count(),
                'is_active'   => $d->is_active,
            ]);

        $branches = Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

        return Inertia::render('Core/Departments/Index', compact('departments', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'branch_id'   => 'nullable|exists:branches,id',
            'description' => 'nullable|string|max:255',
        ]);

        Department::create($data);

        return back()->with('success', 'Đã thêm bộ phận.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'branch_id'   => 'nullable|exists:branches,id',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $department->update($data);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('branches.manage');
        $department->delete();
        return back()->with('success', 'Đã xóa bộ phận.');
    }
}
