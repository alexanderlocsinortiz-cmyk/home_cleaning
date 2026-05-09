<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceEnrollmentRequest;
use App\Models\User;
use App\Services\DeviceTokenService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    private DeviceTokenService $tokenService;

    public function __construct(DeviceTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }
    public function punch(Request $request)
    {
        $device = $this->authenticateDevice($request);

        $request->validate([
            'employee_code' => 'nullable|string|required_without:template_id',
            'template_id' => 'nullable|integer|min:1|required_without:employee_code',
            'punch_type' => 'required|in:in,out,auto',
            'timestamp' => 'nullable|date',
        ]);

        $staff = User::where('role', 'staff')
            ->when(
                $request->filled('template_id'),
                fn ($query) => $query->where('fingerprint_template_id', (int) $request->template_id),
                fn ($query) => $query->where('username', $request->employee_code)
            )
            ->first();

        if (! $staff) {
            return response()->json([
                'error' => $request->filled('template_id')
                    ? 'Fingerprint template is not assigned to any staff member.'
                    : 'Staff not found.',
            ], 404);
        }

        $loggedAtLocal = $request->timestamp
            ? Carbon::parse($request->timestamp, $this->attendanceTimezone())->timezone($this->attendanceTimezone())
            : Carbon::now($this->attendanceTimezone());

        $loggedAtUtc = $loggedAtLocal->copy()->utc();

        $punchType = $request->punch_type === 'auto'
            ? $this->resolvePunchType($staff->id, $loggedAtLocal)
            : $request->punch_type;

        $status = $this->resolveAttendanceStatus($loggedAtLocal, $punchType);

        $log = AttendanceLog::create([
            'user_id' => $staff->id,
            'device_id' => $device->id,
            'punch_type' => $punchType,
            'logged_at' => $loggedAtUtc,
            'status' => $status,
            'source' => 'device',
            'raw_payload' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'staff_name' => $staff->first_name.' '.$staff->last_name,
            'requested_punch_type' => $request->punch_type,
            'punch_type' => $punchType,
            'status' => $status,
            'logged_at' => $loggedAtLocal->format('Y-m-d H:i:s'),
            'message' => $punchType === 'in'
                ? 'Time-in recorded for '.$staff->first_name.' '.$staff->last_name.'.'
                : 'Time-out recorded for '.$staff->first_name.' '.$staff->last_name.'.',
        ]);
    }

    public function nextEnrollmentRequest(Request $request)
    {
        $device = $this->authenticateDevice($request);

        $enrollmentRequest = DeviceEnrollmentRequest::with('user')
            ->where('device_id', $device->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->oldest('created_at')
            ->first();

        if (! $enrollmentRequest) {
            return response()->json([
                'has_request' => false,
            ]);
        }

        return response()->json([
            'has_request' => true,
            'request_id' => $enrollmentRequest->id,
            'status' => $enrollmentRequest->status,
            'template_id' => $enrollmentRequest->template_id,
            'staff_name' => $enrollmentRequest->user->full_name,
            'employee_code' => $enrollmentRequest->user->username,
        ]);
    }

    public function updateEnrollmentRequest(Request $request)
    {
        $device = $this->authenticateDevice($request);

        $validated = $request->validate([
            'request_id' => 'required|integer',
            'status' => 'required|in:in_progress,completed,failed',
            'error_message' => 'nullable|string|max:500',
        ]);

        $enrollmentRequest = DeviceEnrollmentRequest::with('user')
            ->where('device_id', $device->id)
            ->find($validated['request_id']);

        if (! $enrollmentRequest) {
            return response()->json(['error' => 'Enrollment request not found for this device.'], 404);
        }

        if ($validated['status'] === 'in_progress') {
            $enrollmentRequest->update([
                'status' => 'in_progress',
                'started_at' => $enrollmentRequest->started_at ?: now(),
                'error_message' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enrollment marked in progress.',
            ]);
        }

        if ($validated['status'] === 'failed') {
            $enrollmentRequest->update([
                'status' => 'failed',
                'started_at' => $enrollmentRequest->started_at ?: now(),
                'completed_at' => now(),
                'error_message' => $validated['error_message'] ?? 'Enrollment failed on device.',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enrollment marked failed.',
            ]);
        }

        $templateConflict = User::where('fingerprint_template_id', $enrollmentRequest->template_id)
            ->where('id', '!=', $enrollmentRequest->user_id)
            ->exists();

        if ($templateConflict) {
            return response()->json([
                'error' => 'That fingerprint slot is already assigned to another staff member.',
            ], 409);
        }

        DB::transaction(function () use ($enrollmentRequest) {
            $enrollmentRequest->user->update([
                'fingerprint_template_id' => $enrollmentRequest->template_id,
            ]);

            $enrollmentRequest->update([
                'status' => 'completed',
                'started_at' => $enrollmentRequest->started_at ?: now(),
                'completed_at' => now(),
                'error_message' => null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Enrollment completed and assigned to staff.',
        ]);
    }

    public function heartbeat(Request $request)
    {
        $device = $this->authenticateDevice($request);

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'serial_number' => $device->serial_number,
            ],
            'last_seen_at' => $device->last_seen_at
                ? $device->last_seen_at->copy()->timezone($this->attendanceTimezone())->format('Y-m-d H:i:s')
                : null,
        ]);
    }

    public function todayStatus()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        [$todayStartUtc, $todayEndUtc] = $this->attendanceUtcRange();
        $staff = User::where('role', 'staff')->get();

        $attendance = $staff->map(function ($s) use ($todayStartUtc, $todayEndUtc) {
            $timeIn = AttendanceLog::where('user_id', $s->id)
                ->where('punch_type', 'in')
                ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
                ->latest('logged_at')
                ->first();

            $timeOut = AttendanceLog::where('user_id', $s->id)
                ->where('punch_type', 'out')
                ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
                ->latest('logged_at')
                ->first();

            return [
                'id' => $s->id,
                'name' => $s->first_name.' '.$s->last_name,
                'status' => $timeIn
                    ? $this->resolveAttendanceStatus($timeIn->logged_at->copy()->timezone($this->attendanceTimezone()), 'in')
                    : 'absent',
                'time_in' => $timeIn ? $timeIn->logged_at->copy()->timezone($this->attendanceTimezone())->format('h:i A') : null,
                'time_out' => $timeOut ? $timeOut->logged_at->copy()->timezone($this->attendanceTimezone())->format('h:i A') : null,
                'is_present' => $timeIn !== null,
            ];
        });

        return response()->json($attendance);
    }

    private function authenticateDevice(Request $request): Device
    {
        // Get security headers
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $deviceSerial = $request->header('X-Device-Serial');

        // Validate headers present
        if (!$signature || !$timestamp || !$deviceSerial) {
            Log::warning('Missing security headers in IoT request', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            throw new HttpResponseException(response()->json([
                'error' => 'Missing required security headers.',
            ], 401));
        }

        // Find device by serial number
        $device = Device::where('serial_number', $deviceSerial)->first();

        if (!$device) {
            Log::warning('IoT device not found', [
                'serial_number' => $deviceSerial,
                'ip' => $request->ip(),
            ]);
            throw new HttpResponseException(response()->json([
                'error' => 'Device not found.',
            ], 401));
        }

        // Check device is active
        if (!$device->is_active) {
            Log::warning('Inactive device attempted access', [
                'device_id' => $device->id,
                'ip' => $request->ip(),
            ]);
            throw new HttpResponseException(response()->json([
                'error' => 'Device is inactive.',
            ], 403));
        }

        // Check token not expired
        if ($device->isTokenExpired()) {
            Log::warning('Expired device token attempted', [
                'device_id' => $device->id,
                'expired_at' => $device->token_expires_at,
                'ip' => $request->ip(),
            ]);
            throw new HttpResponseException(response()->json([
                'error' => 'Device token has expired. Please rotate token from admin panel.',
            ], 401));
        }

        // Validate signature
        $body = $request->getContent();
        
        if (!$this->tokenService->validateSignature($device, $timestamp, $signature, $body)) {
            Log::warning('Invalid signature in IoT request', [
                'device_id' => $device->id,
                'ip' => $request->ip(),
            ]);

            // Alert admin on repeated failures
            $this->checkForRepeatedFailures($request->ip());

            throw new HttpResponseException(response()->json([
                'error' => 'Invalid request signature.',
            ], 401));
        }

        // Update last seen
        $device->update(['last_seen_at' => now()]);

        return $device->fresh();
    }

    /**
     * Check for repeated authentication failures (potential attack)
     */
    private function checkForRepeatedFailures(string $ip): void
    {
        $failureKey = "auth_failures:iot:$ip";
        $failures = cache()->increment($failureKey, 1, now()->addHours(1));

        if ($failures === 50) {
            Log::critical('Repeated IoT authentication failures detected', [
                'ip' => $ip,
                'failures' => $failures,
            ]);

            // Could send alert to admins here
        }
    }

    private function resolvePunchType(int $staffId, Carbon $loggedAtLocal): string
    {
        [$dayStartUtc, $dayEndUtc] = $this->attendanceUtcRange($loggedAtLocal);

        $latestLog = AttendanceLog::where('user_id', $staffId)
            ->whereBetween('logged_at', [$dayStartUtc, $dayEndUtc])
            ->latest('logged_at')
            ->first();

        if (! $latestLog || $latestLog->punch_type === 'out') {
            return 'in';
        }

        return 'out';
    }

    private function attendanceTimezone(): string
    {
        return config('cleanflow.attendance_timezone', 'Asia/Manila');
    }

    private function attendanceUtcRange(?Carbon $localReference = null): array
    {
        $localReference = $localReference
            ? $localReference->copy()->timezone($this->attendanceTimezone())
            : Carbon::now($this->attendanceTimezone());

        return [
            $localReference->copy()->startOfDay()->utc(),
            $localReference->copy()->endOfDay()->utc(),
            $localReference,
        ];
    }

    private function resolveAttendanceStatus(?Carbon $loggedAtLocal, string $punchType): string
    {
        if (! $loggedAtLocal || $punchType !== 'in') {
            return 'present';
        }

        $cutoff = $loggedAtLocal->copy()->startOfDay()->setTime(8, 0);

        return $loggedAtLocal->greaterThan($cutoff) ? 'late' : 'present';
    }
}
