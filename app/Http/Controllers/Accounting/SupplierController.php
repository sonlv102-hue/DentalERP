<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('accounting.view');

        $query = Supplier::orderBy('name');
        if ($request->search) $query->where('name', 'ilike', '%' . $request->search . '%');
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));

        $suppliers = $query->paginate(20)->through(fn ($s) => [
            'id'             => $s->id,
            'name'           => $s->name,
            'phone'          => $s->phone,
            'email'          => $s->email,
            'tax_code'       => $s->tax_code,
            'contact_person' => $s->contact_person,
            'bank_name'      => $s->bank_name,
            'bank_account'   => $s->bank_account,
            'is_active'      => $s->is_active,
        ]);

        return Inertia::render('Accounting/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => $request->only(['search', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'tax_code'       => 'nullable|string|max:20',
            'bank_account'   => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
        ]);

        Supplier::create($data);

        return back()->with('success', 'Đã tạo nhà cung cấp.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'tax_code'       => 'nullable|string|max:20',
            'bank_account'   => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ]);

        $supplier->update($data);

        return back()->with('success', 'Đã cập nhật nhà cung cấp.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('accounting.manage');
        $supplier->update(['is_active' => false]);
        return back()->with('success', 'Đã vô hiệu hóa nhà cung cấp.');
    }
}
