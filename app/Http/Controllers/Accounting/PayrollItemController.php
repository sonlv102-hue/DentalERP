<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollItemController extends Controller
{
    public function __construct(
        private PayrollCalculationService $calc,
        private PayrollPeriodService $periodService,
    ) {}

    /**
     * AJAX PUT — update one payroll item and return recalculated values.
     */
    public function update(Request $request, Payroll $payroll, PayrollItem $item): JsonResponse
    {
        $this->authorize('accounting.manage');

        if ($item->payroll_id !== $payroll->id) {
            return response()->json(['message' => 'Dòng lương không thuộc bảng lương này.'], 422);
        }
        if (!$payroll->status->canEdit()) {
            return response()->json(['message' => 'Bảng lương đã khóa, vui lòng mở khóa trước khi chỉnh sửa.'], 403);
        }

        $data = $request->validate([
            'actual_working_days'          => 'sometimes|numeric|min:0|max:31',
            'base_salary'                  => 'sometimes|integer|min:0',
            'insurance_salary'             => 'sometimes|integer|min:0',
            'fixed_allowance'              => 'sometimes|integer|min:0',
            'responsibility_allowance'     => 'sometimes|integer|min:0',
            'lunch_allowance'              => 'sometimes|integer|min:0',
            'phone_allowance'              => 'sometimes|integer|min:0',
            'travel_allowance'             => 'sometimes|integer|min:0',
            'performance_kpi_amount'       => 'sometimes|integer|min:0',
            'other_allowance'              => 'sometimes|integer|min:0',
            'social_insurance_enabled'     => 'sometimes|boolean',
            'insurance_manual_override'    => 'sometimes|boolean',
            'company_social_insurance'     => 'sometimes|integer|min:0',
            'company_health_insurance'     => 'sometimes|integer|min:0',
            'company_unemployment_insurance'=> 'sometimes|integer|min:0',
            'employee_social_insurance'    => 'sometimes|integer|min:0',
            'employee_health_insurance'    => 'sometimes|integer|min:0',
            'employee_unemployment_insurance'=> 'sometimes|integer|min:0',
            'dependents_count'             => 'sometimes|integer|min:0',
            'pit_manual_override'          => 'sometimes|boolean',
            'pit_manual_amount'            => 'nullable|integer|min:0',
            'note'                         => 'nullable|string|max:500',
        ]);

        $oldValues = $item->only(array_keys($data));
        $item->fill($data);

        // Recompute workday_ratio if actual days changed
        if (isset($data['actual_working_days'])) {
            $std = $item->standard_working_days ?: 26;
            $item->workday_ratio = round($item->actual_working_days / $std, 4);
        }

        $settings = PayrollSetting::current();
        $this->calc->recalculate($item, $settings);

        // Flag manual overrides
        if (isset($data['insurance_manual_override'])) {
            $item->insurance_manual_override = (bool) $data['insurance_manual_override'];
        }

        $item->updated_by = auth()->id();
        $item->save();

        // Log the change
        $this->periodService->log(
            $payroll, $item->id, $item->employee_id,
            'update', implode(',', array_keys($data)),
            $oldValues, $item->only(array_keys($data)),
            auth()->id()
        );

        // Recompute payroll totals
        $this->periodService->recomputeTotals($payroll);

        return response()->json([
            'item'   => $this->itemDto($item),
            'totals' => $payroll->refresh()->only([
                'total_base_salary', 'total_allowances', 'total_company_insurance',
                'total_employee_insurance', 'total_personal_income_tax', 'total_union_fee',
                'total_gross_income', 'total_deductions', 'total_net_salary',
            ]),
        ]);
    }

    private function itemDto(PayrollItem $i): array
    {
        return [
            'id'                             => $i->id,
            'actual_working_days'            => (float) $i->actual_working_days,
            'workday_ratio'                  => (float) $i->workday_ratio,
            'salary_by_workday'              => $i->salary_by_workday,
            'base_salary'                    => $i->base_salary,
            'insurance_salary'               => $i->insurance_salary,
            'fixed_allowance'                => $i->fixed_allowance,
            'responsibility_allowance'       => $i->responsibility_allowance,
            'lunch_allowance'                => $i->lunch_allowance,
            'phone_allowance'                => $i->phone_allowance,
            'travel_allowance'               => $i->travel_allowance,
            'performance_kpi_amount'         => $i->performance_kpi_amount,
            'other_allowance'                => $i->other_allowance,
            'social_insurance_enabled'       => $i->social_insurance_enabled,
            'insurance_manual_override'      => $i->insurance_manual_override,
            'company_social_insurance'       => $i->company_social_insurance,
            'company_health_insurance'       => $i->company_health_insurance,
            'company_unemployment_insurance' => $i->company_unemployment_insurance,
            'total_company_insurance'        => $i->total_company_insurance,
            'employee_social_insurance'      => $i->employee_social_insurance,
            'employee_health_insurance'      => $i->employee_health_insurance,
            'employee_unemployment_insurance'=> $i->employee_unemployment_insurance,
            'total_employee_insurance'       => $i->total_employee_insurance,
            'taxable_income'                 => $i->taxable_income,
            'dependents_count'               => $i->dependents_count,
            'family_deduction'               => $i->family_deduction,
            'dependent_deduction'            => $i->dependent_deduction,
            'personal_income_tax'            => $i->personal_income_tax,
            'pit_manual_override'            => $i->pit_manual_override,
            'pit_manual_amount'              => $i->pit_manual_amount,
            'union_fee_amount'               => $i->union_fee_amount,
            'gross_income'                   => $i->gross_income,
            'total_deductions'               => $i->total_deductions,
            'net_salary'                     => $i->net_salary,
            'note'                           => $i->note,
        ];
    }
}
