<?php

namespace App\Services\Dental;

use App\Enums\KpiAllocationStatus;
use App\Enums\TreatmentItemStatus;
use App\Models\KpiAllocation;
use App\Models\KpiQualityRule;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentStepExecution;
use Illuminate\Support\Facades\DB;

class DentalQualityService
{
    /**
     * Apply a quality rule to all active allocations for an item.
     * Called when: refund, redo, complaint, missing_attachment, protocol_violation.
     */
    public function applyRuleToItem(TreatmentPlanItem $item, string $triggerEvent, ?string $reason = null): void
    {
        $rule = KpiQualityRule::where('trigger_event', $triggerEvent)
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return;
        }

        DB::transaction(function () use ($item, $rule, $reason) {
            $allocations = KpiAllocation::where('treatment_plan_item_id', $item->id)
                ->whereNotIn('status', [KpiAllocationStatus::Reversed->value, KpiAllocationStatus::Held->value])
                ->get();

            foreach ($allocations as $allocation) {
                if ($rule->reverse_kpi) {
                    $allocation->update([
                        'status' => KpiAllocationStatus::Reversed->value,
                        'reason' => $reason ?? $rule->rule_name,
                    ]);
                } elseif ($rule->hold_kpi) {
                    $allocation->update([
                        'status' => KpiAllocationStatus::Held->value,
                        'reason' => $reason ?? $rule->rule_name,
                    ]);
                } else {
                    // Apply quality_factor reduction
                    $newFinal = (int) round($allocation->kpi_amount * $rule->quality_factor * $allocation->collection_factor);
                    $allocation->update([
                        'quality_factor'   => $rule->quality_factor,
                        'final_kpi_amount' => $newFinal,
                    ]);
                }
            }
        });
    }

    /**
     * Hold KPI for a step execution when require_attachment is set but no file exists.
     */
    public function holdForMissingAttachment(TreatmentStepExecution $execution): void
    {
        $step = $execution->serviceStep;
        if (!$step?->require_attachment) {
            return;
        }

        $allocations = KpiAllocation::where('step_execution_id', $execution->id)->get();
        foreach ($allocations as $allocation) {
            if ($allocation->status->canHold()) {
                $allocation->update([
                    'status' => KpiAllocationStatus::Held->value,
                    'reason' => 'Thiếu file/hồ sơ bắt buộc cho công đoạn ' . $step->step_name,
                ]);
            }
        }
    }

    /**
     * Release held KPI after issue resolved.
     */
    public function releaseHold(KpiAllocation $allocation): void
    {
        if ($allocation->status !== KpiAllocationStatus::Held) {
            throw new \RuntimeException('KPI không đang ở trạng thái treo.');
        }
        $allocation->update([
            'status' => KpiAllocationStatus::Accrued->value,
            'reason' => null,
        ]);
    }

    /**
     * Recalculate collection_factor after a new payment and update final KPI amounts.
     */
    public function refreshCollectionFactor(TreatmentPlanItem $item): void
    {
        $newFactor = $item->collectionFactor();

        KpiAllocation::where('treatment_plan_item_id', $item->id)
            ->whereIn('status', [KpiAllocationStatus::Accrued->value, KpiAllocationStatus::PendingApproval->value])
            ->each(function (KpiAllocation $a) use ($newFactor) {
                $newFinal = (int) round($a->kpi_amount * $a->quality_factor * $newFactor);
                $a->update(['collection_factor' => $newFactor, 'final_kpi_amount' => $newFinal]);
            });
    }

    /**
     * Check if item status change requires KPI action.
     */
    public function handleItemStatusChange(TreatmentPlanItem $item, TreatmentItemStatus $newStatus): void
    {
        match ($newStatus) {
            TreatmentItemStatus::Cancelled => $this->applyRuleToItem($item, 'refund', 'Dịch vụ bị hủy'),
            TreatmentItemStatus::Redo      => $this->applyRuleToItem($item, 'redo', 'Dịch vụ làm lại do lỗi chủ quan'),
            TreatmentItemStatus::Warranty  => $this->applyRuleToItem($item, 'redo', 'Dịch vụ bảo hành'),
            default                        => null,
        };
    }
}
