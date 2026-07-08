<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\ClinicalTemplateType;
use App\Http\Controllers\Controller;
use App\Models\ClinicalTemplate;
use App\Models\DentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicalTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('clinical_notes.create');

        $query = ClinicalTemplate::with('service')
            ->when($request->search, fn ($q, $v) => $q->where('title', 'ilike', "%{$v}%")->orWhere('content', 'ilike', "%{$v}%"))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->orderBy('type')->orderBy('title');

        return Inertia::render('Clinical/Templates/Index', [
            'templates' => $query->paginate(30)->through(fn ($t) => $this->dto($t)),
            'filters'   => $request->only(['search', 'type']),
            'types'     => collect(ClinicalTemplateType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'target_field' => $t->targetField(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('clinical_notes.create');

        $data = $this->validated($request);
        ClinicalTemplate::create([...$data, 'created_by' => auth()->id()]);

        return back()->with('success', 'Đã tạo mẫu.');
    }

    public function update(Request $request, ClinicalTemplate $template): RedirectResponse
    {
        $this->authorize('clinical_notes.create');

        $template->update($this->validated($request));

        return back()->with('success', 'Đã cập nhật mẫu.');
    }

    public function destroy(ClinicalTemplate $template): RedirectResponse
    {
        $this->authorize('clinical_notes.create');
        $template->delete();

        return back()->with('success', 'Đã xóa mẫu.');
    }

    /** Lightweight JSON search used by the ClinicalNotesTab quick-select */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('clinical_notes.create');

        $templates = ClinicalTemplate::query()
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->q, fn ($q, $v) => $q->where('title', 'ilike', "%{$v}%"))
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'title'        => $t->title,
                'content'      => $t->content,
                'type'         => $t->type->value,
                'type_label'   => $t->type->label(),
                'target_field' => $t->type->targetField(),
            ]);

        return response()->json($templates);
    }

    private function dto(ClinicalTemplate $t): array
    {
        return [
            'id'           => $t->id,
            'type'         => $t->type->value,
            'type_label'   => $t->type->label(),
            'type_color'   => $t->type->color(),
            'target_field' => $t->type->targetField(),
            'title'        => $t->title,
            'content'      => $t->content,
            'service'      => $t->service?->name,
            'is_global'    => $t->is_global,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'       => 'required|in:'.implode(',', array_column(ClinicalTemplateType::cases(), 'value')),
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'service_id' => 'nullable|exists:dental_services,id',
            'is_global'  => 'boolean',
        ]);
    }
}
