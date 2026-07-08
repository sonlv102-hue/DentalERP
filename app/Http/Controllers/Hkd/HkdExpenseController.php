<?php

namespace App\Http\Controllers\Hkd;

use App\Enums\HkdExpenseCategory;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\HkdExpenseEntry;
use App\Models\HkdProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        $entries = HkdExpenseEntry::where('hkd_profile_id', $profile->id)
            ->where('period', $period)
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($e) => $this->entryDto($e));

        return Inertia::render('Hkd/Expenses/Index', [
            'profile'    => ['id' => $profile->id, 'full_name' => $profile->full_name],
            'entries'    => $entries,
            'period'     => $period,
            'is_locked'  => $profile->isPeriodClosed($period),
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], HkdExpenseCategory::cases()),
            'total'      => $entries->sum('amount'),
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

        HkdExpenseEntry::create([...$data, 'hkd_profile_id' => $profile->id, 'created_by' => auth()->id()]);
        return back()->with('success', 'Đã thêm chi phí.');
    }

    public function update(Request $request, HkdExpenseEntry $hkdExpense): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $data    = $this->validated($request);

        if ($profile->isPeriodClosed($data['period'])) {
            return back()->withErrors(['period' => "Kỳ {$data['period']} đã được chốt."]);
        }

        $hkdExpense->update($data);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(HkdExpenseEntry $hkdExpense): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();

        if ($profile->isPeriodClosed($hkdExpense->period)) {
            return back()->withErrors(['period' => "Kỳ {$hkdExpense->period} đã được chốt."]);
        }

        $hkdExpense->delete();
        return back()->with('success', 'Đã xoá.');
    }

    /** Auto-import from the dental clinic's expenses table. */
    public function importFromExpenses(Request $request): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));

        if ($profile->isPeriodClosed($period)) {
            return back()->withErrors(['period' => "Kỳ $period đã được chốt."]);
        }

        [$year, $month] = explode('-', $period);
        $expenses = Expense::whereYear('expense_date', $year)->whereMonth('expense_date', $month)
            ->whereNotExists(fn ($q) => $q->from('hkd_expense_entries')
                ->where('hkd_profile_id', $profile->id)->whereColumn('source_id', 'expenses.id')->where('source_type', 'expense'))
            ->get();

        $imported = 0;
        foreach ($expenses as $exp) {
            HkdExpenseEntry::create(['hkd_profile_id' => $profile->id, 'period' => $period, 'entry_date' => $exp->expense_date->toDateString(), 'category' => HkdExpenseCategory::Other->value, 'description' => $exp->description, 'amount' => $exp->amount, 'source_type' => 'expense', 'source_id' => $exp->id, 'created_by' => auth()->id()]);
            $imported++;
        }

        return back()->with('success', "Đã nhập $imported khoản chi phí.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period'        => 'required|regex:/^\d{4}-\d{2}$/',
            'entry_date'    => 'required|date',
            'document_no'   => 'nullable|string|max:50',
            'supplier_name' => 'nullable|string|max:200',
            'supplier_tax_code' => 'nullable|string|max:20',
            'category'      => 'required|in:' . implode(',', array_column(HkdExpenseCategory::cases(), 'value')),
            'description'   => 'required|string',
            'amount'        => 'required|integer|min:0',
            'notes'         => 'nullable|string',
        ]);
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }

    private function entryDto(HkdExpenseEntry $e): array
    {
        return ['id' => $e->id, 'period' => $e->period, 'date' => $e->entry_date->format('d/m/Y'), 'document_no' => $e->document_no, 'supplier_name' => $e->supplier_name, 'description' => $e->description, 'category' => $e->category->value, 'category_label' => $e->category->label(), 'amount' => $e->amount, 'source_type' => $e->source_type, 'notes' => $e->notes];
    }
}
