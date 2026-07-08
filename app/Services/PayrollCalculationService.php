<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;

/**
 * Tính toán tất cả các khoản lương cho một PayrollItem.
 * Hàm calculate() trả về mảng values để update/create PayrollItem.
 */
class PayrollCalculationService
{
    public function __construct(private PayrollTaxService $taxService) {}

    /**
     * Tính lại toàn bộ PayrollItem dựa trên giá trị hiện tại của nó.
     * Gọi sau khi cập nhật bất kỳ trường nào (ngày công, phụ cấp, BH…).
     */
    public function recalculate(PayrollItem $item, PayrollSetting $settings): PayrollItem
    {
        $vals = $this->compute($item->toArray(), $settings);
        $item->fill($vals);
        return $item;
    }

    /**
     * Tạo mảng giá trị khởi tạo PayrollItem từ Employee.
     */
    public function fromEmployee(
        Employee $emp,
        float $actualDays,
        int $kpiAmount,
        PayrollSetting $settings
    ): array {
        $standard = $emp->standard_working_days ?: 26;
        $ratio    = $standard > 0 ? round($actualDays / $standard, 4) : 0;

        $base = [
            'employee_code'           => $emp->code,
            'employee_name'           => $emp->full_name,
            'position_name'           => $emp->position ?? '',
            'department_name'         => $emp->department?->name ?? '',
            'department_id'           => $emp->department_id,
            'standard_working_days'   => $standard,
            'actual_working_days'     => $actualDays,
            'workday_ratio'           => $ratio,
            'base_salary'             => $emp->base_salary,
            'insurance_salary'        => $emp->base_salary,
            'fixed_allowance'         => $emp->fixed_allowance,
            'responsibility_allowance'=> $emp->responsibility_allowance,
            'lunch_allowance'         => $emp->lunch_allowance,
            'phone_allowance'         => $emp->phone_allowance,
            'travel_allowance'        => $emp->travel_allowance,
            'performance_kpi_amount'  => $kpiAmount,
            'other_allowance'         => 0,
            'social_insurance_enabled'=> $emp->social_insurance_enabled,
            'dependents_count'        => $emp->dependents_count,
            'pit_manual_override'     => false,
            'insurance_manual_override'=> false,
            'salary_manual_override'  => false,
            'kpi_source_type'         => 'manual',
        ];

        return $this->compute($base, $settings);
    }

    /**
     * Core formula — works on plain array (not model) for flexibility.
     */
    public function compute(array $d, PayrollSetting $s): array
    {
        $ratio         = (float) ($d['workday_ratio'] ?? 0);
        $baseSalary    = (int) ($d['base_salary'] ?? 0);
        $insuranceSal  = (int) ($d['insurance_salary'] ?? $baseSalary);
        $salByWorkday  = (int) round($baseSalary * $ratio);

        $fixedAllow    = (int) ($d['fixed_allowance'] ?? 0);
        $respAllow     = (int) ($d['responsibility_allowance'] ?? 0);
        $lunchAllow    = (int) ($d['lunch_allowance'] ?? 0);
        $phoneAllow    = (int) ($d['phone_allowance'] ?? 0);
        $travelAllow   = (int) ($d['travel_allowance'] ?? 0);
        $kpiAmount     = (int) ($d['performance_kpi_amount'] ?? 0);
        $otherAllow    = (int) ($d['other_allowance'] ?? 0);

        $gross = $salByWorkday + $fixedAllow + $respAllow
               + $lunchAllow + $phoneAllow + $travelAllow
               + $kpiAmount + $otherAllow;

        // Insurance
        $insEnabled = (bool) ($d['social_insurance_enabled'] ?? false);
        $insOverride = (bool) ($d['insurance_manual_override'] ?? false);

        if ($insEnabled && !$insOverride) {
            $compSI   = (int) round($insuranceSal * $s->company_social_insurance_rate / 100);
            $compHI   = (int) round($insuranceSal * $s->company_health_insurance_rate / 100);
            $compUI   = (int) round($insuranceSal * $s->company_unemployment_insurance_rate / 100);
            $empSI    = (int) round($insuranceSal * $s->employee_social_insurance_rate / 100);
            $empHI    = (int) round($insuranceSal * $s->employee_health_insurance_rate / 100);
            $empUI    = (int) round($insuranceSal * $s->employee_unemployment_insurance_rate / 100);
        } elseif ($insEnabled && $insOverride) {
            // Keep manual values passed in $d
            $compSI = (int) ($d['company_social_insurance'] ?? 0);
            $compHI = (int) ($d['company_health_insurance'] ?? 0);
            $compUI = (int) ($d['company_unemployment_insurance'] ?? 0);
            $empSI  = (int) ($d['employee_social_insurance'] ?? 0);
            $empHI  = (int) ($d['employee_health_insurance'] ?? 0);
            $empUI  = (int) ($d['employee_unemployment_insurance'] ?? 0);
        } else {
            $compSI = $compHI = $compUI = $empSI = $empHI = $empUI = 0;
        }

        $totalCompIns = $compSI + $compHI + $compUI;
        $totalEmpIns  = $empSI + $empHI + $empUI;

        // KPCĐ (borne by company)
        $unionFee = (int) round($insuranceSal * $s->union_fee_rate / 100);

        // PIT
        $dependents   = (int) ($d['dependents_count'] ?? 0);
        $famDeduction = (int) $s->family_deduction_amount;
        $depDeduction = $dependents * (int) $s->dependent_deduction_amount;
        $taxableInc   = $this->taxService->taxableIncome($gross, $totalEmpIns);

        $pitOverride  = (bool) ($d['pit_manual_override'] ?? false);
        if ($pitOverride && isset($d['pit_manual_amount'])) {
            $pit = (int) $d['pit_manual_amount'];
        } else {
            $pit = $this->taxService->calculate($gross, $totalEmpIns, $famDeduction, $depDeduction);
        }

        $totalDeductions = $totalEmpIns + $pit;
        $netSalary       = max(0, $gross - $totalDeductions);

        return array_merge($d, [
            'salary_by_workday'            => $salByWorkday,
            'company_social_insurance'     => $compSI,
            'company_health_insurance'     => $compHI,
            'company_unemployment_insurance' => $compUI,
            'total_company_insurance'      => $totalCompIns,
            'employee_social_insurance'    => $empSI,
            'employee_health_insurance'    => $empHI,
            'employee_unemployment_insurance' => $empUI,
            'total_employee_insurance'     => $totalEmpIns,
            'union_fee_amount'             => $unionFee,
            'taxable_income'               => $taxableInc,
            'family_deduction'             => $famDeduction,
            'dependent_deduction'          => $depDeduction,
            'personal_income_tax'          => $pit,
            'gross_income'                 => $gross,
            'total_deductions'             => $totalDeductions,
            'net_salary'                   => $netSalary,
        ]);
    }
}
