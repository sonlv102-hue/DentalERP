<?php

namespace App\Http\Controllers\Dental;

use App\Http\Controllers\Controller;
use App\Models\DentalService;
use App\Models\DentalServiceCost;
use App\Models\DentalServiceStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceStepController extends Controller
{
    /** Steps + costs management page for a service */
    public function show(DentalService $service): Response
    {
        $this->authorize('dental.manage');

        return Inertia::render('Dental/Services/Steps', [
            'service' => [
                'id'          => $service->id,
                'code'        => $service->code,
                'name'        => $service->name,
                'kpi_base_type'  => $service->kpi_base_type?->value,
                'kpi_rate'       => $service->kpi_rate,
                'fixed_kpi_amount' => $service->fixed_kpi_amount,
            ],
            'steps' => $service->steps()->get()->map(fn ($s) => [
                'id'                     => $s->id,
                'step_name'              => $s->step_name,
                'step_order'             => $s->step_order,
                'default_role'           => $s->default_role,
                'estimated_minutes'      => $s->estimated_minutes,
                'kpi_share_percent'      => $s->kpi_share_percent,
                'deduct_from_main_doctor' => $s->deduct_from_main_doctor,
                'require_quality_check'  => $s->require_quality_check,
                'require_attachment'     => $s->require_attachment,
                'is_active'              => $s->is_active,
            ]),
            'costs' => $service->costs()->get()->map(fn ($c) => [
                'id'                       => $c->id,
                'cost_type'                => $c->cost_type,
                'cost_type_label'          => DentalServiceCost::costTypeLabel($c->cost_type),
                'cost_name'                => $c->cost_name,
                'standard_cost'            => $c->standard_cost,
                'is_excluded_from_kpi_base' => $c->is_excluded_from_kpi_base,
                'is_active'                => $c->is_active,
            ]),
            'cost_types' => [
                'material', 'lab', 'implant_fixture', 'medicine', 'imaging', 'chair_overhead', 'other',
            ],
            'roles' => [
                'counseling', 'examination', 'imaging', 'treatment_planning',
                'main_treatment', 'chairside_assist', 'impression', 'prosthetics', 'follow_up', 'aftercare',
            ],
        ]);
    }

    public function storeStep(Request $request, DentalService $service): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'step_name'              => 'required|string|max:200',
            'step_order'             => 'nullable|integer|min:0',
            'default_role'           => 'nullable|string|max:50',
            'estimated_minutes'      => 'nullable|integer|min:0',
            'kpi_share_percent'      => 'required|numeric|min:0|max:100',
            'deduct_from_main_doctor' => 'boolean',
            'require_quality_check'  => 'boolean',
            'require_attachment'     => 'boolean',
        ]);

        $service->steps()->create([...$data, 'is_active' => true,
            'step_order' => $data['step_order'] ?? ($service->steps()->max('step_order') + 1)]);

        return back()->with('success', 'Đã thêm công đoạn.');
    }

    public function updateStep(Request $request, DentalServiceStep $step): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'step_name'              => 'required|string|max:200',
            'step_order'             => 'nullable|integer|min:0',
            'default_role'           => 'nullable|string|max:50',
            'estimated_minutes'      => 'nullable|integer|min:0',
            'kpi_share_percent'      => 'required|numeric|min:0|max:100',
            'deduct_from_main_doctor' => 'boolean',
            'require_quality_check'  => 'boolean',
            'require_attachment'     => 'boolean',
            'is_active'              => 'boolean',
        ]);

        $step->update($data);

        return back()->with('success', 'Đã cập nhật công đoạn.');
    }

    public function destroyStep(DentalServiceStep $step): RedirectResponse
    {
        $this->authorize('dental.manage');
        $step->delete();

        return back()->with('success', 'Đã xóa công đoạn.');
    }

    public function storeCost(Request $request, DentalService $service): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'cost_type'                => 'required|string',
            'cost_name'                => 'required|string|max:200',
            'standard_cost'            => 'required|integer|min:0',
            'is_excluded_from_kpi_base' => 'boolean',
        ]);

        $service->costs()->create([...$data, 'is_active' => true]);

        return back()->with('success', 'Đã thêm chi phí.');
    }

    public function updateCost(Request $request, DentalServiceCost $cost): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'cost_type'                => 'required|string',
            'cost_name'                => 'required|string|max:200',
            'standard_cost'            => 'required|integer|min:0',
            'is_excluded_from_kpi_base' => 'boolean',
            'is_active'                => 'boolean',
        ]);

        $cost->update($data);

        return back()->with('success', 'Đã cập nhật chi phí.');
    }

    public function destroyCost(DentalServiceCost $cost): RedirectResponse
    {
        $this->authorize('dental.manage');
        $cost->delete();

        return back()->with('success', 'Đã xóa chi phí.');
    }
}
