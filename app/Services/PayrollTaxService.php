<?php

namespace App\Services;

/**
 * Tính thuế TNCN theo biểu thuế lũy tiến từng phần (Thông tư 111/2013/TT-BTC).
 */
class PayrollTaxService
{
    // [ceiling, rate] — income up to ceiling (VND) is taxed at rate%
    private const BRACKETS = [
        [  5_000_000, 0.05],
        [ 10_000_000, 0.10],
        [ 18_000_000, 0.15],
        [ 32_000_000, 0.20],
        [ 52_000_000, 0.25],
        [ 80_000_000, 0.30],
        [PHP_INT_MAX, 0.35],
    ];

    /**
     * @param int $grossIncome        Tổng thu nhập (gross)
     * @param int $totalEmpInsurance  Tổng BH nhân viên chịu
     * @param int $familyDeduction    Giảm trừ bản thân
     * @param int $dependentDeduction Giảm trừ người phụ thuộc
     * @return int                    Thuế TNCN phải nộp (VND, làm tròn)
     */
    public function calculate(
        int $grossIncome,
        int $totalEmpInsurance,
        int $familyDeduction,
        int $dependentDeduction
    ): int {
        // Thu nhập chịu thuế = gross - BH NV
        $taxableIncome = max(0, $grossIncome - $totalEmpInsurance);

        // Thu nhập tính thuế = taxable - giảm trừ
        $taxableNet = $taxableIncome - $familyDeduction - $dependentDeduction;
        if ($taxableNet <= 0) {
            return 0;
        }

        $tax      = 0.0;
        $previous = 0;
        foreach (self::BRACKETS as [$ceiling, $rate]) {
            $bracketWidth = $ceiling - $previous;
            $taxable      = min($taxableNet - $previous, $bracketWidth);
            if ($taxable <= 0) {
                break;
            }
            $tax     += $taxable * $rate;
            $previous = $ceiling;
            if ($previous >= $taxableNet) {
                break;
            }
        }

        return (int) round($tax);
    }

    /** Taxable income (trước giảm trừ) = gross - BH NV */
    public function taxableIncome(int $grossIncome, int $totalEmpInsurance): int
    {
        return max(0, $grossIncome - $totalEmpInsurance);
    }
}
