<?php

namespace App\Console\Commands;

use App\Models\AttendanceDevice;
use App\Services\Hr\AttendanceDeviceService;
use Illuminate\Console\Command;

class SyncAttendanceCommand extends Command
{
    protected $signature = 'attendance:sync
        {--device= : ID of a specific device (omit to sync all active devices)}
        {--date=   : Only process logs for this date (YYYY-MM-DD, omit for all pending)}';

    protected $description = 'Pull attendance logs from ZKTeco devices and push to timesheets';

    public function handle(AttendanceDeviceService $service): int
    {
        $deviceId = $this->option('device');
        $date     = $this->option('date');

        $query = AttendanceDevice::where('is_active', true);

        if ($deviceId) {
            $query->where('id', $deviceId);
        }

        $devices = $query->get();

        if ($devices->isEmpty()) {
            $this->warn('No active attendance devices found.');
            return self::SUCCESS;
        }

        $totalNew = 0;

        foreach ($devices as $device) {
            $this->info("Syncing [{$device->name}] at {$device->ip}:{$device->port} …");

            try {
                $result    = $service->sync($device);
                $totalNew += $result['new'];
                $this->line("  ✓ {$result['new']} new logs (total on device: {$result['total']})");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$device->name}: {$e->getMessage()}");
            }
        }

        $timesheets = $service->processToTimesheets($date ?: null);
        $this->info("Processed {$totalNew} new logs → {$timesheets} timesheet rows updated.");

        return self::SUCCESS;
    }
}
