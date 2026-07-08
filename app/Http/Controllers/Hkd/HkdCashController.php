<?php

namespace App\Http\Controllers\Hkd;

use App\Enums\HkdCashAccountType;
use App\Http\Controllers\Controller;
use App\Models\HkdCashAccount;
use App\Models\HkdCashTransaction;
use App\Models\HkdProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdCashController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile  = $this->activeProfile();
        $period   = $request->input('period', now()->format('Y-m'));
        $accounts = HkdCashAccount::where('hkd_profile_id', $profile->id)->get()
            ->map(function ($acct) use ($period) {
                $txs = $acct->transactions()->where('period', $period)->orderBy('trans_date')->get();
                $balance = $acct->opening_balance;
                $rows    = [];
                foreach ($txs as $tx) {
                    $balance += $tx->isReceipt() ? $tx->amount : -$tx->amount;
                    $rows[]   = ['id' => $tx->id, 'date' => $tx->trans_date->format('d/m/Y'), 'type' => $tx->trans_type, 'description' => $tx->description, 'reference' => $tx->reference, 'receipt' => $tx->isReceipt() ? $tx->amount : null, 'payment' => $tx->isPayment() ? $tx->amount : null, 'balance' => $balance, 'source_type' => $tx->source_type];
                }
                return ['id' => $acct->id, 'name' => $acct->name, 'type' => $acct->type->value, 'type_label' => $acct->type->label(), 'bank_name' => $acct->bank_name, 'account_number' => $acct->account_number, 'opening_balance' => $acct->opening_balance, 'transactions' => $rows, 'total_receipts' => $txs->where('trans_type', 'receipt')->sum('amount'), 'total_payments' => $txs->where('trans_type', 'payment')->sum('amount'), 'closing_balance' => $balance, 'is_active' => $acct->is_active];
            });

        return Inertia::render('Hkd/Cash/Index', [
            'profile'       => ['id' => $profile->id, 'full_name' => $profile->full_name],
            'accounts'      => $accounts,
            'period'        => $period,
            'is_locked'     => $profile->isPeriodClosed($period),
            'account_types' => array_map(fn ($t) => ['value' => $t->value, 'label' => $t->label()], HkdCashAccountType::cases()),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data = $request->validate(['type' => 'required|in:cash,bank,e_wallet', 'name' => 'required|string|max:100', 'bank_name' => 'nullable|string|max:100', 'account_number' => 'nullable|string|max:30', 'opening_balance' => 'required|integer']);
        HkdCashAccount::create([...$data, 'hkd_profile_id' => $profile->id]);
        return back()->with('success', 'Đã thêm tài khoản tiền.');
    }

    public function updateAccount(Request $request, HkdCashAccount $hkdCashAccount): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate(['type' => 'required|in:cash,bank,e_wallet', 'name' => 'required|string|max:100', 'bank_name' => 'nullable|string|max:100', 'account_number' => 'nullable|string|max:30', 'opening_balance' => 'required|integer', 'is_active' => 'boolean']);
        $hkdCashAccount->update($data);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function storeTransaction(Request $request, HkdCashAccount $hkdCashAccount): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data = $request->validate(['period' => 'required|regex:/^\d{4}-\d{2}$/', 'trans_date' => 'required|date', 'trans_type' => 'required|in:receipt,payment', 'amount' => 'required|integer|min:1', 'description' => 'required|string', 'reference' => 'nullable|string|max:100']);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        HkdCashTransaction::create([...$data, 'hkd_profile_id' => $profile->id, 'account_id' => $hkdCashAccount->id, 'source_type' => 'manual', 'created_by' => auth()->id()]);
        return back()->with('success', 'Đã thêm giao dịch tiền.');
    }

    public function destroyTransaction(HkdCashTransaction $hkdCashTransaction): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();

        if ($profile->isPeriodClosed($hkdCashTransaction->period)) {
            return back()->withErrors(['period' => "Kỳ {$hkdCashTransaction->period} đã được chốt."]);
        }

        $hkdCashTransaction->delete();
        return back()->with('success', 'Đã xoá.');
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }
}
