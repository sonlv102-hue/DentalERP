<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HkdCashAccount;
use App\Models\HkdCashTransaction;
use App\Models\HkdExpenseEntry;
use App\Models\HkdInventoryItem;
use App\Models\HkdInventoryTransaction;
use App\Models\HkdOtherTax;
use App\Models\HkdProfile;
use App\Models\HkdRevenueEntry;
use App\Services\Hkd\HkdBookReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HkdBookReportTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private HkdBookReportService $service;
    private string $period = '2025-06';

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch  = Branch::create(['code' => 'CN-0001', 'name' => 'Test Branch', 'is_active' => true]);
        $this->service = app(HkdBookReportService::class);
    }

    private function makeProfile(string $taxStatus): HkdProfile
    {
        return HkdProfile::create([
            'branch_id'  => $this->branch->id,
            'full_name'  => 'HKD Test',
            'tax_code'   => '0123456789',
            'address'    => '123 Đường Test',
            'tax_status' => $taxStatus,
            'is_active'  => true,
        ]);
    }

    // ── Scenario 1: S1a (not_subject) ────────────────────────────────────────

    public function test_s1a_not_subject_profile_returns_correct_books_and_data(): void
    {
        $profile = $this->makeProfile('not_subject');

        HkdRevenueEntry::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $this->period,
            'entry_date'     => '2025-06-05',
            'description'    => 'Dịch vụ nha khoa tháng 6',
            'amount'         => 5_000_000,
        ]);

        HkdRevenueEntry::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $this->period,
            'entry_date'     => '2025-06-12',
            'description'    => 'Hàn răng',
            'buyer_name'     => 'Nguyễn Văn A',
            'amount'         => 2_000_000,
        ]);

        $books = $this->service->booksForProfile($profile, $this->period);
        $this->assertSame(['S1a'], $books);

        $result = $this->service->generateS1a($profile, $this->period);
        $this->assertSame('S1a', $result['book']);
        $this->assertCount(2, $result['entries']);
        $this->assertSame(7_000_000, $result['total']);
        $this->assertSame('Hàn răng', $result['entries'][1]['description']);
    }

    // ── Scenario 2: S2a (vat_pit_revenue) ────────────────────────────────────

    public function test_s2a_vat_pit_revenue_profile_generates_with_tax_totals(): void
    {
        $profile = $this->makeProfile('vat_pit_revenue');

        HkdRevenueEntry::create([
            'hkd_profile_id'   => $profile->id,
            'period'           => $this->period,
            'entry_date'       => '2025-06-10',
            'description'      => 'Bọc sứ',
            'revenue_category' => 'services',
            'amount'           => 10_000_000,
            'vat_amount'       => 100_000,
            'pit_amount'       => 50_000,
        ]);

        HkdRevenueEntry::create([
            'hkd_profile_id'   => $profile->id,
            'period'           => $this->period,
            'entry_date'       => '2025-06-20',
            'description'      => 'Niềng răng',
            'revenue_category' => 'services',
            'amount'           => 30_000_000,
            'vat_amount'       => 300_000,
            'pit_amount'       => 150_000,
        ]);

        $books = $this->service->booksForProfile($profile, $this->period);
        $this->assertSame(['S2a'], $books);

        $result = $this->service->generateS2a($profile, $this->period);
        $this->assertSame('S2a', $result['book']);
        $this->assertCount(2, $result['entries']);
        $this->assertSame(40_000_000, $result['totals']['amount']);
        $this->assertSame(400_000, $result['totals']['vat_amount']);
        $this->assertSame(200_000, $result['totals']['pit_amount']);
    }

    // ── Scenario 3: S2b/S2c/S2d/S2e with weighted average costing ────────────

    public function test_s2b_s2c_s2d_s2e_generated_for_vat_revenue_pit_income_profile(): void
    {
        $profile = $this->makeProfile('vat_revenue_pit_income');

        // Revenue for S2b/S2c
        HkdRevenueEntry::create([
            'hkd_profile_id' => $profile->id, 'period' => $this->period,
            'entry_date' => '2025-06-01', 'description' => 'Dịch vụ', 'amount' => 20_000_000, 'vat_amount' => 200_000,
        ]);
        // Expense for S2c
        HkdExpenseEntry::create([
            'hkd_profile_id' => $profile->id, 'period' => $this->period,
            'entry_date' => '2025-06-05', 'description' => 'Vật tư', 'category' => 'materials', 'amount' => 5_000_000,
        ]);

        // S2d: inventory item with weighted avg costing
        $item = HkdInventoryItem::create([
            'hkd_profile_id'    => $profile->id,
            'code'              => 'VT-0001',
            'name'              => 'Vật liệu hàn composite',
            'unit'              => 'hộp',
            'opening_qty'       => 10,
            'opening_unit_cost' => 100_000,
            'is_active'         => true,
        ]);
        // Import 20 units @ 120,000 → avg = (10*100k + 20*120k) / 30 = 3,400k/30 ≈ 113,333
        HkdInventoryTransaction::create([
            'hkd_profile_id' => $profile->id, 'item_id' => $item->id,
            'period' => $this->period, 'trans_date' => '2025-06-03',
            'trans_type' => 'import', 'qty' => 20, 'unit_cost' => 120_000, 'amount' => 2_400_000,
        ]);
        // Export 15 units @ avg 113,333 ≈ 1,700,000
        HkdInventoryTransaction::create([
            'hkd_profile_id' => $profile->id, 'item_id' => $item->id,
            'period' => $this->period, 'trans_date' => '2025-06-15',
            'trans_type' => 'export', 'qty' => 15, 'unit_cost' => 0, 'amount' => 0,
        ]);

        // S2e: cash account
        $acct = HkdCashAccount::create([
            'hkd_profile_id' => $profile->id, 'name' => 'Tiền mặt', 'type' => 'cash',
            'opening_balance' => 1_000_000, 'is_active' => true,
        ]);
        HkdCashTransaction::create([
            'hkd_profile_id' => $profile->id, 'account_id' => $acct->id,
            'period' => $this->period, 'trans_date' => '2025-06-10',
            'trans_type' => 'receipt', 'amount' => 500_000, 'description' => 'Thu tiền khách',
        ]);

        $books = $this->service->booksForProfile($profile, $this->period);
        $this->assertSame(['S2b', 'S2c', 'S2d', 'S2e'], $books);

        // S2b
        $s2b = $this->service->generateS2b($profile, $this->period);
        $this->assertSame(20_000_000, $s2b['totals']['amount']);
        $this->assertSame(200_000, $s2b['totals']['vat_amount']);

        // S2c: taxable income = revenue - expenses
        $s2c = $this->service->generateS2c($profile, $this->period);
        $this->assertSame(20_000_000, $s2c['totals']['revenue']);
        $this->assertSame(5_000_000, $s2c['totals']['expenses']);
        $this->assertSame(15_000_000, $s2c['totals']['taxable_income']);

        // S2d: verify weighted average costing
        $s2d = $this->service->generateS2d($profile, $this->period);
        $this->assertCount(1, $s2d['items']);
        $itemData = $s2d['items'][0];

        // Service keeps avg as float internally; only casts to int when storing in txRow
        $floatAvg     = (10 * 100_000 + 20 * 120_000) / 30; // 113333.333...
        $expectedAvg  = (int) round($floatAvg);              // 113333 (stored in avg_cost)
        $expectedExpAmt = (int) round(15 * $floatAvg);      // 1700000 (uses unrounded float)

        $exportRow = $itemData['transactions'][1]; // second transaction = export
        $this->assertSame('export', $exportRow['type']);
        $this->assertSame($expectedAvg, $exportRow['avg_cost']);
        $this->assertSame($expectedExpAmt, $exportRow['amount']);

        // closing qty = 10 + 20 - 15 = 15
        $this->assertSame(15.0, $itemData['closing']['qty']);

        // S2e
        $s2e = $this->service->generateS2e($profile, $this->period);
        $this->assertCount(1, $s2e['accounts']);
        $acctData = $s2e['accounts'][0];
        $this->assertSame(1_000_000, $acctData['opening_balance']);
        $this->assertSame(500_000, $acctData['total_receipts']);
        $this->assertSame(0, $acctData['total_payments']);
        $this->assertSame(1_500_000, $acctData['closing_balance']);
    }

    // ── Scenario 4: S3a appended when other taxes exist ──────────────────────

    public function test_s3a_included_in_books_when_other_taxes_exist(): void
    {
        $profile = $this->makeProfile('not_subject');

        // Without other taxes → only S1a
        $books = $this->service->booksForProfile($profile, $this->period);
        $this->assertSame(['S1a'], $books);

        // Add other tax → S3a appended
        HkdOtherTax::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $this->period,
            'tax_type'       => 'Thuế môn bài',
            'taxable_amount' => 0,
            'tax_rate'       => 0,
            'tax_amount'     => 1_000_000,
            'paid_amount'    => 1_000_000,
            'paid_date'      => '2025-06-15',
        ]);
        HkdOtherTax::create([
            'hkd_profile_id' => $profile->id,
            'period'         => $this->period,
            'tax_type'       => 'Phí bảo vệ môi trường',
            'taxable_amount' => 50_000_000,
            'tax_rate'       => 0.01,
            'tax_amount'     => 500_000,
            'paid_amount'    => 0,
            'due_date'       => '2025-07-20',
        ]);

        $books = $this->service->booksForProfile($profile, $this->period);
        $this->assertContains('S3a', $books);
        $this->assertSame(['S1a', 'S3a'], $books);

        $s3a = $this->service->generateS3a($profile, $this->period);
        $this->assertSame('S3a', $s3a['book']);
        $this->assertCount(2, $s3a['taxes']);
        $this->assertSame(1_500_000, $s3a['totals']['tax_amount']);
        $this->assertSame(1_000_000, $s3a['totals']['paid_amount']);
        $this->assertSame(500_000, $s3a['totals']['remaining']);

        // Paid tax should have isPaid = true
        $paidTax = collect($s3a['taxes'])->firstWhere('tax_type', 'Thuế môn bài');
        $this->assertTrue($paidTax['is_paid']);

        // Unpaid tax should have isPaid = false
        $unpaidTax = collect($s3a['taxes'])->firstWhere('tax_type', 'Phí bảo vệ môi trường');
        $this->assertFalse($unpaidTax['is_paid']);
    }
}
