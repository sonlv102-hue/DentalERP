<?php

namespace App\Services\Hkd;

use App\Enums\HkdExpenseCategory;
use App\Enums\HkdRevenueCategory;
use App\Models\Expense;
use App\Models\FixedAssetDepreciation;
use App\Models\HkdCashAccount;
use App\Models\HkdCashTransaction;
use App\Models\HkdExpenseEntry;
use App\Models\HkdProfile;
use App\Models\HkdRevenueEntry;
use App\Models\InventoryItem;
use App\Models\PatientPayment;
use App\Models\Payroll;
use App\Models\TreatmentStepExecution;
use Illuminate\Support\Facades\DB;

/**
 * Tự động ghi sổ kế toán TT152 (HKD) từ các module nghiệp vụ.
 * Idempotent: mỗi source_type + source_id chỉ ghi một lần.
 * Bỏ qua nếu chi nhánh chưa có HkdProfile kích hoạt.
 */
class HkdJournalService
{
    // ─── PatientPayment → Sổ doanh thu + Sổ tiền ────────────────────────────

    public function postRevenue(PatientPayment $payment): ?HkdRevenueEntry
    {
        $invoice = $payment->invoice()->with(['patient', 'branch'])->first();
        if (!$invoice) {
            return null;
        }

        $profile = $this->profileFor($invoice->branch_id);
        if (!$profile) {
            return null;
        }

        $period = $payment->payment_date->format('Y-m');

        if ($this->revenueExists('patient_payment', $payment->id)) {
            return null;
        }

        return DB::transaction(function () use ($payment, $invoice, $profile, $period) {
            $entry = HkdRevenueEntry::create([
                'hkd_profile_id'   => $profile->id,
                'period'           => $period,
                'entry_date'       => $payment->payment_date,
                'document_no'      => $invoice->code,
                'buyer_name'       => $invoice->patient?->full_name ?? '',
                'description'      => 'DV nha khoa — ' . $invoice->code,
                'revenue_category' => HkdRevenueCategory::Services,
                'amount'           => $payment->amount,
                'vat_amount'       => 0,
                'pit_amount'       => 0,
                'source_type'      => 'patient_payment',
                'source_id'        => $payment->id,
                'created_by'       => $payment->created_by,
            ]);

            $this->postCash(
                $profile, $period, $payment->payment_date, 'receipt',
                $payment->amount, 'Thu tiền DV nha khoa — ' . $invoice->code,
                $invoice->code, 'patient_payment', $payment->id, $payment->created_by
            );

            return $entry;
        });
    }

    // ─── Expense → Sổ chi phí + Sổ tiền ─────────────────────────────────────

    public function postExpense(Expense $expense): ?HkdExpenseEntry
    {
        $profile = $this->profileFor($expense->branch_id);
        if (!$profile) {
            return null;
        }

        $period = $expense->expense_date->format('Y-m');

        if ($this->expenseExists('expense', $expense->id)) {
            return null;
        }

        $docNo = 'PC-' . str_pad($expense->id, 5, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($expense, $profile, $period, $docNo) {
            $entry = HkdExpenseEntry::create([
                'hkd_profile_id' => $profile->id,
                'period'         => $period,
                'entry_date'     => $expense->expense_date,
                'document_no'    => $docNo,
                'category'       => $this->mapExpenseCategory($expense->category),
                'description'    => $expense->description,
                'amount'         => $expense->amount,
                'source_type'    => 'expense',
                'source_id'      => $expense->id,
                'created_by'     => $expense->created_by,
            ]);

            $this->postCash(
                $profile, $period, $expense->expense_date, 'payment',
                $expense->amount, 'Chi phí — ' . $expense->description,
                $docNo, 'expense', $expense->id, $expense->created_by
            );

            return $entry;
        });
    }

    // ─── Payroll confirm → Sổ chi phí lương ──────────────────────────────────

    public function postSalary(Payroll $payroll): ?HkdExpenseEntry
    {
        $profile = $this->profileFor($payroll->branch_id);
        if (!$profile) {
            return null;
        }

        if ($this->expenseExists('payroll', $payroll->id)) {
            return null;
        }

        $period = sprintf('%04d-%02d', $payroll->year, $payroll->month);

        return HkdExpenseEntry::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $period,
            'entry_date'     => $payroll->confirmed_at ?? now(),
            'document_no'    => $payroll->code,
            'category'       => HkdExpenseCategory::Labor,
            'description'    => 'Chi phí lương ' . $payroll->period_label(),
            'amount'         => $payroll->total_net_salary,
            'source_type'    => 'payroll',
            'source_id'      => $payroll->id,
            'created_by'     => $payroll->confirmed_by ?? $payroll->created_by,
        ]);
    }

    // ─── FixedAssetDepreciation → Sổ chi phí khấu hao ───────────────────────

    public function postDepreciation(FixedAssetDepreciation $dep): ?HkdExpenseEntry
    {
        $asset = $dep->asset()->first();
        if (!$asset) {
            return null;
        }

        $profile = $this->profileFor($asset->branch_id);
        if (!$profile) {
            return null;
        }

        if ($this->expenseExists('depreciation', $dep->id)) {
            return null;
        }

        return HkdExpenseEntry::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $dep->period,
            'entry_date'     => $dep->period . '-01',
            'document_no'    => 'KH-' . str_pad($dep->id, 5, '0', STR_PAD_LEFT),
            'category'       => HkdExpenseCategory::Depreciation,
            'description'    => 'Khấu hao TSCĐ — ' . $asset->name . ' (' . $dep->period . ')',
            'amount'         => $dep->amount,
            'source_type'    => 'depreciation',
            'source_id'      => $dep->id,
            'created_by'     => auth()->id(),
        ]);
    }

    // ─── Inventory usage → Sổ chi phí vật tư ────────────────────────────────

    public function postMaterialUsage(TreatmentStepExecution $execution, InventoryItem $item, int $amount): ?HkdExpenseEntry
    {
        $branchId = $execution->planItem?->plan?->branch_id;
        $profile  = $this->profileFor($branchId);
        if (!$profile) {
            return null;
        }

        $sourceKey = 'inv_execution_' . $execution->id . '_' . $item->id;
        if ($this->expenseExists('inventory_usage', (int) ($execution->id * 10000 + $item->id))) {
            return null;
        }

        return HkdExpenseEntry::create([
            'hkd_profile_id' => $profile->id,
            'period'         => now()->format('Y-m'),
            'entry_date'     => today()->toDateString(),
            'document_no'    => 'VT-' . str_pad($execution->id, 5, '0', STR_PAD_LEFT),
            'category'       => HkdExpenseCategory::Materials,
            'description'    => 'Vật tư ' . $item->name . ' — công đoạn #' . $execution->id,
            'amount'         => $amount,
            'source_type'    => 'inventory_usage',
            'source_id'      => $execution->id * 10000 + $item->id,
            'created_by'     => auth()->id(),
        ]);
    }

    // ─── Idempotency helpers ─────────────────────────────────────────────────

    private function revenueExists(string $sourceType, int $sourceId): bool
    {
        return HkdRevenueEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    private function expenseExists(string $sourceType, int $sourceId): bool
    {
        return HkdExpenseEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    // ─── Sổ tiền ─────────────────────────────────────────────────────────────

    private function postCash(
        HkdProfile $profile,
        string $period,
        mixed $date,
        string $transType,
        int $amount,
        string $description,
        string $reference,
        string $sourceType,
        int $sourceId,
        ?int $createdBy
    ): void {
        $account = HkdCashAccount::where('hkd_profile_id', $profile->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return;
        }

        if (HkdCashTransaction::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('trans_type', $transType)
            ->exists()) {
            return;
        }

        HkdCashTransaction::create([
            'hkd_profile_id' => $profile->id,
            'account_id'     => $account->id,
            'period'         => $period,
            'trans_date'     => $date,
            'trans_type'     => $transType,
            'amount'         => abs($amount),
            'description'    => $description,
            'reference'      => $reference,
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'created_by'     => $createdBy,
        ]);
    }

    // ─── Profile lookup ───────────────────────────────────────────────────────

    private function profileFor(?int $branchId): ?HkdProfile
    {
        if (!$branchId) {
            return null;
        }

        return HkdProfile::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    private function mapExpenseCategory(mixed $category): HkdExpenseCategory
    {
        $val = $category instanceof \BackedEnum ? $category->value : (string) $category;

        return match ($val) {
            'rent'      => HkdExpenseCategory::Rent,
            'utilities' => HkdExpenseCategory::Utilities,
            'supplies'  => HkdExpenseCategory::Materials,
            'equipment' => HkdExpenseCategory::Materials,
            'salary'    => HkdExpenseCategory::Labor,
            default     => HkdExpenseCategory::Other,
        };
    }
}
