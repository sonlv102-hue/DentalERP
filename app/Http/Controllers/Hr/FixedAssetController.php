<?php

namespace App\Http\Controllers\Hr;

use App\Enums\FixedAssetCategory;
use App\Enums\FixedAssetStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FixedAssetController extends Controller
{
    public function __construct(private FixedAssetService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('fixed_assets.view');

        $query = FixedAsset::with('branch')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('code', 'ilike', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->orderByDesc('id');

        return Inertia::render('Hr/FixedAssets/Index', [
            'assets'     => $query->paginate(20)->through(fn ($a) => $this->listDto($a)),
            'filters'    => $request->only(['search', 'status', 'category']),
            'statuses'   => collect(FixedAssetStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'categories' => collect(FixedAssetCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('fixed_assets.manage');
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('fixed_assets.manage');

        $data = $this->validated($request);
        $asset = $this->service->create([...$data, 'created_by' => auth()->id()]);

        return redirect()->route('hr.fixed-assets.show', $asset)->with('success', 'Đã tạo tài sản cố định.');
    }

    public function show(FixedAsset $fixedAsset): Response
    {
        $this->authorize('fixed_assets.view');

        $fixedAsset->load(['branch', 'depreciations']);

        return Inertia::render('Hr/FixedAssets/Show', [
            'asset'      => $this->detailDto($fixedAsset),
            'schedule'   => $this->service->getSchedule($fixedAsset),
        ]);
    }

    public function edit(FixedAsset $fixedAsset): Response
    {
        $this->authorize('fixed_assets.manage');
        return $this->form($fixedAsset);
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $this->authorize('fixed_assets.manage');

        $data = $this->validated($request);
        $fixedAsset->update($data);

        return redirect()->route('hr.fixed-assets.show', $fixedAsset)->with('success', 'Đã cập nhật tài sản.');
    }

    public function depreciate(Request $request): RedirectResponse
    {
        $this->authorize('fixed_assets.manage');

        $period = $request->input('period', now()->format('Y-m'));
        $result = $this->service->runMonthlyDepreciation($period);

        return back()->with('success', "Khấu hao {$period}: {$result['processed']} tài sản đã xử lý, {$result['skipped']} bỏ qua.");
    }

    public function dispose(FixedAsset $fixedAsset): RedirectResponse
    {
        $this->authorize('fixed_assets.manage');

        if ($fixedAsset->status === FixedAssetStatus::Disposed) {
            return back()->with('error', 'Tài sản đã được thanh lý.');
        }

        $this->service->dispose($fixedAsset);

        return back()->with('success', 'Đã thanh lý tài sản.');
    }

    private function form(?FixedAsset $asset = null): Response
    {
        return Inertia::render('Hr/FixedAssets/Form', [
            'asset'      => $asset ? $this->formDto($asset) : null,
            'branches'   => Branch::where('is_active', true)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'categories' => collect(FixedAssetCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    private function listDto(FixedAsset $a): array
    {
        return [
            'id'                     => $a->id,
            'code'                   => $a->code,
            'name'                   => $a->name,
            'category'               => $a->category->value,
            'category_label'         => $a->category->label(),
            'branch'                 => $a->branch?->name,
            'acquisition_cost'       => $a->acquisition_cost,
            'net_book_value'         => $a->net_book_value,
            'status'                 => $a->status->value,
            'status_label'           => $a->status->label(),
            'status_color'           => $a->status->color(),
            'last_depreciation_period' => $a->last_depreciation_period,
        ];
    }

    private function detailDto(FixedAsset $a): array
    {
        return [
            ...$this->listDto($a),
            'useful_life_months'      => $a->useful_life_months,
            'monthly_depreciation'    => $a->monthly_depreciation,
            'accumulated_depreciation' => $a->accumulated_depreciation,
            'acquisition_date'        => $a->acquisition_date->format('d/m/Y'),
            'notes'                   => $a->notes,
            'depreciations'           => $a->depreciations->map(fn ($d) => [
                'period'             => $d->period,
                'amount'             => $d->amount,
                'accumulated_before' => $d->accumulated_before,
                'net_book_value_after' => $d->net_book_value_after,
                'created_at'         => $d->created_at->format('d/m/Y'),
            ])->values()->all(),
        ];
    }

    private function formDto(FixedAsset $a): array
    {
        return [
            'id'                  => $a->id,
            'name'                => $a->name,
            'category'            => $a->category->value,
            'branch_id'           => $a->branch_id,
            'acquisition_date'    => $a->acquisition_date->format('Y-m-d'),
            'acquisition_cost'    => $a->acquisition_cost,
            'useful_life_months'  => $a->useful_life_months,
            'notes'               => $a->notes,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                => 'required|string|max:255',
            'category'            => 'required|in:'.implode(',', array_column(FixedAssetCategory::cases(), 'value')),
            'branch_id'           => 'nullable|exists:branches,id',
            'acquisition_date'    => 'required|date',
            'acquisition_cost'    => 'required|integer|min:1',
            'useful_life_months'  => 'required|integer|min:1|max:600',
            'notes'               => 'nullable|string',
        ]);
    }
}
