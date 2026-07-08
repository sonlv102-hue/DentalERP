<?php

namespace App\Services;

use App\Enums\KpiAllocationStatus;
use App\Enums\PayrollStatus;
use App\Models\AttendancePeriod;
use App\Models\Employee;
use App\Models\KpiAllocation;
use App\Models\Payroll;
use App\Models\PayrollAuditLog;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Services\Hkd\HkdJournalService;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService
{
    public function __construct(private PayrollCalculationService $calc) {}

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(
        int $month,
        int $year,
        int $userId,
        ?int $branchId = null,
        ?int $attendancePeriodId = null,
        ?string $note = null
    ): Payroll {
        if (Payroll::where('month', $month)->where('year', $year)
            ->where('branch_id', $branchId)->exists()) {
            throw new \RuntimeException("Bảng lương tháng {$month}/{$year} đã tồn tại.");
        }

        return DB::transaction(function () use ($month, $year, $userId, $branchId, $attendancePeriodId, $note) {
            $payroll = Payroll::create([
                'code'                  => Payroll::generateCode($month, $year),
                'month'                 => $month,
                'year'                  => $year,
                'branch_id'             => $branchId,
                'attendance_period_id'  => $attendancePeriodId,
                'status'                => PayrollStatus::Draft,
                'created_by'            => $userId,
                'note'                  => $note,
            ]);

            $this->scaffoldItems($payroll, $branchId);
            $this->log($payroll, null, null, 'create', null, null, null, $userId);

            return $payroll;
        });
    }

    // ─── Scaffold payroll_items ───────────────────────────────────────────────

    private function scaffoldItems(Payroll $payroll, ?int $branchId): void
    {
        $settings = PayrollSetting::current();

        $query = Employee::with('department')
            ->where('employment_status', 'active')
            ->where('is_active', true);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $attendanceDays = $this->getAttendanceDays($payroll);
        $period = sprintf('%04d-%02d', $payroll->year, $payroll->month);
        $kpiByEmployee = $this->getApprovedKpi($period, $branchId);

        foreach ($query->get() as $emp) {
            $actual     = $attendanceDays[$emp->id] ?? (float) $emp->standard_working_days;
            $kpiAmount  = $kpiByEmployee[$emp->id] ?? 0;
            $values     = $this->calc->fromEmployee($emp, $actual, $kpiAmount, $settings);
            PayrollItem::create(array_merge($values, [
                'payroll_id'  => $payroll->id,
                'employee_id' => $emp->id,
                'status'      => 'draft',
                'created_by'  => $payroll->created_by,
            ]));
        }

        $this->recomputeTotals($payroll);
    }

    /**
     * Lấy tổng KPI đã duyệt (Approved) theo nhân viên cho một kỳ lương.
     * Chỉ lấy final_kpi_amount của KpiAllocation có status = Approved.
     *
     * @return array<int, int>  employee_id => total_final_kpi_amount
     */
    private function getApprovedKpi(string $period, ?int $branchId): array
    {
        $query = KpiAllocation::where('period', $period)
            ->where('status', KpiAllocationStatus::Approved->value)
            ->selectRaw('employee_id, SUM(final_kpi_amount) as total_kpi')
            ->groupBy('employee_id');

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $query->pluck('total_kpi', 'employee_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Pull actual working days from a linked (locked) attendance period. */
    private function getAttendanceDays(Payroll $payroll): array
    {
        if (!$payroll->attendance_period_id) {
            return [];
        }

        $ap = AttendancePeriod::find($payroll->attendance_period_id);
        if (!$ap) {
            return [];
        }

        // Sum days where symbol counts as workday (X, CT = 1; OT counted separately)
        $rows = DB::table('attendance_records as ar')
            ->join('attendance_symbols as sym', 'sym.code', '=', 'ar.symbol')
            ->where('ar.attendance_period_id', $ap->id)
            ->where('sym.counts_as_workday', true)
            ->selectRaw('ar.employee_id, COUNT(*) as days')
            ->groupBy('ar.employee_id')
            ->get();

        return $rows->pluck('days', 'employee_id')
            ->map(fn ($d) => (float) $d)
            ->all();
    }

    // ─── Recalculate payroll totals ───────────────────────────────────────────

    public function recomputeTotals(Payroll $payroll): void
    {
        $totals = DB::table('payroll_items')
            ->where('payroll_id', $payroll->id)
            ->selectRaw(implode(', ', [
                'SUM(base_salary) as total_base_salary',
                'SUM(fixed_allowance+responsibility_allowance+lunch_allowance+phone_allowance+travel_allowance+performance_kpi_amount+other_allowance) as total_allowances',
                'SUM(total_company_insurance) as total_company_insurance',
                'SUM(total_employee_insurance) as total_employee_insurance',
                'SUM(personal_income_tax) as total_personal_income_tax',
                'SUM(union_fee_amount) as total_union_fee',
                'SUM(gross_income) as total_gross_income',
                'SUM(total_deductions) as total_deductions',
                'SUM(net_salary) as total_net_salary',
            ]))
            ->first();

        $payroll->update([
            'total_base_salary'        => $totals->total_base_salary ?? 0,
            'total_allowances'         => $totals->total_allowances ?? 0,
            'total_company_insurance'  => $totals->total_company_insurance ?? 0,
            'total_employee_insurance' => $totals->total_employee_insurance ?? 0,
            'total_personal_income_tax'=> $totals->total_personal_income_tax ?? 0,
            'total_union_fee'          => $totals->total_union_fee ?? 0,
            'total_gross_income'       => $totals->total_gross_income ?? 0,
            'total_deductions'         => $totals->total_deductions ?? 0,
            'total_net_salary'         => $totals->total_net_salary ?? 0,
        ]);
    }

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function confirm(Payroll $payroll, int $userId): void
    {
        if (!$payroll->status->canConfirm()) {
            throw new \RuntimeException('Chỉ có thể xác nhận bảng lương ở trạng thái Nháp.');
        }
        DB::transaction(function () use ($payroll, $userId) {
            $payroll->update([
                'status'       => PayrollStatus::Confirmed,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);
            $payroll->items()->update(['status' => 'confirmed']);
            $this->log($payroll, null, null, 'confirm', 'status', ['status' => 'draft'], ['status' => 'confirmed'], $userId);
        });

        // Ghi sổ chi phí lương TT152 (bỏ qua nếu chi nhánh không có HKD profile)
        app(HkdJournalService::class)->postSalary($payroll->fresh());
    }

    public function unconfirm(Payroll $payroll, int $userId): void
    {
        if (!$payroll->status->canUnconfirm()) {
            throw new \RuntimeException('Chỉ có thể hủy xác nhận khi bảng lương đang ở trạng thái Đã xác nhận.');
        }
        DB::transaction(function () use ($payroll, $userId) {
            $payroll->update([
                'status'       => PayrollStatus::Draft,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);
            $payroll->items()->update(['status' => 'draft']);
            $this->log($payroll, null, null, 'unconfirm', 'status', ['status' => 'confirmed'], ['status' => 'draft'], $userId);
        });
    }

    public function lock(Payroll $payroll, int $userId): void
    {
        if (!$payroll->status->canLock()) {
            throw new \RuntimeException('Chỉ có thể khóa bảng lương đã xác nhận.');
        }
        DB::transaction(function () use ($payroll, $userId) {
            $payroll->update([
                'status'    => PayrollStatus::Locked,
                'locked_by' => $userId,
                'locked_at' => now(),
            ]);
            $payroll->items()->update(['status' => 'locked']);
            $this->log($payroll, null, null, 'lock', 'status', ['status' => 'confirmed'], ['status' => 'locked'], $userId);
        });
    }

    public function unlock(Payroll $payroll, string $reason, int $userId): void
    {
        if (!$payroll->status->canUnlock()) {
            throw new \RuntimeException('Chỉ có thể mở khóa bảng lương đang bị khóa.');
        }
        if (in_array($payroll->status->value, ['posted', 'paid'])) {
            throw new \RuntimeException('Không thể mở khóa bảng lương đã hạch toán hoặc đã thanh toán.');
        }
        if (trim($reason) === '') {
            throw new \RuntimeException('Phải nhập lý do mở khóa.');
        }
        DB::transaction(function () use ($payroll, $reason, $userId) {
            $payroll->update(['status' => PayrollStatus::Confirmed]);
            $payroll->items()->update(['status' => 'confirmed']);
            $this->log($payroll, null, null, 'unlock', 'status', ['status' => 'locked'], ['status' => 'confirmed'], $userId, $reason);
        });
    }

    // ─── Audit log ────────────────────────────────────────────────────────────

    public function log(
        Payroll $payroll,
        ?int $itemId,
        ?int $employeeId,
        string $action,
        ?string $field,
        mixed $old,
        mixed $new,
        int $userId,
        ?string $reason = null
    ): void {
        PayrollAuditLog::create([
            'payroll_id'      => $payroll->id,
            'payroll_item_id' => $itemId,
            'employee_id'     => $employeeId,
            'action'          => $action,
            'field_name'      => $field,
            'old_value'       => $old ? (is_array($old) ? $old : ['value' => $old]) : null,
            'new_value'       => $new ? (is_array($new) ? $new : ['value' => $new]) : null,
            'reason'          => $reason,
            'changed_by'      => $userId,
            'changed_at'      => now(),
        ]);
    }
}
