<?php

namespace App\Http\Controllers\Hkd;

use App\Http\Controllers\Controller;
use App\Models\HkdInventoryItem;
use App\Models\HkdInventoryTransaction;
use App\Models\HkdProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        $items = HkdInventoryItem::where('hkd_profile_id', $profile->id)->get()
            ->map(fn ($item) => $this->itemDto($item, $period));

        return Inertia::render('Hkd/Inventory/Index', [
            'profile'   => ['id' => $profile->id, 'full_name' => $profile->full_name],
            'items'     => $items,
            'period'    => $period,
            'is_locked' => $profile->isPeriodClosed($period),
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data = $request->validate(['code' => 'nullable|string|max:30', 'name' => 'required|string|max:200', 'unit' => 'required|string|max:20', 'opening_qty' => 'required|numeric|min:0', 'opening_unit_cost' => 'required|integer|min:0']);
        HkdInventoryItem::create([...$data, 'hkd_profile_id' => $profile->id]);
        return back()->with('success', 'Đã thêm hàng hóa.');
    }

    public function updateItem(Request $request, HkdInventoryItem $hkdInventoryItem): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate(['code' => 'nullable|string|max:30', 'name' => 'required|string|max:200', 'unit' => 'required|string|max:20', 'opening_qty' => 'required|numeric|min:0', 'opening_unit_cost' => 'required|integer|min:0', 'is_active' => 'boolean']);
        $hkdInventoryItem->update($data);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function storeTransaction(Request $request, HkdInventoryItem $hkdInventoryItem): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data = $request->validate(['period' => 'required|regex:/^\d{4}-\d{2}$/', 'trans_date' => 'required|date', 'trans_type' => 'required|in:import,export', 'qty' => 'required|numeric|min:0.001', 'unit_cost' => 'required|integer|min:0', 'document_no' => 'nullable|string|max:50', 'counterpart' => 'nullable|string|max:200', 'notes' => 'nullable|string']);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        $amount = (int) round((float) $data['qty'] * $data['unit_cost']);
        HkdInventoryTransaction::create([...$data, 'hkd_profile_id' => $profile->id, 'item_id' => $hkdInventoryItem->id, 'amount' => $amount, 'created_by' => auth()->id()]);
        return back()->with('success', 'Đã thêm nhập/xuất kho.');
    }

    public function destroyTransaction(HkdInventoryTransaction $hkdInventoryTransaction): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();

        if ($profile->isPeriodClosed($hkdInventoryTransaction->period)) {
            return back()->withErrors(['period' => "Kỳ {$hkdInventoryTransaction->period} đã được chốt."]);
        }

        $hkdInventoryTransaction->delete();
        return back()->with('success', 'Đã xoá.');
    }

    private function itemDto(HkdInventoryItem $item, string $period): array
    {
        $txCount = $item->transactionsForPeriod($period)->count();
        return ['id' => $item->id, 'code' => $item->code, 'name' => $item->name, 'unit' => $item->unit, 'opening_qty' => (float) $item->opening_qty, 'opening_unit_cost' => $item->opening_unit_cost, 'is_active' => $item->is_active, 'tx_count_period' => $txCount];
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }
}
