<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSymbol;

class AttendanceSummaryService
{
    private array $symbolMap = [];

    public function __construct()
    {
        $this->symbolMap = AttendanceSymbol::activeMap();
    }

    /**
     * Compute per-employee totals for a period.
     * Returns: [ employee_id => ['cong'=>N, 'nghi_hl'=>N, 'nghi_kl'=>N, 'ot_hours'=>N, 'total'=>N] ]
     */
    public function summarize(AttendancePeriod $period): array
    {
        $records = AttendanceRecord::where('attendance_period_id', $period->id)->get();

        $totals = [];

        foreach ($records as $rec) {
            $eid = $rec->employee_id;
            if (! isset($totals[$eid])) {
                $totals[$eid] = ['cong' => 0.0, 'nghi_hl' => 0.0, 'nghi_kl' => 0.0, 'ot_hours' => 0.0, 'total' => 0.0];
            }

            $sym = $this->symbolMap[$rec->symbol] ?? null;
            if (! $rec->symbol || ! $sym) {
                continue;
            }

            if ($sym['is_overtime']) {
                $totals[$eid]['ot_hours'] += $rec->overtime_hours;
            } elseif ($sym['is_unpaid_leave']) {
                $totals[$eid]['nghi_kl'] += 1;
            } elseif ($sym['paid_workday'] > 0) {
                $paid = $rec->paid_workday ?: $sym['paid_workday'];
                // Distinguish workday vs paid leave
                if (in_array($rec->symbol, ['X', 'CT'])) {
                    $totals[$eid]['cong'] += $paid;
                } else {
                    $totals[$eid]['nghi_hl'] += $paid;
                }
            }
        }

        // Compute total = cong + nghi_hl (OT not included in workdays by default)
        foreach ($totals as &$t) {
            $t['total'] = $t['cong'] + $t['nghi_hl'];
            // Round to 1 decimal
            foreach ($t as &$v) {
                $v = round($v, 1);
            }
        }
        unset($t);

        return $totals;
    }

    /**
     * Build grid matrix: [employee_id][YYYY-MM-DD] = symbol for display
     */
    public function buildGrid(AttendancePeriod $period): array
    {
        $records = AttendanceRecord::where('attendance_period_id', $period->id)->get();

        $grid = [];
        foreach ($records as $rec) {
            $key = $rec->work_date->format('Y-m-d');
            $grid[$rec->employee_id][$key] = [
                'symbol'        => $rec->symbol,
                'display'       => $rec->displaySymbol(),
                'overtime_hours'=> $rec->overtime_hours,
                'check_in'      => $rec->check_in_time,
                'check_out'     => $rec->check_out_time,
                'working_hours' => $rec->working_hours,
                'note'          => $rec->note,
                'record_id'     => $rec->id,
            ];
        }

        return $grid;
    }
}
