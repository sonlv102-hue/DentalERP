<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\KpiAllocationStatus;
use App\Models\CommissionRule;
use App\Models\CommissionTransaction;
use App\Models\KpiAllocation;
use App\Models\PatientInvoice;

class CommissionService
{
    /**
     * Calculate and record commissions when an invoice is fully paid.
     * Called from InvoiceService::addPayment after status becomes Paid.
     *
     * Guard: employees who already have KpiAllocation records (via DentalKpiService)
     * for any item in this invoice's treatment plan are skipped — they receive
     * operational KPI instead of a flat commission to avoid double-counting.
     */
    public function calculateForInvoice(PatientInvoice $invoice): void
    {
        // Idempotent: skip if already processed
        if (CommissionTransaction::where('invoice_id', $invoice->id)->exists()) {
            return;
        }

        $plan = $invoice->treatmentPlan;
        $period = now()->format('Y-m');

        // Employees who have KPI allocations for this treatment plan's items
        // (any non-reversed status) are managed by DentalKpiService — skip them.
        $kpiEmployeeIds = $plan
            ? KpiAllocation::whereHas('planItem', fn ($q) => $q->where('treatment_plan_id', $plan->id))
                ->whereNotIn('status', [KpiAllocationStatus::Reversed->value])
                ->pluck('employee_id')
                ->unique()
                ->all()
            : [];

        // Collect doctor_id + consultant_id from treatment plan
        $employeeIds = array_filter(
            [$plan?->doctor_id, $plan?->consultant_id],
            fn ($id) => $id !== null
        );

        foreach (array_unique($employeeIds) as $employeeId) {
            // Skip: this employee's KPI is handled by DentalKpiService
            if (in_array($employeeId, $kpiEmployeeIds, true)) {
                continue;
            }

            $rules = CommissionRule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->get();

            foreach ($rules as $rule) {
                $amount = $rule->type === CommissionType::RevenuePercentage
                    ? (int) round($invoice->total * $rule->value / 100)
                    : (int) $rule->value;

                if ($amount <= 0) {
                    continue;
                }

                CommissionTransaction::create([
                    'employee_id'       => $employeeId,
                    'invoice_id'        => $invoice->id,
                    'treatment_plan_id' => $plan?->id,
                    'amount'            => $amount,
                    'period'            => $period,
                    'status'            => CommissionStatus::Pending,
                ]);
            }
        }
    }
}
