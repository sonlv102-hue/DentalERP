<?php

namespace App\Http\Controllers\Hkd;

use App\Http\Controllers\Controller;
use App\Models\HkdPeriodClose;
use App\Models\HkdProfile;
use App\Services\Hkd\HkdPeriodCloseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HkdPeriodCloseController extends Controller
{
    public function __construct(private readonly HkdPeriodCloseService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();

        // Show last 12 periods
        $periods = collect();
        $current = now();
        for ($i = 0; $i < 12; $i++) {
            $period = $current->copy()->subMonths($i)->format('Y-m');
            $close  = $this->service->getOrCreate($profile, $period);
            $periods->push([
                'period'      => $period,
                'status'      => $close->status->value,
                'status_label'=> $close->status->label(),
                'status_color'=> $close->status->color(),
                'closed_at'   => $close->closed_at?->format('d/m/Y H:i'),
                'closed_by'   => $close->closedByUser?->name,
                'unlocked_at' => $close->unlocked_at?->format('d/m/Y H:i'),
                'unlocked_by' => $close->unlockedByUser?->name,
                'unlock_reason' => $close->unlock_reason,
                'snapshot_totals' => $close->snapshot_data['totals'] ?? null,
            ]);
        }

        return Inertia::render('Hkd/PeriodClose/Index', [
            'profile' => ['id' => $profile->id, 'full_name' => $profile->full_name],
            'periods' => $periods,
        ]);
    }

    public function close(Request $request, string $period): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $this->validatePeriod($period);
        $profile = $this->activeProfile();

        try {
            $this->service->closePeriod($profile, $period, auth()->user());
            return back()->with('success', "Đã chốt kỳ $period.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['period' => $e->getMessage()]);
        }
    }

    public function unlock(Request $request, string $period): RedirectResponse
    {
        $this->authorize('hkd.manage');
        $this->validatePeriod($period);
        $request->validate(['reason' => 'required|string|min:10|max:500']);
        $profile = $this->activeProfile();

        try {
            $this->service->unlockPeriod($profile, $period, auth()->user(), $request->reason);
            return back()->with('success', "Đã mở khoá kỳ $period.");
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return back()->withErrors(['period' => $e->getMessage()]);
        }
    }

    private function validatePeriod(string $period): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            abort(422, 'Định dạng kỳ không hợp lệ (YYYY-MM).');
        }
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }
}
