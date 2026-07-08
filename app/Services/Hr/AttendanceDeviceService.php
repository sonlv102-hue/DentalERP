<?php

namespace App\Services\Hr;

use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Collection;

class AttendanceDeviceService
{
    /**
     * Pull raw logs from device → persist to attendance_logs.
     * Returns ['total' => int, 'new' => int].
     */
    public function sync(AttendanceDevice $device): array
    {
        $zk = new ZkSocketService($device->ip, $device->port);

        try {
            $zk->connect();
            $logs = $zk->getAttendanceLogs();
            $zk->disconnect();
        } catch (\Throwable $e) {
            $zk->disconnect();
            throw $e;
        }

        // Map user_pin → employee_id once
        $pinMap = Employee::whereNotNull('zk_user_pin')
            ->pluck('id', 'zk_user_pin');

        $new = 0;

        foreach ($logs as $log) {
            $created = AttendanceLog::firstOrCreate(
                [
                    'device_id'  => $device->id,
                    'user_pin'   => $log['user_pin'],
                    'punched_at' => $log['punched_at'],
                ],
                [
                    'employee_id'  => $pinMap[$log['user_pin']] ?? null,
                    'status'       => $log['status'],
                    'punch_type'   => $log['punch_type'],
                    'is_processed' => false,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $new++;
            }
        }

        $device->update(['last_sync_at' => now()]);

        return ['total' => count($logs), 'new' => $new];
    }

    /**
     * Process unprocessed attendance_logs → upsert timesheets.
     *
     * Groups by employee+date; first IN punch → check_in, last OUT → check_out.
     * OT hours = duration between status=4 (ot_in) and status=5 (ot_out).
     *
     * @return int Number of timesheet rows created/updated.
     */
    public function processToTimesheets(?string $date = null): int
    {
        $query = AttendanceLog::with('employee')
            ->where('is_processed', false)
            ->whereNotNull('employee_id');

        if ($date) {
            $query->whereDate('punched_at', $date);
        }

        $grouped = $query->get()->groupBy(
            fn (AttendanceLog $l) => $l->employee_id . '|' . $l->punched_at->toDateString()
        );

        $count = 0;

        foreach ($grouped as $dayLogs) {
            /** @var Collection<AttendanceLog> $dayLogs */
            $firstLog = $dayLogs->first();
            $empId    = $firstLog->employee_id;
            $workDate = $firstLog->punched_at->toDateString();

            $checkIn  = $dayLogs->where('status', 0)->sortBy('punched_at')->first()?->punched_at;
            $checkOut = $dayLogs->where('status', 1)->sortByDesc('punched_at')->first()?->punched_at;

            // OT hours from ot_in / ot_out pair
            $otIn  = $dayLogs->where('status', 4)->sortBy('punched_at')->first()?->punched_at;
            $otOut = $dayLogs->where('status', 5)->sortByDesc('punched_at')->first()?->punched_at;
            $otHours = ($otIn && $otOut && $otOut->gt($otIn))
                ? round($otOut->diffInMinutes($otIn) / 60, 2)
                : 0.0;

            $employee = Employee::find($empId);

            Timesheet::updateOrCreate(
                ['employee_id' => $empId, 'work_date' => $workDate],
                [
                    'branch_id'  => $employee?->branch_id ?? $firstLog->device->branch_id,
                    'check_in'   => $checkIn,
                    'check_out'  => $checkOut,
                    'ot_hours'   => $otHours,
                ]
            );

            $dayLogs->each(fn ($l) => $l->update(['is_processed' => true]));
            $count++;
        }

        return $count;
    }
}
