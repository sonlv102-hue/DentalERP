<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSymbol;
use App\Services\AttendancePeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function __construct(private AttendancePeriodService $periodService) {}

    /**
     * Update a single attendance cell (AJAX).
     */
    public function update(AttendancePeriod $attendance, AttendanceRecord $record, Request $request): JsonResponse
    {
        $this->authorize('employees.manage');

        if ($attendance->isLocked()) {
            return response()->json(['message' => 'Kỳ chấm công đã khóa, không thể sửa.'], 403);
        }

        if ($record->attendance_period_id !== $attendance->id) {
            return response()->json(['message' => 'Record không thuộc kỳ này.'], 422);
        }

        $data = $request->validate([
            'symbol'         => 'nullable|string|max:10',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'check_in_time'  => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'note'           => 'nullable|string|max:500',
        ]);

        // Resolve paid_workday from symbol definition
        $sym = $data['symbol'] ? AttendanceSymbol::where('code', $data['symbol'])->first() : null;
        $paidWorkday   = $sym ? $sym->default_paid_workday : 0;
        $unpaidWorkday = ($sym && $sym->counts_as_unpaid_leave) ? 1 : 0;

        $old = $record->only(['symbol', 'overtime_hours', 'check_in_time', 'check_out_time', 'note']);

        $record->update([
            'symbol'         => $data['symbol'] ?? null,
            'overtime_hours' => $data['overtime_hours'] ?? 0,
            'check_in_time'  => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'note'           => $data['note'] ?? null,
            'paid_workday'   => $paidWorkday,
            'unpaid_workday' => $unpaidWorkday,
            'updated_by'     => auth()->id(),
        ]);

        $new = $record->fresh()->only(['symbol', 'overtime_hours', 'check_in_time', 'check_out_time', 'note']);

        $this->periodService->logRecordChange($attendance, $record, $old, $new, auth()->id());

        return response()->json([
            'symbol'        => $record->symbol,
            'display'       => $record->displaySymbol(),
            'overtime_hours'=> $record->overtime_hours,
            'paid_workday'  => $record->paid_workday,
            'note'          => $record->note,
        ]);
    }
}
