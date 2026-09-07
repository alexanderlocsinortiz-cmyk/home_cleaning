<?php

namespace App\Http\Controllers;

use App\Models\AccessRestrictionHistory;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\SecurityEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $attendanceTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $todayStartUtc = Carbon::now($attendanceTimezone)->startOfDay()->utc();
        $todayEndUtc = Carbon::now($attendanceTimezone)->endOfDay()->utc();

        $bookingLogsQuery = $this->bookingLogsQuery($filters);
        $attendanceLogsQuery = $this->attendanceLogsQuery($filters);
        $adminLogsQuery = $this->adminLogsQuery($filters);
        $securityLogsQuery = $this->securityLogsQuery($filters);

        $bookingLogs = (clone $bookingLogsQuery)
            ->latest()
            ->paginate($filters['source'] === 'bookings' ? 15 : 5, ['*'], 'booking_page')
            ->withQueryString();

        $attendanceLogs = (clone $attendanceLogsQuery)
            ->latest('logged_at')
            ->latest()
            ->paginate($filters['source'] === 'attendance' ? 15 : 5, ['*'], 'attendance_page')
            ->withQueryString();

        $adminLogs = (clone $adminLogsQuery)
            ->latest()
            ->paginate($filters['source'] === 'admin' ? 15 : 5, ['*'], 'admin_page')
            ->withQueryString();

        $securityLogs = (clone $securityLogsQuery)
            ->latest()
            ->paginate($filters['source'] === 'admin' ? 15 : 5, ['*'], 'security_page')
            ->withQueryString();

        $stats = [
            'total' => BookingActivityLog::count() + AttendanceLog::count() + AccessRestrictionHistory::count() + SecurityEvent::count(),
            'attendance_today' => AttendanceLog::where('punch_type', 'in')->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])->count(),
            'late_arrivals' => AttendanceLog::where('punch_type', 'in')->where('status', 'late')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            'completed_bookings' => Booking::where('status', 'completed')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            'booking_filtered' => (clone $bookingLogsQuery)->count(),
            'attendance_filtered' => (clone $attendanceLogsQuery)->count(),
            'admin_filtered' => (clone $adminLogsQuery)->count() + (clone $securityLogsQuery)->count(),
        ];

        $actions = $this->actionOptions();
        $staff = User::where('role', 'staff')->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.logs', compact('bookingLogs', 'attendanceLogs', 'adminLogs', 'securityLogs', 'actions', 'staff', 'filters', 'stats'));
    }

    public function export(Request $request, string $source, string $format)
    {
        $filters = $this->filters($request);
        $rows = $this->exportRows($source, $filters);
        $title = str($source)->replace('_', ' ')->title().' Logs';
        $timestamp = now()->format('Ymd_His');

        if ($format === 'pdf') {
            return response($this->simplePdf($title, $rows), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$source.'_logs_'.$timestamp.'.pdf"');
        }

        return response($this->excelTable($title, $rows), 200)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$source.'_logs_'.$timestamp.'.xls"');
    }

    private function filters(Request $request): array
    {
        return [
            'source' => in_array($request->get('source'), ['all', 'bookings', 'attendance', 'admin'], true)
                ? $request->get('source')
                : 'all',
            'search' => trim((string) $request->get('search', '')),
            'action' => (string) $request->get('action', ''),
            'staff_id' => (int) $request->get('staff_id', 0),
        ];
    }

    private function bookingLogsQuery(array $filters): Builder
    {
        return BookingActivityLog::query()
            ->with(['booking.user', 'actor'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $bookingIdSearch = preg_replace('/\D/', '', $search);

                $query->where(function ($query) use ($search, $bookingIdSearch) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($bookingQuery) use ($search, $bookingIdSearch) {
                            $bookingQuery->where('barangay', 'like', "%{$search}%")
                                ->when($bookingIdSearch !== '', fn ($query) => $query->orWhere('id', (int) $bookingIdSearch))
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['staff_id'] > 0, fn ($query) => $query->where('actor_id', $filters['staff_id']));
    }

    private function attendanceLogsQuery(array $filters): Builder
    {
        return AttendanceLog::query()
            ->with(['user', 'device'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('punch_type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('device', function ($deviceQuery) use ($search) {
                            $deviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['action'] === 'time_in', fn ($query) => $query->where('punch_type', 'in'))
            ->when($filters['action'] === 'time_out', fn ($query) => $query->where('punch_type', 'out'))
            ->when(in_array($filters['action'], ['present', 'late'], true), fn ($query) => $query->where('status', $filters['action']))
            ->when($filters['staff_id'] > 0, fn ($query) => $query->where('user_id', $filters['staff_id']));
    }

    private function adminLogsQuery(array $filters): Builder
    {
        return AccessRestrictionHistory::query()
            ->with(['targetUser', 'actorUser'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('target_name', 'like', "%{$search}%")
                        ->orWhere('target_email', 'like', "%{$search}%")
                        ->orWhere('target_role', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('actorUser', function ($actorQuery) use ($search) {
                            $actorQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['staff_id'] > 0, fn ($query) => $query->where(function ($query) use ($filters) {
                $query->where('actor_user_id', $filters['staff_id'])
                    ->orWhere('target_user_id', $filters['staff_id']);
            }));
    }

    private function securityLogsQuery(array $filters): Builder
    {
        return SecurityEvent::query()
            ->with('user')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('event', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['action'] !== '', fn ($query) => $query->where('event', $filters['action']))
            ->when($filters['staff_id'] > 0, fn ($query) => $query->where('user_id', $filters['staff_id']));
    }

    private function actionOptions(): Collection
    {
        return collect()
            ->merge(BookingActivityLog::query()->select('action')->whereNotNull('action')->distinct()->pluck('action'))
            ->merge(AccessRestrictionHistory::query()->select('action')->whereNotNull('action')->distinct()->pluck('action'))
            ->merge(SecurityEvent::query()->select('event')->whereNotNull('event')->distinct()->pluck('event'))
            ->merge(['time_in', 'time_out', 'present', 'late'])
            ->unique()
            ->sort()
            ->values();
    }

    private function exportRows(string $source, array $filters): array
    {
        return match ($source) {
            'bookings' => $this->bookingLogsQuery($filters)->latest()->limit(5000)->get()->map(fn ($log) => [
                'Date' => optional($log->created_at)->format('Y-m-d H:i:s'),
                'Action' => str($log->action)->replace('_', ' ')->title(),
                'Description' => $log->description,
                'Booking' => $log->booking_id ? 'CF-'.str_pad($log->booking_id, 5, '0', STR_PAD_LEFT) : '',
                'Client' => $log->booking?->user?->display_name,
                'Actor' => $log->actor_name ?? $log->actor?->display_name ?? 'System',
            ])->all(),
            'attendance' => $this->attendanceLogsQuery($filters)->latest('logged_at')->limit(5000)->get()->map(function ($log) {
                $loggedAt = $log->logged_at ?? $log->created_at;
                $loggedAt = $loggedAt?->copy()->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'));

                return [
                    'Date' => $loggedAt?->format('Y-m-d H:i:s'),
                    'Action' => 'Time '.ucfirst($log->punch_type),
                    'Status' => str($log->status)->replace('_', ' ')->title(),
                    'Staff' => $log->user?->display_name,
                    'Email' => $log->user?->email,
                    'Source' => str($log->source)->replace('_', ' ')->title(),
                    'Device' => $log->device?->name,
                ];
            })->all(),
            'admin' => $this->adminLogsQuery($filters)->latest()->limit(5000)->get()->map(fn ($log) => [
                'Date' => optional($log->created_at)->format('Y-m-d H:i:s'),
                'Action' => str($log->action)->replace('_', ' ')->title(),
                'Target' => $log->target_name,
                'Target Email' => $log->target_email,
                'Target Role' => ucfirst($log->target_role),
                'Actor' => $log->actorUser?->display_name ?? 'System',
                'Reason' => $log->reason,
            ])->concat($this->securityLogsQuery($filters)->latest()->limit(5000)->get()->map(fn ($log) => [
                'Date' => optional($log->created_at)->format('Y-m-d H:i:s'),
                'Action' => str($log->event)->replace('_', ' ')->title(),
                'Target' => $log->user?->display_name ?? 'System / Device',
                'Target Email' => $log->user?->email,
                'Target Role' => $log->user?->role ? ucfirst($log->user->role) : '-',
                'Actor' => $log->user?->display_name ?? 'System',
                'Reason' => collect($log->metadata ?? [])->map(fn ($value, $key) => $key.': '.$value)->implode(', '),
            ]))->sortByDesc('Date')->values()->all(),
        };
    }

    private function excelTable(string $title, array $rows): string
    {
        $headers = $rows ? array_keys($rows[0]) : ['Message'];
        $rows = $rows ?: [['Message' => 'No records found']];

        return view('admin.logs-export-excel', compact('title', 'headers', 'rows'))->render();
    }

    private function simplePdf(string $title, array $rows): string
    {
        $lines = [$title, 'Generated: '.now()->format('Y-m-d H:i:s'), ''];

        foreach ($rows ?: [['Message' => 'No records found']] as $row) {
            $lines[] = collect($row)->map(fn ($value, $key) => $key.': '.(string) $value)->implode(' | ');
        }

        $pages = array_chunk($lines, 42);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pageRefs = [];
        $nextObjectId = 4;

        foreach ($pages as $pageLines) {
            $content = "BT\n/F1 9 Tf\n50 790 Td\n";

            foreach ($pageLines as $line) {
                $content .= '('.$this->escapePdfText(str($line)->limit(150, '')->toString()).") Tj\n0 -16 Td\n";
            }

            $content .= 'ET';
            $contentId = $nextObjectId++;
            $pageId = $nextObjectId++;
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
            $pageRefs[] = "{$pageId} 0 R";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs).'] /Count '.count($pageRefs).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $objectCount = count($objects);
        $pdf .= "xref\n0 ".($objectCount + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".($objectCount + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
    }
}
