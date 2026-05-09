<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AttendanceLog;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AttendanceHelpers
{
    private function attendanceTimezone(): string
    {
        return config('cleanflow.attendance_timezone', 'Asia/Manila');
    }

    private function attendanceTimezoneOffset(): string
    {
        return Carbon::now($this->attendanceTimezone())->format('P');
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

    private function localDateToUtc(string $date, bool $endOfDay = false): Carbon
    {
        $localDate = Carbon::parse($date, $this->attendanceTimezone());

        return ($endOfDay ? $localDate->endOfDay() : $localDate->startOfDay())->utc();
    }

    private function formatAttendanceTime(?Carbon $timestamp): ?string
    {
        return $timestamp
            ? $timestamp->copy()->timezone($this->attendanceTimezone())->format('h:i A')
            : null;
    }

    private function attendanceStatusFor(?AttendanceLog $timeIn): string
    {
        return $timeIn
            ? $this->attendanceStatusFromTimestamp($timeIn->logged_at->copy()->timezone($this->attendanceTimezone()))
            : 'absent';
    }

    private function attendanceStatusFromTimestamp(?Carbon $loggedAtLocal, string $punchType = 'in'): string
    {
        if (! $loggedAtLocal) {
            return 'absent';
        }

        if ($punchType !== 'in') {
            return 'present';
        }

        $cutoff = $loggedAtLocal->copy()->startOfDay()->setTime(8, 0);

        return $loggedAtLocal->greaterThan($cutoff) ? 'late' : 'present';
    }

    private function attendanceSqlExpressions(): array
    {
        $driver = DB::connection()->getDriverName();
        $attendanceOffset = $this->attendanceTimezoneOffset();
        $timezone = str_replace("'", "''", $this->attendanceTimezone());

        return match ($driver) {
            'sqlite' => [
                'local_date' => "date(datetime(logged_at, '{$attendanceOffset}'))",
                'late_rank' => "MAX(CASE WHEN punch_type = 'in' AND time(datetime(logged_at, '{$attendanceOffset}')) > '08:00:00' THEN 2 WHEN punch_type = 'in' THEN 1 ELSE 0 END)",
                'late_condition' => "punch_type = 'in' AND time(datetime(logged_at, '{$attendanceOffset}')) > '08:00:00'",
                'present_condition' => "(punch_type != 'in' OR time(datetime(logged_at, '{$attendanceOffset}')) <= '08:00:00')",
            ],
            'pgsql' => [
                'local_date' => "DATE(timezone('{$timezone}', logged_at AT TIME ZONE 'UTC'))",
                'late_rank' => "MAX(CASE WHEN punch_type = 'in' AND CAST(timezone('{$timezone}', logged_at AT TIME ZONE 'UTC') AS time) > TIME '08:00:00' THEN 2 WHEN punch_type = 'in' THEN 1 ELSE 0 END)",
                'late_condition' => "punch_type = 'in' AND CAST(timezone('{$timezone}', logged_at AT TIME ZONE 'UTC') AS time) > TIME '08:00:00'",
                'present_condition' => "(punch_type != 'in' OR CAST(timezone('{$timezone}', logged_at AT TIME ZONE 'UTC') AS time) <= TIME '08:00:00')",
            ],
            default => [
                'local_date' => "DATE(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}'))",
                'late_rank' => "MAX(CASE WHEN punch_type = 'in' AND TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) > '08:00:00' THEN 2 WHEN punch_type = 'in' THEN 1 ELSE 0 END)",
                'late_condition' => "punch_type = 'in' AND TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) > '08:00:00'",
                'present_condition' => "(punch_type != 'in' OR TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) <= '08:00:00')",
            ],
        };
    }

    private function generateUniqueDeviceToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Device::where('api_token', $token)->exists());

        return $token;
    }

    private function buildAttendanceHistoryData(Request $request): array
    {
        $attendanceExpressions = $this->attendanceSqlExpressions();
        $localDateExpression = $attendanceExpressions['local_date'];
        $lateRankExpression = $attendanceExpressions['late_rank'];
        $lateCondition = $attendanceExpressions['late_condition'];
        $presentCondition = $attendanceExpressions['present_condition'];

        $query = AttendanceLog::with(['user', 'device'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'staff');
            });

        $dateFrom = null;
        $dateTo = null;

        if ($request->period) {
            $now = Carbon::now($this->attendanceTimezone());

            [$dateFrom, $dateTo] = match ($request->period) {
                'today'      => [$now->toDateString(), $now->toDateString()],
                'yesterday'  => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
                'this_week'  => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
                'last_week'  => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
                'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
                'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
                default      => [null, null],
            };
        }

        if ($request->staff_id) {
            $query->where('user_id', $request->staff_id);
        }

        $dateFrom = $request->date_from ?: $dateFrom;
        $dateTo = $request->date_to ?: $dateTo;

        if ($dateFrom) {
            $query->where('logged_at', '>=', $this->localDateToUtc($dateFrom));
        }

        if ($dateTo) {
            $query->where('logged_at', '<=', $this->localDateToUtc($dateTo, true));
        }

        if ($request->status === 'late') {
            $query->whereRaw($lateCondition);
        } elseif ($request->status === 'present') {
            $query->whereRaw($presentCondition);
        }

        if ($request->punch_type) {
            $query->where('punch_type', $request->punch_type);
        }

        $logs = $query->orderByDesc('logged_at')->paginate(15, ['*'], 'logs_page')->withQueryString();
        $logs->getCollection()->transform(function (AttendanceLog $log) {
            $loggedAtLocal = $log->logged_at->copy()->timezone($this->attendanceTimezone());
            $log->display_logged_at_date = $loggedAtLocal->format('M d, Y');
            $log->display_logged_at_time = $loggedAtLocal->format('h:i:s A');
            $log->display_status = $this->attendanceStatusFromTimestamp($loggedAtLocal, $log->punch_type);

            return $log;
        });

        $summaryQuery = AttendanceLog::with('user')
            ->whereHas('user', function ($q) {
                $q->where('role', 'staff');
            })
            ->selectRaw("user_id, {$localDateExpression} as date,
                MIN(CASE WHEN punch_type = 'in' THEN logged_at END) as time_in,
                MAX(CASE WHEN punch_type = 'out' THEN logged_at END) as time_out,
                {$lateRankExpression} as status_rank")
            ->groupBy('user_id', DB::raw($localDateExpression));

        if ($request->staff_id) {
            $summaryQuery->where('user_id', $request->staff_id);
        }

        if ($dateFrom) {
            $summaryQuery->where('logged_at', '>=', $this->localDateToUtc($dateFrom));
        }

        if ($dateTo) {
            $summaryQuery->where('logged_at', '<=', $this->localDateToUtc($dateTo, true));
        }

        if ($request->status === 'late') {
            $summaryQuery->havingRaw("{$lateRankExpression} = 2");
        } elseif ($request->status === 'present') {
            $summaryQuery->havingRaw("{$lateRankExpression} = 1");
        }

        $summaries = $summaryQuery->orderByDesc('date')->paginate(15, ['*'], 'summaries_page')->withQueryString();
        $summaries->getCollection()->transform(function ($summary) {
            $timeInLocal = $summary->time_in
                ? Carbon::parse($summary->time_in, 'UTC')->timezone($this->attendanceTimezone())
                : null;
            $timeOutLocal = $summary->time_out
                ? Carbon::parse($summary->time_out, 'UTC')->timezone($this->attendanceTimezone())
                : null;

            $summary->display_date = Carbon::parse($summary->date, $this->attendanceTimezone());
            $summary->display_time_in = $timeInLocal?->format('h:i A');
            $summary->display_time_out = $timeOutLocal?->format('h:i A');
            $summary->display_status = $timeInLocal
                ? $this->attendanceStatusFromTimestamp($timeInLocal, 'in')
                : 'unknown';
            $summary->hours_worked = null;

            if ($timeInLocal && $timeOutLocal) {
                $diff = $timeInLocal->diff($timeOutLocal);
                $summary->hours_worked = $diff->h.'h '.$diff->i.'m';
            }

            return $summary;
        });

        $staffList = \App\Models\User::where('role', 'staff')->get();

        $totalLogs = AttendanceLog::whereHas('user', function ($q) {
            $q->where('role', 'staff');
        })->count();

        $totalLate = AttendanceLog::whereHas('user', function ($q) {
            $q->where('role', 'staff');
        })->whereRaw($lateCondition)->count();

        return compact('logs', 'summaries', 'staffList', 'totalLogs', 'totalLate');
    }
}
