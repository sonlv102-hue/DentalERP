<?php

namespace App\Services;

use App\Enums\AttendancePeriodStatus;
use App\Models\AttendanceAuditLog;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendancePeriodService
{
    /**
     * Create a new monthly attendance period and scaffold records for all active employees.
     */
    public function create(int $month, int $year, ?string $note, int $userId): AttendancePeriod
    {
        if (AttendancePeriod::where('month', $month)->where('year', $year)->exists()) {
            throw new \RuntimeException("Kỳ chấm công tháng {$month}/{$year} đã tồn tại.");
        }

        return DB::transaction(function () use ($month, $year, $note, $userId) {
            $period = AttendancePeriod::create([
                'code'       => AttendancePeriod::generateCode($month, $year),
                'month'      => $month,
                'year'       => $year,
                'status'     => AttendancePeriodStatus::Open->value,
                'note'       => $note,
                'created_by' => $userId,
            ]);

            $this->scaffoldRecords($period);

            $this->log($period, null, null, null, 'create', null, [
                'code' => $period->code, 'month' => $month, 'year' => $year,
            ], null, $userId);

            return $period;
        });
    }

    /**
     * Generate blank attendance_records for each active employee × each day of the month.
     */
    public function scaffoldRecords(AttendancePeriod $period): void
    {
        $employees = Employee::where('is_active', true)
            ->orWhere(function ($q) use ($period) {
                // Include resigned employees who started before end of this period
                $q->where('employment_status', 'resigned')
                  ->where('start_date', '<=', "{$period->year}-{$period->month}-" . $period->daysInMonth());
            })
            ->pluck('id');

        $daysInMonth = $period->daysInMonth();
        $rows        = [];
        $now         = now();

        foreach ($employees as $empId) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date    = Carbon::create($period->year, $period->month, $day);
                $weekday = (int) $date->format('N'); // 1=Mon..7=Sun

                // Skip if record already exists
                $rows[] = [
                    'attendance_period_id' => $period->id,
                    'employee_id'          => $empId,
                    'work_date'            => $date->format('Y-m-d'),
                    'weekday'              => $weekday,
                    'symbol'               => null,
                    'working_hours'        => 0,
                    'overtime_hours'       => 0,
                    'paid_workday'         => 0,
                    'unpaid_workday'       => 0,
                    'source_type'          => 'system',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AttendanceRecord::insertOrIgnore($chunk);
        }
    }

    /**
     * Lock the period — no further edits allowed.
     */
    public function lock(AttendancePeriod $period, int $userId): void
    {
        if ($period->isLocked()) {
            throw new \RuntimeException('Kỳ chấm công đã được khóa.');
        }

        $period->update([
            'status'    => AttendancePeriodStatus::Locked->value,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        $this->log($period, null, null, null, 'lock', null, ['status' => 'locked'], null, $userId);
    }

    /**
     * Unlock the period — requires a reason.
     */
    public function unlock(AttendancePeriod $period, string $reason, int $userId): void
    {
        if (! $period->isLocked()) {
            throw new \RuntimeException('Kỳ chấm công đang mở, không cần mở lại.');
        }

        $period->update([
            'status'         => AttendancePeriodStatus::Open->value,
            'unlocked_by'    => $userId,
            'unlocked_at'    => now(),
            'unlock_reason'  => $reason,
        ]);

        $this->log($period, null, null, null, 'unlock', ['status' => 'locked'], ['status' => 'open', 'reason' => $reason], $reason, $userId);
    }

    private function log(
        AttendancePeriod $period,
        ?AttendanceRecord $record,
        ?int $employeeId,
        ?string $workDate,
        string $action,
        ?array $oldValue,
        ?array $newValue,
        ?string $reason,
        int $userId
    ): void {
        AttendanceAuditLog::create([
            'attendance_period_id'  => $period->id,
            'attendance_record_id'  => $record?->id,
            'employee_id'           => $employeeId ?? $record?->employee_id,
            'work_date'             => $workDate ?? $record?->work_date,
            'action'                => $action,
            'old_value'             => $oldValue,
            'new_value'             => $newValue,
            'reason'                => $reason,
            'changed_by'            => $userId,
            'changed_at'            => now(),
        ]);
    }

    public function logRecordChange(AttendancePeriod $period, AttendanceRecord $record, array $old, array $new, int $userId): void
    {
        $this->log($period, $record, null, null, 'update', $old, $new, null, $userId);
    }
}
