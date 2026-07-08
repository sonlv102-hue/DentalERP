<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Services\Hr\AttendanceDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDeviceController extends Controller
{
    public function __construct(private AttendanceDeviceService $service) {}

    public function index(): Response
    {
        $this->authorize('employees.manage');

        $devices = AttendanceDevice::with('branch')
            ->orderBy('branch_id')->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'name'         => $d->name,
                'ip'           => $d->ip,
                'port'         => $d->port,
                'serial'       => $d->serial_number,
                'branch'       => $d->branch?->name,
                'branch_id'    => $d->branch_id,
                'last_sync_at' => $d->last_sync_at?->format('d/m/Y H:i'),
                'is_active'    => $d->is_active,
            ]);

        return Inertia::render('Hr/AttendanceDevices/Index', [
            'devices'  => $devices,
            'branches' => Branch::where('is_active', true)->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'ip'            => 'required|string|max:45',
            'port'          => 'nullable|integer|between:1,65535',
            'password'      => 'nullable|string|max:20',
            'serial_number' => 'nullable|string|max:50',
            'branch_id'     => 'required|exists:branches,id',
            'is_active'     => 'boolean',
        ]);

        AttendanceDevice::create($data + ['port' => $data['port'] ?? 4370]);

        return back()->with('success', 'Đã thêm máy chấm công.');
    }

    public function update(Request $request, AttendanceDevice $attendanceDevice): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'ip'            => 'required|string|max:45',
            'port'          => 'nullable|integer|between:1,65535',
            'password'      => 'nullable|string|max:20',
            'serial_number' => 'nullable|string|max:50',
            'branch_id'     => 'required|exists:branches,id',
            'is_active'     => 'boolean',
        ]);

        $attendanceDevice->update($data);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(AttendanceDevice $attendanceDevice): RedirectResponse
    {
        $this->authorize('employees.manage');
        $attendanceDevice->delete();

        return back()->with('success', 'Đã xóa máy chấm công.');
    }

    /**
     * Trigger manual sync: pull logs from device → attendance_logs → timesheets.
     */
    public function sync(AttendanceDevice $attendanceDevice): RedirectResponse
    {
        $this->authorize('employees.manage');

        try {
            $result     = $this->service->sync($attendanceDevice);
            $timesheets = $this->service->processToTimesheets();

            return back()->with(
                'success',
                "Đồng bộ hoàn tất: {$result['new']} log mới / {$result['total']} tổng. Đã tạo/cập nhật {$timesheets} bảng công."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi kết nối máy chấm công: ' . $e->getMessage());
        }
    }
}
