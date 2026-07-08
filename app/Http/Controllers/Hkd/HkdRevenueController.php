<?php

namespace App\Http\Controllers\Hkd;

use App\Enums\HkdRevenueCategory;
use App\Http\Controllers\Controller;
use App\Models\HkdProfile;
use App\Models\HkdRevenueEntry;
use App\Models\PatientInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdRevenueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        $entries = HkdRevenueEntry::where('hkd_profile_id', $profile->id)
            ->where('period', $period)
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($e) => $this->entryDto($e));

        return Inertia::render('Hkd/Revenue/Index', [
            'profile'    => ['id' => $profile->id, 'full_name' => $profile->full_name, 'tax_status' => $profile->tax_status->value],
            'entries'    => $entries,
            'period'     => $period,
            'is_locked'  => $profile->isPeriodClosed($period),
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], HkdRevenueCategory::cases()),
            'totals'     => ['amount' => $entries->sum('amount'), 'vat_amount' => $entries->sum('vat_amount'), 'pit_amount' => $entries->sum('pit_amount')],
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

        [$vat, $pit] = $this->calcTax($data['amount'], $data['revenue_category'], $data['entry_date'], $profile->tax_status->value);

        HkdRevenueEntry::create([...$data, 'hkd_profile_id' => $profile->id, 'vat_amount' => $vat, 'pit_amount' => $pit, 'created_by' => auth()->id()]);
        return back()->with('success', 'Đã thêm doanh thu.');
    }

    public function update(Request $request, HkdRevenueEntry $hkdRevenue): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data    = $this->validated($request);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        [$vat, $pit] = $this->calcTax($data['amount'], $data['revenue_category'], $data['entry_date'], $profile->tax_status->value);
        $hkdRevenue->update([...$data, 'vat_amount' => $vat, 'pit_amount' => $pit]);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(HkdRevenueEntry $hkdRevenue): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();

        if ($profile->isPeriodClosed($hkdRevenue->period)) {
            return back()->withErrors(['period' => "Kỳ {$hkdRevenue->period} đã được chốt."]);
        }

        $hkdRevenue->delete();
        return back()->with('success', 'Đã xoá.');
    }

    /** Auto-import paid patient_invoices for the period as revenue entries. */
    public function importFromInvoices(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        if ($profile->isPeriodClosed($period)) {
            return back()->withErrors(['period' => "Kỳ $period đã được chốt."]);
        }

        [$year, $month] = explode('-', $period);
        $invoices = PatientInvoice::where('status', 'paid')
            ->whereYear('updated_at', $year)->whereMonth('updated_at', $month)
            ->whereNotExists(fn ($q) => $q->from('hkd_revenue_entries')
                ->where('hkd_profile_id', $profile->id)->whereColumn('source_id', 'patient_invoices.id')->where('source_type', 'invoice'))
            ->get();

        $imported = 0;
        foreach ($invoices as $inv) {
            HkdRevenueEntry::create(['hkd_profile_id' => $profile->id, 'period' => $period, 'entry_date' => $inv->updated_at->toDateString(), 'description' => "Doanh thu dịch vụ nha khoa - {$inv->code}", 'revenue_category' => HkdRevenueCategory::Services->value, 'amount' => $inv->total, 'vat_amount' => 0, 'pit_amount' => 0, 'source_type' => 'invoice', 'source_id' => $inv->id, 'created_by' => auth()->id()]);
            $imported++;
        }

        return back()->with('success', "Đã nhập $imported hóa đơn.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period'           => 'required|regex:/^\d{4}-\d{2}$/',
            'entry_date'       => 'required|date',
            'document_no'      => 'nullable|string|max:50',
            'buyer_name'       => 'nullable|string|max:200',
            'buyer_tax_code'   => 'nullable|string|max:20',
            'description'      => 'required|string',
            'revenue_category' => 'required|in:' . implode(',', array_column(HkdRevenueCategory::cases(), 'value')),
            'amount'           => 'required|integer|min:0',
            'notes'            => 'nullable|string',
        ]);
    }

    private function calcTax(int $amount, string $category, string $date, string $taxStatus): array
    {
        if ($taxStatus === 'not_subject') {
            return [0, 0];
        }
        $rate = \App\Models\HkdTaxRate::rateFor($category, \Carbon\Carbon::parse($date));
        if (! $rate) return [0, 0];
        $vat = (int) round($amount * (float) $rate->vat_rate);
        $pit = $taxStatus === 'vat_pit_revenue' ? (int) round($amount * (float) $rate->pit_rate) : 0;
        return [$vat, $pit];
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }

    private function entryDto(HkdRevenueEntry $e): array
    {
        return ['id' => $e->id, 'period' => $e->period, 'date' => $e->entry_date->format('d/m/Y'), 'document_no' => $e->document_no, 'buyer_name' => $e->buyer_name, 'buyer_tax_code' => $e->buyer_tax_code, 'description' => $e->description, 'revenue_category' => $e->revenue_category->value, 'revenue_category_label' => $e->revenue_category->label(), 'amount' => $e->amount, 'vat_amount' => $e->vat_amount, 'pit_amount' => $e->pit_amount, 'source_type' => $e->source_type, 'notes' => $e->notes];
    }
}
