<?php

namespace App\Http\Controllers\Lab;

use App\Enums\LabWarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabWarranty;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabWarrantyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('labo.view');

        $query = LabWarranty::with(['patient', 'labOrder.lab'])
            ->when($request->search, fn ($q, $v) => $q->where('service_name', 'ilike', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id');

        return Inertia::render('Lab/Warranties/Index', [
            'warranties' => $query->paginate(20)->through(fn ($w) => [
                'id'           => $w->id,
                'patient'      => $w->patient->full_name,
                'lab'          => $w->labOrder?->lab->name,
                'service_name' => $w->service_name,
                'tooth_number' => $w->tooth_number,
                'start_date'   => $w->start_date->format('d/m/Y'),
                'end_date'     => $w->end_date->format('d/m/Y'),
                'status'       => $w->status->value,
                'status_label' => $w->status->label(),
                'status_color' => $w->status->color(),
            ]),
            'filters'    => $request->only(['search', 'status']),
            'statuses'   => collect(LabWarrantyStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function store(Request $request, LabOrder $order): RedirectResponse
    {
        $this->authorize('labo.manage');

        $data = $request->validate([
            'service_name' => 'required|string|max:255',
            'tooth_number' => 'nullable|string|max:20',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'notes'        => 'nullable|string',
        ]);

        LabWarranty::create([
            ...$data,
            'lab_order_id' => $order->id,
            'patient_id'   => $order->patient_id,
            'status'       => LabWarrantyStatus::Active,
        ]);

        return back()->with('success', 'Đã tạo thẻ bảo hành.');
    }

    public function claim(LabWarranty $warranty): RedirectResponse
    {
        $this->authorize('labo.manage');

        $warranty->update(['status' => LabWarrantyStatus::Claimed]);

        return back()->with('success', 'Đã ghi nhận bảo hành.');
    }

    public function destroy(LabWarranty $warranty): RedirectResponse
    {
        $this->authorize('labo.manage');
        $warranty->delete();

        return back()->with('success', 'Đã xóa thẻ bảo hành.');
    }
}
