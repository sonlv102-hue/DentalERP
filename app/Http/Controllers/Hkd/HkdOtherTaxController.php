<?php

namespace App\Http\Controllers\Hkd;

use App\Http\Controllers\Controller;
use App\Models\HkdOtherTax;
use App\Models\HkdProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdOtherTaxController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        $taxes = HkdOtherTax::where('hkd_profile_id', $profile->id)
            ->where('period', $period)
            ->orderBy('due_date')
            ->get()
            ->map(fn ($t) => $this->taxDto($t));

        return Inertia::render('Hkd/OtherTaxes/Index', [
            'profile'   => ['id' => $profile->id, 'full_name' => $profile->full_name],
            'taxes'     => $taxes,
            'period'    => $period,
            'is_locked' => $profile->isPeriodClosed($period),
            'totals'    => ['tax_amount' => $taxes->sum('tax_amount'), 'paid_amount' => $taxes->sum('paid_amount'), 'remaining' => $taxes->sum('remaining')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data    = $this->validated($request);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        HkdOtherTax::create([...$data, 'hkd_profile_id' => $profile->id, 'created_by' => auth()->id()]);
        return back()->with('success', 'Đã thêm thuế khác.');
    }

    public function update(Request $request, HkdOtherTax $hkdOtherTax): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data    = $this->validated($request);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        $hkdOtherTax->update($data);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(HkdOtherTax $hkdOtherTax): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();

        if ($profile->isPeriodClosed($hkdOtherTax->period)) {
            return back()->withErrors(['period' => "Kỳ {$hkdOtherTax->period} đã được chốt."]);
        }

        $hkdOtherTax->delete();
        return back()->with('success', 'Đã xoá.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period'         => 'required|regex:/^\d{4}-\d{2}$/',
            'tax_type'       => 'required|string|max:100',
            'taxable_amount' => 'nullable|integer|min:0',
            'tax_rate'       => 'nullable|numeric|min:0|max:1',
            'tax_amount'     => 'required|integer|min:0',
            'due_date'       => 'nullable|date',
            'paid_date'      => 'nullable|date',
            'paid_amount'    => 'nullable|integer|min:0',
            'notes'          => 'nullable|string',
        ]);
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }

    private function taxDto(HkdOtherTax $t): array
    {
        return ['id' => $t->id, 'period' => $t->period, 'tax_type' => $t->tax_type, 'taxable_amount' => $t->taxable_amount, 'tax_rate' => $t->tax_rate, 'tax_amount' => $t->tax_amount, 'due_date' => $t->due_date?->format('Y-m-d'), 'paid_date' => $t->paid_date?->format('Y-m-d'), 'paid_amount' => $t->paid_amount, 'remaining' => $t->remainingAmount(), 'is_paid' => $t->isPaid(), 'notes' => $t->notes];
    }
}
