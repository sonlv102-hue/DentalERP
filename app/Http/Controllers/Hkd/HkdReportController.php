<?php

namespace App\Http\Controllers\Hkd;

use App\Http\Controllers\Controller;
use App\Models\HkdProfile;
use App\Services\Hkd\HkdBookReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class HkdReportController extends Controller
{
    public function __construct(private readonly HkdBookReportService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('hkd.view');
        $profile = $this->activeProfile();
        $period  = $request->input('period', now()->format('Y-m'));
        $books   = $this->service->booksForProfile($profile, $period);

        return Inertia::render('Hkd/Reports/Index', [
            'profile'     => ['id' => $profile->id, 'full_name' => $profile->full_name, 'tax_code' => $profile->tax_code, 'tax_status' => $profile->tax_status->value, 'tax_status_label' => $profile->tax_status->label()],
            'period'      => $period,
            'books'       => $books,
            'book_labels' => $this->bookLabels(),
        ]);
    }

    /** Preview book data as JSON (used by Vue for on-page display). */
    public function preview(Request $request, string $period, string $book): \Illuminate\Http\JsonResponse
    {
        $this->authorize('hkd.view');
        $this->validateBookAndPeriod($book, $period);
        $profile = $this->activeProfile();

        return response()->json($this->service->generate($profile, $book, $period));
    }

    /** Download book as PDF. */
    public function downloadPdf(string $period, string $book): HttpResponse
    {
        $this->authorize('hkd.view');
        $this->validateBookAndPeriod($book, $period);
        $profile = $this->activeProfile();

        $data  = $this->service->generate($profile, $book, $period);
        $blade = 'pdf.hkd-' . strtolower($book);

        $pdf = Pdf::loadView($blade, $data)
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download("so-{$book}-{$period}.pdf");
    }

    private function validateBookAndPeriod(string $book, string $period): void
    {
        $validBooks = ['S1a', 'S2a', 'S2b', 'S2c', 'S2d', 'S2e', 'S3a'];
        if (! in_array($book, $validBooks, true)) {
            abort(422, "Sổ $book không hợp lệ.");
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            abort(422, 'Định dạng kỳ không hợp lệ (YYYY-MM).');
        }
    }

    private function activeProfile(): HkdProfile
    {
        return HkdProfile::where('is_active', true)->firstOrFail();
    }

    private function bookLabels(): array
    {
        return [
            'S1a' => 'S1a-HKD — Sổ doanh thu (không kê khai thuế)',
            'S2a' => 'S2a-HKD — Sổ doanh thu (VAT + TNCN theo % DT)',
            'S2b' => 'S2b-HKD — Sổ doanh thu (VAT theo % DT)',
            'S2c' => 'S2c-HKD — Sổ chi tiết doanh thu và chi phí',
            'S2d' => 'S2d-HKD — Sổ chi tiết hàng hóa (giá bình quân)',
            'S2e' => 'S2e-HKD — Sổ chi tiết tiền',
            'S3a' => 'S3a-HKD — Sổ theo dõi thuế khác',
        ];
    }
}
