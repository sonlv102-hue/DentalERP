<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\PayrollStatus;
use App\Enums\SalarySlipStatus;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\SalarySlip;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalarySlipService
{
    /**
     * Preview salary calculation for given employee + period without persisting.
     * Returns a data array the controller can forward to the form.
     */
    public function preview(Employee $employee, string $period): array
    {
        $contract = $this->activeContract($employee);

        [$from, $to] = $this->periodRange($period);
        $timesheets = Timesheet::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$from, $to])
            ->get();

        $baseSalary  = $contract?->base_salary ?? 0;
        $otHours     = (float) $timesheets->sum('ot_hours');
        $workDays    = $timesheets->count();

        // OT rate: base / (26 working-days × 8 hours) × 1.5
        $otRate = $baseSalary > 0 ? (int) round($baseSalary / (26 * 8) * 1.5) : 0;
        $otAmount = (int) round($otHours * $otRate);

        $commissionTotal = (int) $employee->commissionTransactions()
            ->where('period', $period)
            ->whereIn('status', [CommissionStatus::Approved->value, CommissionStatus::Paid->value])
            ->sum('amount');

        return [
            'base_salary'      => $baseSalary,
            'work_days'        => $workDays,
            'ot_hours'         => $otHours,
            'ot_rate'          => $otRate,
            'ot_amount'        => $otAmount,
            'commission_total' => $commissionTotal,
            'net_preview'      => $baseSalary + $otAmount + $commissionTotal,
        ];
    }

    /**
     * Generate (or regenerate draft) salary slip.
     * Pulls OT from timesheets and commission_transactions automatically.
     */
    public function generate(
        Employee $employee,
        string $period,
        int $baseSalary,
        int $deductions = 0,
        ?string $notes = null,
        float $otHours = 0,
        int $otRate = 0,
    ): SalarySlip {
        $otAmount = (int) round($otHours * $otRate);

        $commissions = $employee->commissionTransactions()
            ->with('invoice')
            ->where('period', $period)
            ->whereIn('status', [CommissionStatus::Approved->value, CommissionStatus::Paid->value])
            ->get();

        $commissionTotal = (int) $commissions->sum('amount');

        [$from, $to] = $this->periodRange($period);
        $workDays = Timesheet::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$from, $to])
            ->count();

        $netSalary = $baseSalary + $otAmount + $commissionTotal - $deductions;

        return DB::transaction(function () use (
            $employee, $period, $baseSalary, $otHours, $otRate, $otAmount,
            $commissionTotal, $deductions, $netSalary, $notes, $commissions, $workDays
        ) {
            SalarySlip::where('employee_id', $employee->id)
                ->where('period', $period)
                ->where('status', SalarySlipStatus::Draft)
                ->delete();

            $slip = SalarySlip::create([
                'employee_id'      => $employee->id,
                'branch_id'        => $employee->branch_id,
                'period'           => $period,
                'work_days'        => $workDays,
                'base_salary'      => $baseSalary,
                'ot_hours'         => $otHours,
                'ot_rate'          => $otRate,
                'ot_amount'        => $otAmount,
                'commission_total' => $commissionTotal,
                'deductions'       => $deductions,
                'net_salary'       => $netSalary,
                'status'           => SalarySlipStatus::Draft,
                'notes'            => $notes,
                'created_by'       => auth()->id(),
            ]);

            $items = [];

            if ($baseSalary > 0) {
                $items[] = ['type' => 'base', 'description' => "Lương cơ bản tháng {$period}", 'amount' => $baseSalary];
            }
            if ($otAmount > 0) {
                $items[] = ['type' => 'ot', 'description' => sprintf('Tăng ca %.1fh × %s₫/h', $otHours, number_format($otRate)), 'amount' => $otAmount];
            }
            foreach ($commissions as $c) {
                $items[] = [
                    'type'        => 'commission',
                    'description' => 'Hoa hồng HĐ ' . ($c->invoice?->code ?? '#' . $c->invoice_id),
                    'amount'      => (int) $c->amount,
                ];
            }
            if ($deductions > 0) {
                $items[] = ['type' => 'deduction', 'description' => 'Khấu trừ', 'amount' => -$deductions];
            }

            $slip->items()->createMany($items);

            return $slip;
        });
    }

    public function confirm(SalarySlip $slip): void
    {
        if ($slip->status !== SalarySlipStatus::Draft) {
            throw new \RuntimeException('Chỉ có thể duyệt phiếu lương ở trạng thái Nháp.');
        }
        $slip->update(['status' => SalarySlipStatus::Confirmed]);
    }

    /**
     * Mark paid.
     * Chỉ tạo Expense (→ ghi sổ TT152) khi KHÔNG có Payroll đã xác nhận/khóa
     * cho cùng kỳ + chi nhánh. Nếu có Payroll thì Payroll đã là nguồn sự thật
     * cho HKD sổ chi phí lương — tránh double-count.
     */
    public function markPaid(SalarySlip $slip): void
    {
        if ($slip->status !== SalarySlipStatus::Confirmed) {
            throw new \RuntimeException('Chỉ có thể thanh toán phiếu đã duyệt.');
        }

        $slip->load('employee');

        DB::transaction(function () use ($slip) {
            $slip->update([
                'status'  => SalarySlipStatus::Paid,
                'paid_at' => now(),
            ]);

            // Chỉ ghi chi phí qua Expense nếu không có Payroll chính thức cùng kỳ
            $hasPayroll = Payroll::where('branch_id', $slip->branch_id)
                ->where('month', (int) substr($slip->period, 5, 2))
                ->where('year', (int) substr($slip->period, 0, 4))
                ->whereIn('status', [PayrollStatus::Confirmed->value, PayrollStatus::Locked->value, PayrollStatus::Posted->value, PayrollStatus::Paid->value])
                ->exists();

            if (!$hasPayroll) {
                Expense::create([
                    'branch_id'      => $slip->branch_id,
                    'category'       => 'salary',
                    'description'    => "Lương {$slip->period} — {$slip->employee->full_name} ({$slip->employee->code})",
                    'amount'         => $slip->net_salary,
                    'expense_date'   => Carbon::parse($slip->period . '-01')->endOfMonth()->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'notes'          => "Phiếu lương #{$slip->id}",
                    'created_by'     => auth()->id(),
                ]);
            }
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function activeContract(Employee $employee): ?EmployeeContract
    {
        return EmployeeContract::where('employee_id', $employee->id)
            ->where('start_date', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
            ->latest('start_date')
            ->first();
    }

    private function periodRange(string $period): array
    {
        $start = Carbon::parse($period . '-01');
        return [$start->toDateString(), $start->endOfMonth()->toDateString()];
    }
}
