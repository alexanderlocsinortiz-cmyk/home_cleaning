<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceHelpers;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceEnrollmentRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAttendanceController extends Controller
{
    use AttendanceHelpers;

    public function attendance(Request $request)
    {
        [$todayStartUtc, $todayEndUtc, $attendanceDate] = $this->attendanceUtcRange();
        $historyData = $this->buildAttendanceHistoryData($request);
        $staff = User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $devices = Device::orderBy('name')->get();
        $recentEnrollmentRequests = DeviceEnrollmentRequest::with(['device', 'user', 'requestedBy'])
            ->latest()
            ->take(10)
            ->get();

        $staffIds = $staff->pluck('id')->all();

        $todayLogs = AttendanceLog::whereIn('user_id', $staffIds)
            ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
            ->orderBy('logged_at')
            ->get(['user_id', 'punch_type', 'logged_at']);

        $latestTimeIn = $todayLogs->where('punch_type', 'in')
            ->groupBy('user_id')
            ->map(fn ($logs) => $logs->last());

        $latestTimeOut = $todayLogs->where('punch_type', 'out')
            ->groupBy('user_id')
            ->map(fn ($logs) => $logs->last());

        $attendance = $staff->map(function ($s) use ($latestTimeIn, $latestTimeOut) {
            $timeIn = $latestTimeIn->get($s->id);
            $timeOut = $latestTimeOut->get($s->id);

            return [
                'id' => $s->id,
                'name' => $s->first_name.' '.$s->last_name,
                'email' => $s->email,
                'barangay' => $s->barangay,
                'status' => $this->attendanceStatusFor($timeIn),
                'time_in' => $this->formatAttendanceTime($timeIn?->logged_at),
                'time_out' => $this->formatAttendanceTime($timeOut?->logged_at),
                'is_present' => $timeIn !== null,
            ];
        });

        $presentCount = $attendance->where('is_present', true)->count();
        $absentCount = $attendance->where('is_present', false)->count();
        $lateCount = $attendance->where('status', 'late')->count();

        return view('admin.attendance', array_merge(
            compact(
                'attendance', 'presentCount', 'absentCount', 'lateCount',
                'devices', 'staff', 'recentEnrollmentRequests', 'attendanceDate',
            ),
            $historyData
        ));
    }

    public function storeAttendanceDevice(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:devices,serial_number'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $device = Device::create([
            'name' => $validated['name'],
            'serial_number' => $validated['serial_number'],
            'location' => $validated['location'] ?? null,
            'api_token' => $this->generateUniqueDeviceToken(),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Biometric device created successfully.')
            ->with('generated_device_token', $device->api_token)
            ->with('generated_device_name', $device->name)
            ->with('generated_device_serial', $device->serial_number);
    }

    public function storeAttendanceEnrollmentRequest(Request $request)
    {
        $validated = $request->validate([
            'device_id' => [
                'required',
                Rule::exists('devices', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'template_id' => ['required', 'integer', 'min:1', 'max:162'],
        ]);

        $staff = User::where('role', 'staff')->findOrFail($validated['user_id']);

        if ($staff->fingerprint_template_id !== null) {
            return back()->withErrors([
                'user_id' => $staff->full_name.' already has fingerprint slot #'.$staff->fingerprint_template_id.'.',
            ])->withInput();
        }

        $deviceBusy = DeviceEnrollmentRequest::where('device_id', $validated['device_id'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($deviceBusy) {
            return back()->withErrors([
                'device_id' => 'This device already has an enrollment in progress or waiting in queue.',
            ])->withInput();
        }

        $templateTaken = User::where('fingerprint_template_id', $validated['template_id'])->exists();

        if ($templateTaken) {
            return back()->withErrors([
                'template_id' => 'That fingerprint slot is already assigned to a staff member.',
            ])->withInput();
        }

        $templateQueued = DeviceEnrollmentRequest::where('template_id', $validated['template_id'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($templateQueued) {
            return back()->withErrors([
                'template_id' => 'That fingerprint slot is already waiting in the enrollment queue.',
            ])->withInput();
        }

        DeviceEnrollmentRequest::create([
            'device_id' => $validated['device_id'],
            'user_id' => $staff->id,
            'requested_by' => auth()->id(),
            'template_id' => $validated['template_id'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Fingerprint enrollment request created. Ask the staff member to scan the same finger twice on the device.');
    }

    public function rotateAttendanceDeviceToken(Device $device)
    {
        $device->update([
            'api_token' => $this->generateUniqueDeviceToken(),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Device token rotated successfully.')
            ->with('generated_device_token', $device->api_token)
            ->with('generated_device_name', $device->name)
            ->with('generated_device_serial', $device->serial_number);
    }

    public function attendanceHistory(Request $request)
    {
        return redirect()->route('admin.attendance', array_merge(
            $request->query(),
            ['tab' => 'history']
        ));
    }
}
