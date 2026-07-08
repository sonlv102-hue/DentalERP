<?php

namespace App\Services\Hkd;

use App\Models\HkdProfile;

class HkdBookReportService
{
    /** Returns which sổ kế toán this profile needs for a given period. */
    public function booksForProfile(HkdProfile $profile, string $period): array
    {
        $books = $profile->tax_status->books(); // S1a | S2a | [S2b,S2c,S2d,S2e]
        if ($profile->otherTaxes()->where('period', $period)->exists()) {
            $books[] = 'S3a';
        }
        return $books;
    }

    /** S1a — Sổ doanh thu (không kê khai VAT & TNCN) */
    public function generateS1a(HkdProfile $profile, string $period): array
    {
        $entries = $profile->revenueEntries()->where('period', $period)->orderBy('entry_date')->get();
        return [
            'book'    => 'S1a',
            'period'  => $period,
            'profile' => $this->profileDto($profile),
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id, 'date' => $e->entry_date->format('d/m/Y'),
                'document_no' => $e->document_no, 'buyer_name' => $e->buyer_name,
                'description' => $e->description, 'amount' => $e->amount, 'notes' => $e->notes,
            ])->all(),
            'total' => $entries->sum('amount'),
        ];
    }

    /** S2a — Doanh thu với VAT + TNCN theo % doanh thu */
    public function generateS2a(HkdProfile $profile, string $period): array
    {
        $entries = $profile->revenueEntries()->where('period', $period)->orderBy('entry_date')->get();
        return [
            'book'    => 'S2a',
            'period'  => $period,
            'profile' => $this->profileDto($profile),
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id, 'date' => $e->entry_date->format('d/m/Y'),
                'document_no' => $e->document_no, 'buyer_name' => $e->buyer_name,
                'buyer_tax_code' => $e->buyer_tax_code, 'description' => $e->description,
                'revenue_category' => $e->revenue_category?->label(),
                'amount' => $e->amount, 'vat_amount' => $e->vat_amount, 'pit_amount' => $e->pit_amount,
            ])->all(),
            'totals' => [
                'amount'     => $entries->sum('amount'),
                'vat_amount' => $entries->sum('vat_amount'),
                'pit_amount' => $entries->sum('pit_amount'),
            ],
        ];
    }

    /** S2b — Doanh thu với VAT (TNCN tính trên thu nhập chịu thuế — xem S2c) */
    public function generateS2b(HkdProfile $profile, string $period): array
    {
        $entries = $profile->revenueEntries()->where('period', $period)->orderBy('entry_date')->get();
        return [
            'book'    => 'S2b',
            'period'  => $period,
            'profile' => $this->profileDto($profile),
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id, 'date' => $e->entry_date->format('d/m/Y'),
                'document_no' => $e->document_no, 'buyer_name' => $e->buyer_name,
                'buyer_tax_code' => $e->buyer_tax_code, 'description' => $e->description,
                'amount' => $e->amount, 'vat_amount' => $e->vat_amount,
            ])->all(),
            'totals' => ['amount' => $entries->sum('amount'), 'vat_amount' => $entries->sum('vat_amount')],
        ];
    }

    /** S2c — Chi tiết doanh thu và chi phí (thu nhập chịu thuế TNCN) */
    public function generateS2c(HkdProfile $profile, string $period): array
    {
        $revenue  = $profile->revenueEntries()->where('period', $period)->orderBy('entry_date')->get();
        $expenses = $profile->expenseEntries()->where('period', $period)->orderBy('entry_date')->get();
        $totalRev = $revenue->sum('amount');
        $totalExp = $expenses->sum('amount');
        return [
            'book'            => 'S2c',
            'period'          => $period,
            'profile'         => $this->profileDto($profile),
            'revenue_entries' => $revenue->map(fn ($e) => ['date' => $e->entry_date->format('d/m/Y'), 'document_no' => $e->document_no, 'description' => $e->description, 'amount' => $e->amount])->all(),
            'expense_entries' => $expenses->map(fn ($e) => ['date' => $e->entry_date->format('d/m/Y'), 'document_no' => $e->document_no, 'description' => $e->description, 'category' => $e->category?->label(), 'amount' => $e->amount])->all(),
            'totals'          => ['revenue' => $totalRev, 'expenses' => $totalExp, 'taxable_income' => max(0, $totalRev - $totalExp)],
        ];
    }

    /** S2d — Chi tiết hàng hóa (weighted average costing) */
    public function generateS2d(HkdProfile $profile, string $period): array
    {
        $rows = [];
        foreach ($profile->inventoryItems()->where('is_active', true)->get() as $item) {
            $qty   = (float) $item->opening_qty;
            $value = $qty * $item->opening_unit_cost;
            $avg   = $qty > 0 ? $value / $qty : 0;
            $txRows = [];
            [$totalImpQty, $totalImpVal, $totalExpQty, $totalExpVal] = [0, 0, 0, 0];

            foreach ($item->transactionsForPeriod($period)->get() as $tx) {
                if ($tx->isImport()) {
                    $qty += (float) $tx->qty; $value += $tx->amount;
                    $avg  = $qty > 0 ? $value / $qty : 0;
                    $totalImpQty += (float) $tx->qty; $totalImpVal += $tx->amount;
                    $txRows[] = ['type' => 'import', 'date' => $tx->trans_date->format('d/m/Y'), 'document_no' => $tx->document_no, 'counterpart' => $tx->counterpart, 'qty' => (float) $tx->qty, 'unit_cost' => $tx->unit_cost, 'amount' => $tx->amount, 'running_qty' => round($qty, 3), 'avg_cost' => (int) round($avg)];
                } else {
                    $amt   = (int) round((float) $tx->qty * $avg);
                    $qty  -= (float) $tx->qty; $value -= $amt;
                    $totalExpQty += (float) $tx->qty; $totalExpVal += $amt;
                    $txRows[] = ['type' => 'export', 'date' => $tx->trans_date->format('d/m/Y'), 'document_no' => $tx->document_no, 'counterpart' => $tx->counterpart, 'qty' => (float) $tx->qty, 'unit_cost' => (int) round($avg), 'amount' => $amt, 'running_qty' => round($qty, 3), 'avg_cost' => (int) round($avg)];
                }
            }
            $rows[] = [
                'item'         => ['id' => $item->id, 'code' => $item->code, 'name' => $item->name, 'unit' => $item->unit],
                'opening'      => ['qty' => (float) $item->opening_qty, 'unit_cost' => $item->opening_unit_cost, 'value' => (int) ((float) $item->opening_qty * $item->opening_unit_cost)],
                'transactions' => $txRows,
                'import_total' => ['qty' => $totalImpQty, 'value' => $totalImpVal],
                'export_total' => ['qty' => $totalExpQty, 'value' => $totalExpVal],
                'closing'      => ['qty' => round($qty, 3), 'unit_cost' => (int) round($avg), 'value' => (int) $value],
            ];
        }
        return ['book' => 'S2d', 'period' => $period, 'profile' => $this->profileDto($profile), 'items' => $rows];
    }

    /** S2e — Chi tiết tiền (per cash account) */
    public function generateS2e(HkdProfile $profile, string $period): array
    {
        $accounts = [];
        foreach ($profile->cashAccounts()->where('is_active', true)->get() as $acct) {
            $balance = $acct->opening_balance;
            $txRows  = []; $totalRec = 0; $totalPay = 0;
            foreach ($acct->transactions()->where('period', $period)->orderBy('trans_date')->get() as $tx) {
                if ($tx->isReceipt()) { $balance += $tx->amount; $totalRec += $tx->amount; } else { $balance -= $tx->amount; $totalPay += $tx->amount; }
                $txRows[] = ['date' => $tx->trans_date->format('d/m/Y'), 'type' => $tx->trans_type, 'description' => $tx->description, 'reference' => $tx->reference, 'receipt' => $tx->isReceipt() ? $tx->amount : null, 'payment' => $tx->isPayment() ? $tx->amount : null, 'balance' => $balance];
            }
            $accounts[] = ['account' => ['id' => $acct->id, 'name' => $acct->name, 'type' => $acct->type->label(), 'bank_name' => $acct->bank_name, 'account_number' => $acct->account_number], 'opening_balance' => $acct->opening_balance, 'transactions' => $txRows, 'total_receipts' => $totalRec, 'total_payments' => $totalPay, 'closing_balance' => $balance];
        }
        return ['book' => 'S2e', 'period' => $period, 'profile' => $this->profileDto($profile), 'accounts' => $accounts];
    }

    /** S3a — Thuế khác */
    public function generateS3a(HkdProfile $profile, string $period): array
    {
        $taxes = $profile->otherTaxes()->where('period', $period)->orderBy('due_date')->get();
        return [
            'book'    => 'S3a',
            'period'  => $period,
            'profile' => $this->profileDto($profile),
            'taxes'   => $taxes->map(fn ($t) => ['id' => $t->id, 'tax_type' => $t->tax_type, 'taxable_amount' => $t->taxable_amount, 'tax_rate' => $t->tax_rate, 'tax_amount' => $t->tax_amount, 'due_date' => $t->due_date?->format('d/m/Y'), 'paid_date' => $t->paid_date?->format('d/m/Y'), 'paid_amount' => $t->paid_amount, 'remaining' => $t->remainingAmount(), 'is_paid' => $t->isPaid(), 'notes' => $t->notes])->all(),
            'totals'  => ['tax_amount' => $taxes->sum('tax_amount'), 'paid_amount' => $taxes->sum('paid_amount'), 'remaining' => $taxes->sum(fn ($t) => $t->remainingAmount())],
        ];
    }

    public function generate(HkdProfile $profile, string $book, string $period): array
    {
        return match ($book) {
            'S1a' => $this->generateS1a($profile, $period),
            'S2a' => $this->generateS2a($profile, $period),
            'S2b' => $this->generateS2b($profile, $period),
            'S2c' => $this->generateS2c($profile, $period),
            'S2d' => $this->generateS2d($profile, $period),
            'S2e' => $this->generateS2e($profile, $period),
            'S3a' => $this->generateS3a($profile, $period),
            default => throw new \InvalidArgumentException("Không hỗ trợ sổ $book"),
        };
    }

    private function profileDto(HkdProfile $profile): array
    {
        return ['id' => $profile->id, 'full_name' => $profile->full_name, 'tax_code' => $profile->tax_code, 'address' => $profile->address, 'representative' => $profile->representative_name, 'tax_status' => $profile->tax_status->label(), 'tax_status_value' => $profile->tax_status->value];
    }
}
