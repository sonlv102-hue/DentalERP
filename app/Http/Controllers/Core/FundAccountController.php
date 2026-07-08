<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\FundAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FundAccountController extends Controller
{
    public function index(): Response
    {
        $this->authorize('settings.manage');

        $accounts = FundAccount::orderBy('type')->orderBy('name')->get()
            ->map(fn ($a) => [
                'id'              => $a->id,
                'name'            => $a->name,
                'type'            => $a->type,
                'type_label'      => $a->typeLabel(),
                'initial_balance' => $a->initial_balance,
                'current_balance' => $a->currentBalance(),
                'bank_name'       => $a->bank_name,
                'account_number'  => $a->account_number,
                'is_active'       => $a->is_active,
            ]);

        return Inertia::render('Core/FundAccounts/Index', ['accounts' => $accounts]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:cash,bank,ewallet',
            'initial_balance'=> 'required|integer|min:0',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
        ]);

        FundAccount::create($data);

        return back()->with('success', 'Đã thêm nguồn quỹ.');
    }

    public function update(Request $request, FundAccount $fundAccount): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:cash,bank,ewallet',
            'initial_balance'=> 'required|integer|min:0',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'is_active'      => 'boolean',
        ]);

        $fundAccount->update($data);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(FundAccount $fundAccount): RedirectResponse
    {
        $this->authorize('settings.manage');

        $fundAccount->update(['is_active' => false]);

        return back()->with('success', 'Đã vô hiệu hóa nguồn quỹ.');
    }
}
