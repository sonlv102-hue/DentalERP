<?php

namespace App\Services;

use App\Models\CareRule;
use App\Models\MessageLog;
use App\Models\Patient;
use Carbon\Carbon;

class CareRuleService
{
    public function __construct(private MessageService $messageService) {}

    /**
     * Evaluate care rules for a specific trigger event and fire due reminders.
     * Called by the RunCareReminders artisan command daily.
     *
     * @param string $event  appointment_completed | treatment_completed
     * @param Carbon $eventDate  The date the event occurred
     */
    public function evaluateForPatient(Patient $patient, string $event, Carbon $eventDate, ?int $serviceId = null): int
    {
        $sent = 0;
        $today = now()->startOfDay();

        $rules = CareRule::where('is_active', true)
            ->where('trigger_event', $event)
            ->when($serviceId, fn ($q) => $q->where(fn ($q) =>
                $q->whereNull('trigger_service_id')->orWhere('trigger_service_id', $serviceId)
            ), fn ($q) => $q->whereNull('trigger_service_id'))
            ->with('messageTemplate')
            ->get();

        foreach ($rules as $rule) {
            $dueDate = $eventDate->copy()->addDays($rule->delay_days)->startOfDay();

            if (!$dueDate->eq($today)) {
                continue;
            }

            // Skip if already sent today for same rule+patient
            $alreadySent = MessageLog::where('patient_id', $patient->id)
                ->where('template_id', $rule->message_template_id)
                ->whereDate('sent_at', $today)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $this->messageService->send($patient, $rule->messageTemplate);
            $sent++;
        }

        return $sent;
    }
}
