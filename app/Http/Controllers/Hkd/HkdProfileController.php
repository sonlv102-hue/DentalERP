<?php

namespace App\Http\Controllers\Hkd;

use App\Enums\HkdRevenueCategory;
use App\Enums\HkdTaxStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HkdBusinessCategory;
use App\Models\HkdBusinessLocation;
use App\Models\HkdProfile;
use App\Models\HkdTaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdProfileController extends Controller
{
    public function index(): Response
    {
        $this->authorize('hkd.view');

        $profile = HkdProfile::with(['branch', 'locations', 'businessCategories'])->where('is_active', true)->first();
        $taxRates = HkdTaxRate::orderBy('revenue_category')->orderByDesc('effective_from')->get()
            ->map(fn ($r) => ['id' => $r->id, 'revenue_category' => $r->revenue_category->value, 'revenue_category_label' => $r->revenue_category->label(), 'vat_rate' => (float) $r->vat_rate, 'pit_rate' => (float) $r->pit_rate, 'effective_from' => $r->effective_from->format('Y-m-d'), 'effective_to' => $r->effective_to?->format('Y-m-d'), 'notes' => $r->notes]);

        return Inertia::render('Hkd/Profile/Index', [
            'profile'      => $profile ? $this->profileDto($profile) : null,
            'taxRates'     => $taxRates,
            'branches'     => Branch::where('is_active', true)->pluck('name', 'id'),
            'taxStatuses'  => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], HkdTaxStatus::cases()),
            'revCategories'=> array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], HkdRevenueCategory::cases()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate([
            'branch_id'          => 'nullable|exists:branches,id',
            'full_name'          => 'required|string|max:200',
            'tax_code'           => 'nullable|string|max:20',
            'id_number'          => 'nullable|string|max:20',
            'address'            => 'nullable|string',
            'province'           => 'nullable|string|max:100',
            'district'           => 'nullable|string|max:100',
            'representative_name'=> 'nullable|string|max:200',
            'representative_id'  => 'nullable|string|max:20',
            'tax_authority_name' => 'nullable|string|max:200',
            'registration_date'  => 'nullable|date',
            'tax_status'         => 'required|in:' . implode(',', array_column(HkdTaxStatus::cases(), 'value')),
        ]);
        HkdProfile::create($data);
        return back()->with('success', 'Đã tạo hồ sơ HKD.');
    }

    public function update(Request $request, HkdProfile $hkdProfile): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate([
            'branch_id'          => 'nullable|exists:branches,id',
            'full_name'          => 'required|string|max:200',
            'tax_code'           => 'nullable|string|max:20',
            'id_number'          => 'nullable|string|max:20',
            'address'            => 'nullable|string',
            'province'           => 'nullable|string|max:100',
            'district'           => 'nullable|string|max:100',
            'representative_name'=> 'nullable|string|max:200',
            'representative_id'  => 'nullable|string|max:20',
            'tax_authority_name' => 'nullable|string|max:200',
            'registration_date'  => 'nullable|date',
            'tax_status'         => 'required|in:' . implode(',', array_column(HkdTaxStatus::cases(), 'value')),
        ]);
        $hkdProfile->update($data);
        return back()->with('success', 'Đã cập nhật hồ sơ HKD.');
    }

    // --- Locations ---
    public function storeLocation(Request $request, HkdProfile $hkdProfile): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate(['address' => 'required|string', 'province' => 'nullable|string|max:100', 'district' => 'nullable|string|max:100', 'ward' => 'nullable|string|max:100', 'business_type' => 'nullable|string|max:100', 'is_primary' => 'boolean', 'notes' => 'nullable|string']);
        $hkdProfile->locations()->create($data);
        return back()->with('success', 'Đã thêm địa điểm kinh doanh.');
    }

    public function destroyLocation(HkdProfile $hkdProfile, HkdBusinessLocation $location): RedirectResponse
    {
        $this->authorize('update', $hkdProfile);
        $location->delete();
        return back()->with('success', 'Đã xoá địa điểm.');
    }

    // --- Tax Rates ---
    public function storeTaxRate(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $data = $request->validate(['revenue_category' => 'required|in:' . implode(',', array_column(HkdRevenueCategory::cases(), 'value')), 'vat_rate' => 'required|numeric|min:0|max:1', 'pit_rate' => 'required|numeric|min:0|max:1', 'effective_from' => 'required|date', 'effective_to' => 'nullable|date|after:effective_from', 'notes' => 'nullable|string']);
        HkdTaxRate::create($data);
        return back()->with('success', 'Đã thêm tỷ lệ thuế.');
    }

    public function destroyTaxRate(HkdTaxRate $taxRate): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $taxRate->delete();
        return back()->with('success', 'Đã xoá tỷ lệ thuế.');
    }

    private function profileDto(HkdProfile $profile): array
    {
        return [
            'id' => $profile->id, 'branch_id' => $profile->branch_id, 'branch' => $profile->branch?->name,
            'full_name' => $profile->full_name, 'tax_code' => $profile->tax_code, 'id_number' => $profile->id_number,
            'address' => $profile->address, 'province' => $profile->province, 'district' => $profile->district,
            'representative_name' => $profile->representative_name, 'representative_id' => $profile->representative_id,
            'tax_authority_name' => $profile->tax_authority_name,
            'registration_date' => $profile->registration_date?->format('Y-m-d'),
            'tax_status' => $profile->tax_status->value, 'tax_status_label' => $profile->tax_status->label(),
            'is_active' => $profile->is_active,
            'locations' => $profile->locations->map(fn ($l) => ['id' => $l->id, 'address' => $l->address, 'province' => $l->province, 'district' => $l->district, 'ward' => $l->ward, 'business_type' => $l->business_type, 'is_primary' => $l->is_primary, 'notes' => $l->notes])->all(),
            'business_categories' => $profile->businessCategories->map(fn ($c) => ['id' => $c->id, 'industry_code' => $c->industry_code, 'industry_name' => $c->industry_name, 'revenue_category' => $c->revenue_category->value, 'revenue_category_label' => $c->revenue_category->label(), 'is_primary' => $c->is_primary])->all(),
        ];
    }
}
