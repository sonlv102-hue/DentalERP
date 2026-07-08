<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\DebtStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadSource;
use App\Enums\PaymentMethod;
use App\Enums\RoleType;
use App\Enums\TreatmentItemStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DentalChair;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientDebt;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds a small, proportionate demo dataset (roles/permissions, branches, staff,
 * services, patients, treatment plans, invoices, payments, appointments) for the
 * standalone DentalERP-Demo copy — see .claude plan "Bản demo độc lập".
 *
 * Deliberately NOT registered in any DatabaseSeeder / composer script: run explicitly
 * with `php artisan db:seed --class=DemoSeeder`.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Hard safety rail: this must never run against the real Postgres database.
        if (DB::getDriverName() !== 'sqlite') {
            throw new \RuntimeException(
                'DemoSeeder refuses to run: DB_CONNECTION is "'.DB::getDriverName().'", not "sqlite". '
                .'This seeder is only meant for the local DentalERP-Demo SQLite database.'
            );
        }

        $this->command?->info('Seeding demo data into: '.DB::connection()->getDatabaseName());

        [$role] = $this->seedRoleAndPermissions();
        $branches = $this->seedBranches();
        $employees = $this->seedEmployees($branches);
        $services = $this->seedServices();
        $this->seedChairs($branches);
        $demoUser = $this->seedDemoUser($role);
        $patients = $this->seedPatients($branches);
        $this->seedTreatmentPlansAndInvoices($patients, $employees, $services, $demoUser);
        $this->seedAppointments($patients, $employees, $demoUser);

        $this->command?->info('Done. Login: demo@dentalerp.test / Demo@12345');
    }

    private function seedRoleAndPermissions(): array
    {
        // Full admin: the demo login should see and do everything, so every permission
        // referenced anywhere in routes/web.php, controller authorize()/can() calls, and
        // menuConfig.js is granted. Role is named "admin" (not "Demo") because a few
        // controllers (DashboardController, FollowUpTaskController, KpiAllocationController)
        // hard-code hasRole(['owner', 'admin']) checks that permissions alone can't satisfy.
        $permissions = [
            'admin', 'admin.audit_log', 'admin.roles', 'admin.users',
            'accounting.view', 'accounting.manage',
            'appointments.view', 'appointments.create', 'appointments.manage',
            'branches.view', 'branches.manage',
            'cashier.view', 'cashier.manage', 'cashier.approve_discount', 'cashier.approve_refund',
            'clinical_notes.create',
            'commissions.view', 'commissions.manage',
            'dental.view', 'dental.manage', 'dental.kpi.view', 'dental.kpi.manage',
            'employees.view', 'employees.manage',
            'expenses.view', 'expenses.manage',
            'fixed_assets.view', 'fixed_assets.manage',
            'hkd.view', 'hkd.manage',
            'inventory.view', 'inventory.manage',
            'labo.view', 'labo.manage',
            'leads.view', 'leads.manage', 'leads.assign', 'leads.create',
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'reports.view', 'reports.financial', 'reports.clinical',
            'services.view', 'services.manage',
            'settings.view', 'settings.manage',
            'treatment_plans.view', 'treatment_plans.create', 'treatment_plans.edit', 'treatment_plans.approve',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return [$role];
    }

    private function seedBranches(): array
    {
        $data = [
            ['name' => 'Nha Khoa Demo - Quận 1', 'address' => '12 Nguyễn Huệ, Quận 1, TP.HCM', 'phone' => '02838221100'],
            ['name' => 'Nha Khoa Demo - Quận 7', 'address' => '88 Nguyễn Thị Thập, Quận 7, TP.HCM', 'phone' => '02854130022'],
        ];

        return collect($data)->map(function ($b) {
            return Branch::create([
                'code' => Branch::generateCode(),
                'name' => $b['name'],
                'address' => $b['address'],
                'phone' => $b['phone'],
                'is_active' => true,
            ]);
        })->all();
    }

    private function seedEmployees(array $branches): array
    {
        $doctors = [
            'BS. Nguyễn Văn Long', 'BS. Trần Thị Minh Anh', 'BS. Lê Hoàng Nam',
            'BS. Phạm Thị Thu Hà', 'BS. Đỗ Quốc Việt', 'BS. Vũ Thị Ngọc Lan',
        ];
        $consultants = ['Hoàng Thị Kim Chi', 'Bùi Văn Đức'];

        $employees = [];
        foreach ($doctors as $i => $name) {
            $employees[] = Employee::create([
                'code' => Employee::generateCode(),
                'branch_id' => $branches[$i % count($branches)]->id,
                'full_name' => $name,
                'role_type' => RoleType::Doctor->value,
                'is_active' => true,
            ]);
        }
        foreach ($consultants as $i => $name) {
            $employees[] = Employee::create([
                'code' => Employee::generateCode(),
                'branch_id' => $branches[$i % count($branches)]->id,
                'full_name' => $name,
                'role_type' => RoleType::Consultant->value,
                'is_active' => true,
            ]);
        }

        return $employees;
    }

    private function seedServices(): array
    {
        $services = [
            ['name' => 'Khám tổng quát', 'price' => 100000, 'duration' => 15],
            ['name' => 'Cạo vôi răng', 'price' => 200000, 'duration' => 30],
            ['name' => 'Trám răng thẩm mỹ', 'price' => 350000, 'duration' => 30],
            ['name' => 'Nhổ răng khôn', 'price' => 800000, 'duration' => 45],
            ['name' => 'Nhổ răng sữa', 'price' => 150000, 'duration' => 15],
            ['name' => 'Tẩy trắng răng', 'price' => 2500000, 'duration' => 60],
            ['name' => 'Bọc răng sứ thẩm mỹ', 'price' => 3000000, 'duration' => 60],
            ['name' => 'Điều trị tủy răng', 'price' => 1200000, 'duration' => 60],
            ['name' => 'Cấy ghép Implant', 'price' => 25000000, 'duration' => 90],
            ['name' => 'Niềng răng Invisalign', 'price' => 60000000, 'duration' => 60],
            ['name' => 'Niềng răng mắc cài kim loại', 'price' => 35000000, 'duration' => 60],
            ['name' => 'Hàn răng sâu', 'price' => 300000, 'duration' => 30],
            ['name' => 'Nhổ răng thường', 'price' => 400000, 'duration' => 30],
            ['name' => 'Khám và tư vấn chỉnh nha', 'price' => 0, 'duration' => 20],
            ['name' => 'Chụp X-quang răng', 'price' => 150000, 'duration' => 15],
            ['name' => 'Máng chống nghiến răng', 'price' => 1500000, 'duration' => 30],
            ['name' => 'Vệ sinh răng miệng định kỳ', 'price' => 250000, 'duration' => 30],
            ['name' => 'Phục hình răng tháo lắp', 'price' => 4500000, 'duration' => 60],
        ];

        return collect($services)->map(function ($s) {
            return DentalService::create([
                'code' => DentalService::generateCode(),
                'name' => $s['name'],
                'selling_price' => $s['price'],
                'duration_minutes' => $s['duration'],
                'is_active' => true,
            ]);
        })->all();
    }

    private function seedChairs(array $branches): void
    {
        foreach ($branches as $branch) {
            foreach (['Ghế 1', 'Ghế 2', 'Ghế 3'] as $name) {
                DentalChair::create([
                    'branch_id' => $branch->id,
                    'code' => DB::table('dental_chairs')->count() + 1,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedDemoUser(Role $role): User
    {
        // No branch_id on purpose: the dashboard/list scoping only restricts non-owner/admin
        // roles when a branch_id is set, so leaving it null lets the demo login see both
        // demo branches at once — better for showing off the software than a single-branch view.
        $user = User::create([
            'name' => 'Người dùng Demo',
            'email' => 'demo@dentalerp.test',
            'password' => Hash::make('Demo@12345'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function seedPatients(array $branches): array
    {
        $lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Vũ', 'Đặng', 'Bùi', 'Đỗ'];
        $middleNamesMale = ['Văn', 'Hữu', 'Đức', 'Minh', 'Quốc', 'Thành'];
        $middleNamesFemale = ['Thị', 'Ngọc', 'Thu', 'Kim', 'Thanh', 'Bích'];
        $firstNamesMale = ['Long', 'Nam', 'Khôi', 'Bảo', 'Phúc', 'Hùng', 'Dũng', 'Việt', 'Tuấn', 'Hoàng'];
        $firstNamesFemale = ['Anh', 'Hà', 'Linh', 'Chi', 'Trang', 'Dung', 'Mai', 'Ngân', 'Thảo', 'Vy'];
        $sources = LeadSource::cases();

        $patients = [];
        for ($i = 0; $i < 30; $i++) {
            $isMale = $i % 2 === 0;
            $last = $lastNames[array_rand($lastNames)];
            $middle = $isMale ? $middleNamesMale[array_rand($middleNamesMale)] : $middleNamesFemale[array_rand($middleNamesFemale)];
            $first = $isMale ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
            $fullName = "{$last} {$middle} {$first}";
            $phone = '09'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);

            $patients[] = Patient::create([
                'code' => Patient::generateCode(),
                'full_name' => $fullName,
                'phone' => $phone,
                'gender' => $isMale ? 'male' : 'female',
                'dob' => now()->subYears(random_int(18, 65))->subDays(random_int(0, 365))->toDateString(),
                'address' => random_int(1, 100).' đường số '.random_int(1, 20).', TP.HCM',
                'source' => $sources[array_rand($sources)]->value,
                'branch_id' => $branches[$i % count($branches)]->id,
                'is_active' => true,
            ]);
        }

        return $patients;
    }

    private function seedTreatmentPlansAndInvoices(array $patients, array $employees, array $services, User $demoUser): void
    {
        $doctors = array_values(array_filter($employees, fn ($e) => $e->role_type === RoleType::Doctor));
        $statuses = [TreatmentPlanStatus::Draft, TreatmentPlanStatus::Approved, TreatmentPlanStatus::InProgress, TreatmentPlanStatus::Completed];

        for ($i = 0; $i < 15; $i++) {
            $patient = $patients[$i];
            $doctor = $doctors[$i % count($doctors)];
            $status = $statuses[$i % count($statuses)];

            $plan = TreatmentPlan::create([
                'code' => TreatmentPlan::generateCode(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $patient->branch_id,
                'status' => $status->value,
                'total_amount' => 0,
                'discount_amount' => 0,
                'deposit_amount' => 0,
                'priority' => 'normal',
                'created_by' => $demoUser->id,
                'start_date' => now()->subDays(random_int(0, 20))->toDateString(),
                'diagnosis' => 'Sâu răng, cần điều trị phục hồi',
                'chief_complaint' => 'Đau răng khi ăn nhai',
            ]);

            $itemCount = random_int(1, 3);
            $total = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $service = $services[array_rand($services)];
                $qty = random_int(1, 2);
                $subtotal = $service->selling_price * $qty;
                $total += $subtotal;

                TreatmentPlanItem::create([
                    'treatment_plan_id' => $plan->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'tooth_number' => (string) random_int(11, 48),
                    'quantity' => $qty,
                    'unit_price' => $service->selling_price,
                    'subtotal' => $subtotal,
                    'amount' => $subtotal,
                    'status' => $status === TreatmentPlanStatus::Completed
                        ? TreatmentItemStatus::Completed->value
                        : TreatmentItemStatus::Pending->value,
                ]);
            }
            $plan->update(['total_amount' => $total]);

            // Invoice + payment, only for plans past draft (mirrors real usage: draft plans have no invoice yet).
            if ($status === TreatmentPlanStatus::Draft) {
                continue;
            }

            $paidRatio = match (true) {
                $status === TreatmentPlanStatus::Completed => 1.0,
                $i % 3 === 0 => 0.0,
                $i % 3 === 1 => 0.5,
                default => 1.0,
            };
            $amountPaid = (int) round($total * $paidRatio, -3);
            $invoiceStatus = $amountPaid <= 0 ? InvoiceStatus::Sent : ($amountPaid >= $total ? InvoiceStatus::Paid : InvoiceStatus::PartialPaid);

            $invoice = PatientInvoice::create([
                'code' => PatientInvoice::generateCode(),
                'patient_id' => $patient->id,
                'treatment_plan_id' => $plan->id,
                'branch_id' => $patient->branch_id,
                'status' => $invoiceStatus->value,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'due_date' => now()->addDays(random_int(-5, 15))->toDateString(),
                'created_by' => $demoUser->id,
            ]);

            if ($amountPaid > 0) {
                PatientPayment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $amountPaid,
                    'method' => [PaymentMethod::Cash, PaymentMethod::Transfer, PaymentMethod::Card][$i % 3]->value,
                    'payment_date' => now()->subDays(random_int(0, 10))->toDateString(),
                    'created_by' => $demoUser->id,
                ]);
            }

            $remaining = $total - $amountPaid;
            if ($remaining > 0) {
                PatientDebt::create([
                    'patient_id' => $patient->id,
                    'treatment_plan_id' => $plan->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $total,
                    'paid_amount' => $amountPaid,
                    'remaining' => $remaining,
                    'due_date' => $invoice->due_date,
                    'status' => $amountPaid > 0 ? DebtStatus::Partial->value : DebtStatus::Pending->value,
                ]);
            }
        }
    }

    private function seedAppointments(array $patients, array $employees, User $demoUser): void
    {
        $doctors = array_values(array_filter($employees, fn ($e) => $e->role_type === RoleType::Doctor));
        $statuses = [AppointmentStatus::Booked, AppointmentStatus::Confirmed, AppointmentStatus::CheckedIn, AppointmentStatus::Cancelled];

        for ($i = 0; $i < 20; $i++) {
            $patient = $patients[array_rand($patients)];
            $doctor = $doctors[$i % count($doctors)];
            $dayOffset = random_int(-7, 7);
            $hour = random_int(8, 17);
            $status = $dayOffset < 0
                ? $statuses[array_rand($statuses)]
                : AppointmentStatus::Booked;

            $chair = DentalChair::where('branch_id', $patient->branch_id)->inRandomOrder()->first();

            Appointment::create([
                'code' => Appointment::generateCode(),
                'patient_id' => $patient->id,
                'branch_id' => $patient->branch_id,
                'doctor_id' => $doctor->id,
                'dental_chair_id' => $chair?->id,
                'scheduled_at' => now()->addDays($dayOffset)->setTime($hour, random_int(0, 1) * 30),
                'duration_minutes' => [30, 45, 60][random_int(0, 2)],
                'status' => $status->value,
                'created_by' => $demoUser->id,
            ]);
        }
    }
}
