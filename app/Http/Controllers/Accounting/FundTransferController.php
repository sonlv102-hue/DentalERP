<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FundAccount;
use App\Models\FundTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FundTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('accounting.view');

        $transfers = FundTransfer::with(['fromAccount', 'toAccount', 'creator'])
            ->orderByDesc('transfer_date')
            ->paginate(30)
            ->through(fn ($t) => [
                'id'           => $t->id,
                'from_account' => $t->fromAccount->name,
                'to_account'   => $t->toAccount->name,
                'amount'       => $t->amount,
                'transfer_date'=> $t->transfer_date->format('d/m/Y'),
                'description'  => $t->description,
                'reference'    => $t->reference,
                'created_by'   => $t->creator?->name,
            ]);

        return Inertia::render('Accounting/FundTransfers/Index', [
            'transfers'    => $transfers,
            'fundAccounts' => FundAccount::where('is_active', true)->get()->map(fn ($a) => [
                'id'      => $a->id,
                'name'    => $a->name,
                'balance' => $a->currentBalance(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'from_account_id' => 'required|exists:fund_accounts,id',
            'to_account_id'   => 'required|exists:fund_accounts,id|different:from_account_id',
            'amount'          => 'required|integer|min:1',
            'transfer_date'   => 'required|date',
            'description'     => 'nullable|string|max:500',
            'reference'       => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data) {
            FundTransfer::create([...$data, 'created_by' => auth()->id()]);
        });

        return back()->with('success', 'Đã chuyển quỹ thành công.');
    }

    public function destroy(FundTransfer $fundTransfer): RedirectResponse
    {
        $this->authorize('accounting.manage');
        $fundTransfer->delete();
        return back()->with('success', 'Đã xóa phiếu chuyển quỹ.');
    }
}
