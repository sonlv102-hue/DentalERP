<?php

namespace App\Http\Controllers\Dental;

use App\Enums\TreatmentItemStatus;
use App\Enums\TreatmentStepStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentStepExecution;
use App\Models\TreatmentStepParticipant;
use App\Services\Dental\DentalKpiService;
use App\Services\Dental\DentalQualityService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentExecutionController extends Controller
{
    public function __construct(
        private DentalKpiService     $kpiService,
        private DentalQualityService $qualityService,
        private InventoryService     $inventoryService,
    ) {}

    /** Show step executions for a treatment plan item */
    public function show(TreatmentPlanItem $item): Response
    {
        $this->authorize('treatment_plans.edit');

        $item->load(['service.steps', 'plan.patient', 'responsibleDoctor',
            'stepExecutions.serviceStep', 'stepExecutions.performer',
            'stepExecutions.assistant', 'stepExecutions.participants.employee']);

        $steps = $item->service?->steps ?? collect();

        return Inertia::render('Dental/TreatmentExecution/Show', [
            'item' => [
                'id'           => $item->id,
                'name'         => $item->name,
                'tooth_number' => $item->tooth_number,
                'status'       => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->color(),
                'patient_name' => $item->plan?->patient?->full_name,
                'plan_code'    => $item->plan?->code,
                'doctor_name'  => $item->responsibleDoctor?->full_name,
            ],
            'steps'      => $steps->map(fn ($s) => [
                'id'                     => $s->id,
                'step_name'              => $s->step_name,
                'step_order'             => $s->step_order,
                'default_role'           => $s->default_role,
                'kpi_share_percent'      => $s->kpi_share_percent,
                'require_quality_check'  => $s->require_quality_check,
                'require_attachment'     => $s->require_attachment,
                'deduct_from_main_doctor' => $s->deduct_from_main_doctor,
            ]),
            'executions' => $item->stepExecutions->map(fn ($e) => [
                'id'             => $e->id,
                'service_step_id' => $e->service_step_id,
                'step_name'      => $e->serviceStep?->step_name,
                'performed_by'   => $e->performed_by,
                'performer_name' => $e->performer?->full_name,
                'assisted_by'    => $e->assisted_by,
                'assistant_name' => $e->assistant?->full_name,
                'started_at'     => $e->started_at?->format('d/m/Y H:i'),
                'ended_at'       => $e->ended_at?->format('d/m/Y H:i'),
                'status'         => $e->status->value,
                'status_label'   => $e->status->label(),
                'status_color'   => $e->status->color(),
                'quality_status' => $e->quality_status,
                'note'           => $e->note,
                'participants'   => $e->participants->map(fn ($p) => [
                    'id'            => $p->id,
                    'employee_id'   => $p->employee_id,
                    'employee_name' => $p->employee?->full_name,
                    'role'          => $p->role,
                    'share_percent' => $p->share_percent,
                ]),
            ]),
            'employees' => Employee::where('is_active', true)->orderBy('full_name')
                ->get(['id', 'full_name', 'role_type']),
            'step_statuses' => collect(TreatmentStepStatus::cases())->map(fn ($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
        ]);
    }

    public function storeExecution(Request $request, TreatmentPlanItem $item): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'service_step_id' => 'required|exists:dental_service_steps,id',
            'appointment_id'  => 'nullable|exists:appointments,id',
            'performed_by'    => 'required|exists:employees,id',
            'assisted_by'     => 'nullable|exists:employees,id',
            'started_at'      => 'nullable|date',
            'ended_at'        => 'nullable|date',
            'note'            => 'nullable|string|max:1000',
            'participants'    => 'nullable|array',
            'participants.*.employee_id'   => 'required|exists:employees,id',
            'participants.*.role'          => 'nullable|string|max:50',
            'participants.*.share_percent' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($item, $data) {
            $execution = TreatmentStepExecution::create([
                'treatment_plan_item_id' => $item->id,
                'service_step_id'        => $data['service_step_id'],
                'appointment_id'         => $data['appointment_id'] ?? null,
                'performed_by'           => $data['performed_by'],
                'assisted_by'            => $data['assisted_by'] ?? null,
                'started_at'             => $data['started_at'] ?? null,
                'ended_at'               => $data['ended_at'] ?? null,
                'status'                 => TreatmentStepStatus::Pending->value,
                'quality_status'         => 'pending',
                'note'                   => $data['note'] ?? null,
                'created_by'             => auth()->id(),
            ]);

            foreach ($data['participants'] ?? [] as $p) {
                TreatmentStepParticipant::create([
                    'step_execution_id' => $execution->id,
                    ...$p,
                ]);
            }
        });

        return back()->with('success', 'Đã ghi nhận công đoạn.');
    }

    public function completeExecution(Request $request, TreatmentStepExecution $execution): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'ended_at'       => 'nullable|date',
            'quality_status' => 'nullable|in:passed,failed',
            'note'           => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($execution, $data) {
            $execution->update([
                'status'         => TreatmentStepStatus::Done->value,
                'ended_at'       => $data['ended_at'] ?? now(),
                'quality_status' => $data['quality_status'] ?? 'passed',
                'note'           => $data['note'] ?? $execution->note,
            ]);

            // Check if quality failed — apply hold
            if (($data['quality_status'] ?? 'passed') === 'failed') {
                $this->qualityService->holdForMissingAttachment($execution);
            }
        });

        // Auto-consume inventory materials outside transaction (idempotent)
        $this->inventoryService->consumeForExecution($execution->fresh());

        return back()->with('success', 'Đã hoàn thành công đoạn.');
    }

    public function updateItemStatus(Request $request, TreatmentPlanItem $item): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'status' => 'required|string|in:pending,scheduled,in_progress,completed,cancelled,warranty,redo',
            'reason' => 'nullable|string|max:500',
        ]);

        $newStatus = TreatmentItemStatus::from($data['status']);

        DB::transaction(function () use ($item, $newStatus, $data) {
            $old = $item->status;
            $item->update([
                'status'       => $newStatus->value,
                'started_at'   => $newStatus === TreatmentItemStatus::InProgress && !$item->started_at ? now() : $item->started_at,
                'completed_at' => $newStatus === TreatmentItemStatus::Completed ? now() : null,
            ]);

            // KPI side-effects
            if ($newStatus === TreatmentItemStatus::Completed) {
                $this->kpiService->calculateForItem($item->fresh());
            } elseif ($newStatus->requiresKpiReversal()) {
                $this->qualityService->handleItemStatusChange($item, $newStatus);
            }
        });

        return back()->with('success', 'Đã cập nhật trạng thái dịch vụ.');
    }
}
