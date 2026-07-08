<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\TreatmentPlan;
use App\Services\CareRuleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunCareReminders extends Command
{
    protected $signature   = 'dental:care-reminders {--date= : Override today\'s date (YYYY-MM-DD)}';
    protected $description = 'Evaluate care rules and send due reminder messages';

    public function __construct(private CareRuleService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        $this->info("Running care reminders for: {$today->toDateString()}");

        $totalSent = 0;

        // appointment_completed: appointments that completed delay_days ago
        Appointment::where('status', 'completed')
            ->with('patient')
            ->whereNotNull('patient_id')
            ->get()
            ->each(function ($appt) use ($today, &$totalSent) {
                $sent = $this->service->evaluateForPatient(
                    $appt->patient,
                    'appointment_completed',
                    Carbon::parse($appt->scheduled_at),
                    $appt->service_id,
                );
                $totalSent += $sent;
            });

        // treatment_completed: treatment plans that completed delay_days ago
        TreatmentPlan::where('status', 'completed')
            ->with('patient')
            ->whereNotNull('patient_id')
            ->get()
            ->each(function ($plan) use ($today, &$totalSent) {
                $sent = $this->service->evaluateForPatient(
                    $plan->patient,
                    'treatment_completed',
                    Carbon::parse($plan->updated_at),
                );
                $totalSent += $sent;
            });

        $this->info("Done. Messages sent: {$totalSent}");

        return self::SUCCESS;
    }
}
