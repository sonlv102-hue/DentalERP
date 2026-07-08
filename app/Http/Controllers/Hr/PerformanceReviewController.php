<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.manage');

        $period = $request->period ?? now()->format('Y-m');

        $reviews = PerformanceReview::with(['employee', 'reviewer'])
            ->where('period', $period)
            ->orderBy('employee_id')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'employee_id'      => $r->employee_id,
                'employee'         => $r->employee->full_name,
                'employee_code'    => $r->employee->code,
                'reviewer'         => $r->reviewer->name,
                'overall_score'    => $r->overall_score,
                'punctuality_score'=> $r->punctuality_score,
                'quality_score'    => $r->quality_score,
                'teamwork_score'   => $r->teamwork_score,
                'average_score'    => $r->averageScore(),
                'strengths'        => $r->strengths,
                'improvements'     => $r->improvements,
                'goals'            => $r->goals,
                'status'           => $r->status->value,
                'status_label'     => $r->status->label(),
                'status_color'     => $r->status->color(),
            ]);

        return Inertia::render('Hr/Reviews/Index', [
            'reviews'   => $reviews,
            'employees' => Employee::where('is_active', true)->orderBy('full_name')->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'period'    => $period,
            'filters'   => ['period' => $period],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'period'            => 'required|date_format:Y-m',
            'overall_score'     => 'required|integer|between:1,5',
            'punctuality_score' => 'required|integer|between:1,5',
            'quality_score'     => 'required|integer|between:1,5',
            'teamwork_score'    => 'required|integer|between:1,5',
            'strengths'         => 'nullable|string|max:1000',
            'improvements'      => 'nullable|string|max:1000',
            'goals'             => 'nullable|string|max:1000',
        ]);

        PerformanceReview::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'period' => $data['period']],
            [...$data, 'reviewer_id' => auth()->id(), 'created_by' => auth()->id()]
        );

        return back()->with('success', 'Đã lưu đánh giá.');
    }

    public function finalize(PerformanceReview $performanceReview): RedirectResponse
    {
        $this->authorize('employees.manage');
        $performanceReview->update(['status' => 'finalized']);
        return back()->with('success', 'Đã hoàn thành đánh giá.');
    }

    public function destroy(PerformanceReview $performanceReview): RedirectResponse
    {
        $this->authorize('employees.manage');
        $performanceReview->delete();
        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
