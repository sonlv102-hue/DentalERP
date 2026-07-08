<?php

namespace App\Services\Hkd;

use App\Enums\HkdPeriodCloseStatus;
use App\Models\HkdExpenseEntry;
use App\Models\HkdOtherTax;
use App\Models\HkdPeriodClose;
use App\Models\HkdProfile;
use App\Models\HkdRevenueEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HkdPeriodCloseService
{
    public function __construct(private readonly HkdBookReportService $reportService) {}

    /**
     * Close a period. Generates a snapshot summary and locks all entries.
     * Throws if already closed.
     */
    public function closePeriod(HkdProfile $profile, string $period, User $user): HkdPeriodClose
    {
        if ($profile->isPeriodClosed($period)) {
            throw new \RuntimeException("Kỳ $period đã được chốt.");
        }

        $snapshot = $this->buildSnapshot($profile, $period);

        return DB::transaction(function () use ($profile, $period, $user, $snapshot) {
            $close = HkdPeriodClose::updateOrCreate(
                ['hkd_profile_id' => $profile->id, 'period' => $period],
                [
                    'status'        => HkdPeriodCloseStatus::Closed,
                    'closed_at'     => now(),
                    'closed_by'     => $user->id,
                    'snapshot_data' => $snapshot,
                    // Clear any previous unlock info
                    'unlock_reason' => null,
                    'unlocked_at'   => null,
                    'unlocked_by'   => null,
                ]
            );

            activity()->causedBy($user)->performedOn($close)
                ->withProperties(['period' => $period, 'snapshot_totals' => $snapshot['totals'] ?? []])
                ->log("Chốt kỳ kế toán HKD $period cho {$profile->full_name}");

            return $close;
        });
    }

    /**
     * Unlock a closed period. Requires explicit reason for audit trail.
     * Throws if period is not closed.
     */
    public function unlockPeriod(HkdProfile $profile, string $period, User $user, string $reason): HkdPeriodClose
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Phải nhập lý do mở khoá kỳ kế toán.');
        }

        $close = HkdPeriodClose::where('hkd_profile_id', $profile->id)
            ->where('period', $period)
            ->firstOrFail();

        if (! $close->isClosed()) {
            throw new \RuntimeException("Kỳ $period chưa được chốt.");
        }

        return DB::transaction(function () use ($close, $user, $reason) {
            $close->update([
                'status'        => HkdPeriodCloseStatus::Open,
                'unlock_reason' => $reason,
                'unlocked_at'   => now(),
                'unlocked_by'   => $user->id,
            ]);

            activity()->causedBy($user)->performedOn($close)
                ->withProperties(['reason' => $reason])
                ->log("Mở khoá kỳ kế toán HKD {$close->period}: $reason");

            return $close;
        });
    }

    /**
     * Get or create the period close record for listing purposes.
     */
    public function getOrCreate(HkdProfile $profile, string $period): HkdPeriodClose
    {
        return HkdPeriodClose::firstOrCreate(
            ['hkd_profile_id' => $profile->id, 'period' => $period],
            ['status' => HkdPeriodCloseStatus::Open]
        );
    }

    /**
     * Build a lightweight summary snapshot for the close record.
     */
    private function buildSnapshot(HkdProfile $profile, string $period): array
    {
        $books    = $this->reportService->booksForProfile($profile, $period);
        $totals   = [];

        $totalRevenue  = HkdRevenueEntry::where('hkd_profile_id', $profile->id)->where('period', $period)->sum('amount');
        $totalVat      = HkdRevenueEntry::where('hkd_profile_id', $profile->id)->where('period', $period)->sum('vat_amount');
        $totalPit      = HkdRevenueEntry::where('hkd_profile_id', $profile->id)->where('period', $period)->sum('pit_amount');
        $totalExpenses = HkdExpenseEntry::where('hkd_profile_id', $profile->id)->where('period', $period)->sum('amount');
        $totalTax      = HkdOtherTax::where('hkd_profile_id', $profile->id)->where('period', $period)->sum('tax_amount');

        foreach ($books as $book) {
            $totals[$book] = match ($book) {
                'S1a'   => ['total_revenue' => $totalRevenue],
                'S2a'   => ['total_revenue' => $totalRevenue, 'total_vat' => $totalVat, 'total_pit' => $totalPit],
                'S2b'   => ['total_revenue' => $totalRevenue, 'total_vat' => $totalVat],
                'S2c'   => ['total_revenue' => $totalRevenue, 'total_expenses' => $totalExpenses, 'taxable_income' => max(0, $totalRevenue - $totalExpenses)],
                'S2d'   => ['note' => 'Chi tiết tồn kho — xem báo cáo đầy đủ'],
                'S2e'   => ['note' => 'Chi tiết tiền — xem báo cáo đầy đủ'],
                'S3a'   => ['total_other_tax' => $totalTax],
                default => [],
            };
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'period'       => $period,
            'books'        => $books,
            'totals'       => $totals,
        ];
    }
}
