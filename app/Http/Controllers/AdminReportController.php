<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function reports(Request $request)
    {
        [$filters, $dateRange] = $this->resolveReportFilters($request);

        $bookingScope = fn (Builder $query) => $this->applyReportDateRange($query, $dateRange);

        $totalBookings = $bookingScope(Booking::query())->count();
        $completedBookings = $bookingScope(Booking::where('status', 'completed'))->count();
        $pendingBookings = $bookingScope(Booking::where('status', 'pending'))->count();
        $cancelledBookings = $bookingScope(Booking::where('status', 'cancelled'))->count();
        $confirmedBookings = $bookingScope(Booking::where('status', 'confirmed'))->count();
        $inProgressBookings = $bookingScope(Booking::where('status', 'in_progress'))->count();
        $totalRevenue = $bookingScope(Booking::where('status', 'completed'))->sum('price');
        $averageCompletedRevenue = $completedBookings > 0
            ? round((float) $totalRevenue / $completedBookings, 2)
            : 0.0;
        $cancellationRate = $totalBookings > 0
            ? round(($cancelledBookings / $totalBookings) * 100, 1)
            : 0.0;
        $pendingOlderThanDay = $bookingScope(Booking::where('status', 'pending')->where('created_at', '<=', now()->subDay()))->count();
        $unassignedActiveBookings = $bookingScope(Booking::whereIn('status', ['pending', 'confirmed', 'in_progress'])->whereNull('staff_id'))->count();

        $revenueByType = $bookingScope(Booking::query())
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->where('bookings.status', 'completed')
            ->where('services.is_active', true)
            ->selectRaw('services.slug as service_type, services.name as service_name, COUNT(bookings.id) as total, SUM(bookings.price) as revenue')
            ->groupBy('services.slug', 'services.name')
            ->orderByDesc('revenue')
            ->get();

        $bookingsByType = $bookingScope(Booking::query())
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->where('services.is_active', true)
            ->selectRaw('services.slug as service_type, services.name as service_name, COUNT(bookings.id) as total')
            ->groupBy('services.slug', 'services.name')
            ->orderByDesc('total')
            ->get();

        $statusSummary = [
            'completed' => $completedBookings,
            'confirmed' => $confirmedBookings,
            'pending' => $pendingBookings,
            'cancelled' => $cancelledBookings,
            'in_progress' => $inProgressBookings,
        ];

        $invalidServiceBookings = Booking::query()
            ->whereNull('service_id')
            ->count();

        $advancedAnalytics = $this->buildAdvancedAnalytics();
        $staffPerformance = $advancedAnalytics['staffPerformance'];

        $recentBookings = $bookingScope(Booking::with(['user', 'staff', 'service']))
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $monthlyBookings = $advancedAnalytics['monthlyBookingTrend'];

        $reportInsights = [
            'average_completed_revenue' => $averageCompletedRevenue,
            'cancellation_rate' => $cancellationRate,
            'pending_older_than_day' => $pendingOlderThanDay,
            'unassigned_active_bookings' => $unassignedActiveBookings,
            'date_label' => $dateRange['label'],
        ];

        return view('admin.reports', array_merge(compact(
            'totalBookings', 'completedBookings', 'pendingBookings',
            'cancelledBookings', 'confirmedBookings', 'inProgressBookings',
            'totalRevenue', 'revenueByType', 'bookingsByType',
            'statusSummary', 'invalidServiceBookings',
            'staffPerformance', 'recentBookings', 'monthlyBookings',
            'filters', 'reportInsights'
        ), $advancedAnalytics));
    }

    public function export(Request $request, string $format)
    {
        [, $dateRange] = $this->resolveReportFilters($request);
        $rows = $this->reportExportRows($dateRange);
        $title = 'Reports & Analytics - '.$dateRange['label'];
        $timestamp = Carbon::now($this->reportTimezone())->format('Ymd_His');

        if ($format === 'pdf') {
            return response($this->simplePdf($title, $rows), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="reports_'.$timestamp.'.pdf"');
        }

        return response($this->excelTable($title, $rows), 200)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="reports_'.$timestamp.'.xls"');
    }

    public function providerPayouts(Request $request)
    {
        [$filters, $baseQuery] = $this->providerPayoutQuery($request);

        $summaryRows = (clone $baseQuery)->with(['payment', 'payout'])->get(['id']);

        $payoutSummary = [
            'gross' => round((float) $summaryRows->sum('provider_gross_amount'), 2),
            'commission' => round((float) $summaryRows->sum('platform_commission_amount'), 2),
            'payout' => round((float) $summaryRows->sum('provider_payout_amount'), 2),
            'cash_collected' => round((float) $summaryRows->where('payment_method', 'on_site_cash')->sum('cash_collected_amount'), 2),
            'commission_due' => round((float) $summaryRows->where('provider_commission_status', 'unpaid')->sum('provider_commission_due'), 2),
            'commission_paid' => round((float) $summaryRows->where('provider_commission_status', 'paid')->sum('provider_commission_due'), 2),
            'count' => $summaryRows->count(),
            'status_counts' => collect(Booking::providerPayoutStatuses())
                ->mapWithKeys(fn (string $status) => [$status => $summaryRows->where('provider_payout_status', $status)->count()])
                ->all(),
            'commission_status_counts' => collect(Booking::providerCommissionStatuses())
                ->mapWithKeys(fn (string $status) => [$status => $summaryRows->where('provider_commission_status', $status)->count()])
                ->all(),
        ];

        $payoutRows = (clone $baseQuery)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $providers = CleanerApplication::query()
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        return view('admin.provider-payouts', compact('filters', 'payoutRows', 'payoutSummary', 'providers'));
    }

    public function exportProviderPayouts(Request $request)
    {
        [$filters, $baseQuery] = $this->providerPayoutQuery($request);
        $filename = 'provider_payouts_'.Carbon::now($this->reportTimezone())->format('Ymd_His').'.csv';
        $reportTimezone = $this->reportTimezone();

        return response()->streamDownload(function () use ($baseQuery, $reportTimezone) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Booking',
                'Provider',
                'Customer',
                'Service',
                'Service Date',
                'Status',
                'Gross',
                'Commission',
                'Provider Payout',
                'Payout Reference',
                'Payout Paid At',
                'Payment Flow',
                'Cash Collected',
                'Commission Collection Status',
                'Commission Reference',
                'Commission Paid At',
                'Latest Audit Status',
                'Latest Audit By',
                'Latest Audit At',
                'Has Current Proof',
            ]);

            (clone $baseQuery)
                ->orderByDesc('scheduled_date')
                ->orderByDesc('id')
                ->chunk(200, function ($bookings) use ($handle, $reportTimezone) {
                    foreach ($bookings as $booking) {
                        $latestTransaction = $booking->providerPayoutTransactions->first();

                        fputcsv($handle, [
                            'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                            $booking->cleanerApplication?->business_name ?? 'Unknown provider',
                            $booking->user?->display_name ?? 'Unknown customer',
                            $booking->service_label,
                            $booking->scheduled_date?->format('Y-m-d'),
                            Booking::providerPayoutStatusLabel($booking->provider_payout_status),
                            number_format((float) $booking->provider_gross_amount, 2, '.', ''),
                            number_format((float) $booking->platform_commission_amount, 2, '.', ''),
                            number_format((float) $booking->provider_payout_amount, 2, '.', ''),
                            $booking->provider_payout_reference,
                            $booking->provider_payout_paid_at?->copy()->timezone($reportTimezone)->format('Y-m-d H:i:s'),
                            $booking->payment?->method === 'on_site_cash' ? 'Cash commission collection' : 'Provider payout',
                            number_format((float) $booking->cash_collected_amount, 2, '.', ''),
                            Booking::providerCommissionStatusLabel($booking->provider_commission_status),
                            $booking->provider_commission_reference,
                            $booking->provider_commission_paid_at?->copy()->timezone($reportTimezone)->format('Y-m-d H:i:s'),
                            $latestTransaction
                                ? Booking::providerPayoutStatusLabel($latestTransaction->from_status).' -> '.Booking::providerPayoutStatusLabel($latestTransaction->to_status)
                                : '',
                            $latestTransaction?->processor?->full_name ?? '',
                            $latestTransaction?->created_at?->copy()->timezone($reportTimezone)->format('Y-m-d H:i:s'),
                            filled($booking->provider_payout_proof_path) ? 'Yes' : 'No',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function providerPerformance(Request $request)
    {
        $filters = [
            'provider_id' => (int) $request->get('provider_id', 0),
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];

        $dateFrom = $this->parseReportDate($filters['date_from'])?->startOfDay();
        $dateTo = $this->parseReportDate($filters['date_to'])?->endOfDay();

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
            $filters['date_from'] = $dateFrom->toDateString();
            $filters['date_to'] = $dateTo->toDateString();
        }

        $providersQuery = CleanerApplication::query()
            ->with(['documents', 'bookings' => function ($query) use ($dateFrom, $dateTo) {
                $query
                    ->with(['rating'])
                    ->when($dateFrom, fn ($query) => $query->whereDate('scheduled_date', '>=', $dateFrom->toDateString()))
                    ->when($dateTo, fn ($query) => $query->whereDate('scheduled_date', '<=', $dateTo->toDateString()));
            }])
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->when($filters['provider_id'] > 0, fn ($query) => $query->where('id', $filters['provider_id']))
            ->orderBy('business_name');

        $providerRows = $providersQuery->get()->map(function (CleanerApplication $provider) {
            $bookings = $provider->bookings;
            $ratings = $bookings->pluck('rating')->filter();
            $assignedCount = $bookings->count();
            $completedCount = $bookings->where('status', 'completed')->count();
            $acceptedCount = $bookings->where('provider_assignment_status', 'accepted')->count();
            $declinedCount = $bookings->where('provider_assignment_status', 'declined')->count();
            $openDisputeCount = $bookings->where('dispute_status', 'open')->count();
            $lateCount = $bookings
                ->whereIn('on_time_status', ['started_late', 'completed_late', 'late'])
                ->count();

            $provider->performance = [
                'assigned' => $assignedCount,
                'accepted' => $acceptedCount,
                'declined' => $declinedCount,
                'pending_response' => $bookings->where('provider_assignment_status', 'pending')->count(),
                'active' => $bookings->whereIn('status', Booking::ACTIVE_SCHEDULE_STATUSES)->count(),
                'completed' => $completedCount,
                'cancelled' => $bookings->where('status', 'cancelled')->count(),
                'open_disputes' => $openDisputeCount,
                'disputed' => $bookings->whereNotNull('dispute_status')->count(),
                'avg_rating' => $ratings->count() > 0 ? round((float) $ratings->avg('stars'), 1) : null,
                'rating_count' => $ratings->count(),
                'on_time' => $bookings->where('on_time_status', 'on_time')->count(),
                'late' => $lateCount,
                'gross' => round((float) $bookings->sum('provider_gross_amount'), 2),
                'commission' => round((float) $bookings->sum('platform_commission_amount'), 2),
                'payout' => round((float) $bookings->sum('provider_payout_amount'), 2),
                'acceptance_rate' => $assignedCount > 0 ? round(($acceptedCount / $assignedCount) * 100, 1) : 0.0,
                'completion_rate' => $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100, 1) : 0.0,
                'dispute_rate' => $assignedCount > 0 ? round(($openDisputeCount / $assignedCount) * 100, 1) : 0.0,
                'late_rate' => $completedCount > 0 ? round(($lateCount / $completedCount) * 100, 1) : 0.0,
                'payout_status_counts' => collect(Booking::providerPayoutStatuses())
                    ->mapWithKeys(fn (string $status) => [$status => $bookings->where('provider_payout_status', $status)->count()])
                    ->all(),
            ];

            return $provider;
        })->values();

        $providers = CleanerApplication::query()
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $summary = [
            'providers' => $providerRows->count(),
            'assigned' => $providerRows->sum(fn (CleanerApplication $provider) => $provider->performance['assigned']),
            'completed' => $providerRows->sum(fn (CleanerApplication $provider) => $provider->performance['completed']),
            'open_disputes' => $providerRows->sum(fn (CleanerApplication $provider) => $provider->performance['open_disputes']),
            'commission' => round((float) $providerRows->sum(fn (CleanerApplication $provider) => $provider->performance['commission']), 2),
        ];

        return view('admin.provider-performance', compact('filters', 'providerRows', 'providers', 'summary'));
    }

    private function providerPayoutQuery(Request $request): array
    {
        $filters = [
            'provider_id' => (int) $request->get('provider_id', 0),
            'payout_status' => in_array($request->get('payout_status'), Booking::providerPayoutStatuses(), true)
                ? $request->get('payout_status')
                : '',
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];

        $dateFrom = $this->parseReportDate($filters['date_from'])?->startOfDay();
        $dateTo = $this->parseReportDate($filters['date_to'])?->endOfDay();

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
            $filters['date_from'] = $dateFrom->toDateString();
            $filters['date_to'] = $dateTo->toDateString();
        }

        $query = Booking::query()
            ->with(['user', 'cleanerApplication', 'service', 'payment', 'payout', 'providerPayoutTransactions.processor'])
            ->whereNotNull('cleaner_application_id')
            ->whereHas('payout')
            ->when($filters['provider_id'] > 0, fn (Builder $query) => $query->where('cleaner_application_id', $filters['provider_id']))
            ->when($filters['payout_status'] !== '', fn (Builder $query) => $query->whereHas('payout', fn (Builder $payout) => $payout->where('provider_payout_status', $filters['payout_status'])))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('scheduled_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('scheduled_date', '<=', $dateTo->toDateString()));

        return [$filters, $query];
    }

    private function resolveReportFilters(Request $request): array
    {
        $period = in_array($request->get('period'), ['all', 'today', 'this_week', 'this_month', 'last_month', 'custom'], true)
            ? $request->get('period')
            : 'all';

        $filters = [
            'period' => $period,
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];

        $start = null;
        $end = null;
        $label = 'All time';
        $reportNow = Carbon::now($this->reportTimezone());

        if ($period === 'today') {
            $start = $reportNow->copy()->startOfDay();
            $end = $reportNow->copy()->endOfDay();
            $label = 'Today';
        } elseif ($period === 'this_week') {
            $start = $reportNow->copy()->startOfWeek();
            $end = $reportNow->copy()->endOfWeek();
            $label = 'This week';
        } elseif ($period === 'this_month') {
            $start = $reportNow->copy()->startOfMonth();
            $end = $reportNow->copy()->endOfMonth();
            $label = 'This month';
        } elseif ($period === 'last_month') {
            $start = $reportNow->copy()->subMonthNoOverflow()->startOfMonth();
            $end = $reportNow->copy()->subMonthNoOverflow()->endOfMonth();
            $label = 'Last month';
        } elseif ($period === 'custom') {
            $start = $this->parseReportDate($filters['date_from'])?->startOfDay();
            $end = $this->parseReportDate($filters['date_to'])?->endOfDay();

            if ($start && $end && $start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $label = trim(($start?->format('M d, Y') ?? 'Any start').' - '.($end?->format('M d, Y') ?? 'Any end'));
        }

        return [$filters, compact('start', 'end', 'label')];
    }

    private function parseReportDate(string $value): ?Carbon
    {
        if (! filled($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, $this->reportTimezone());

            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyReportDateRange(Builder $query, array $dateRange): Builder
    {
        $start = ($dateRange['start'] ?? null)?->copy()->utc();
        $end = ($dateRange['end'] ?? null)?->copy()->utc();

        return $query
            ->when($start, fn (Builder $query) => $query->where('bookings.created_at', '>=', $start))
            ->when($end, fn (Builder $query) => $query->where('bookings.created_at', '<=', $end));
    }

    private function reportTimezone(): string
    {
        return config('cleanflow.attendance_timezone', 'Asia/Manila');
    }

    private function reportExportRows(array $dateRange): array
    {
        $bookingScope = fn (Builder $query) => $this->applyReportDateRange($query, $dateRange);

        $totalBookings = $bookingScope(Booking::query())->count();
        $completedBookings = $bookingScope(Booking::where('status', 'completed'))->count();
        $pendingBookings = $bookingScope(Booking::where('status', 'pending'))->count();
        $cancelledBookings = $bookingScope(Booking::where('status', 'cancelled'))->count();
        $confirmedBookings = $bookingScope(Booking::where('status', 'confirmed'))->count();
        $inProgressBookings = $bookingScope(Booking::where('status', 'in_progress'))->count();
        $totalRevenue = (float) $bookingScope(Booking::where('status', 'completed'))->sum('price');

        $rows = [
            ['Section' => 'Overview', 'Metric' => 'Selected Range', 'Value' => $dateRange['label']],
            ['Section' => 'Overview', 'Metric' => 'Total Bookings', 'Value' => $totalBookings],
            ['Section' => 'Overview', 'Metric' => 'Completed', 'Value' => $completedBookings],
            ['Section' => 'Overview', 'Metric' => 'Pending', 'Value' => $pendingBookings],
            ['Section' => 'Overview', 'Metric' => 'Confirmed', 'Value' => $confirmedBookings],
            ['Section' => 'Overview', 'Metric' => 'In Progress', 'Value' => $inProgressBookings],
            ['Section' => 'Overview', 'Metric' => 'Cancelled', 'Value' => $cancelledBookings],
            ['Section' => 'Overview', 'Metric' => 'Total Revenue', 'Value' => 'PHP '.number_format($totalRevenue, 2)],
            ['Section' => 'Overview', 'Metric' => 'Average Completed Revenue', 'Value' => 'PHP '.number_format($completedBookings > 0 ? $totalRevenue / $completedBookings : 0, 2)],
        ];

        $statusRows = [
            'Completed' => $completedBookings,
            'Confirmed' => $confirmedBookings,
            'Pending' => $pendingBookings,
            'In Progress' => $inProgressBookings,
            'Cancelled' => $cancelledBookings,
        ];

        foreach ($statusRows as $status => $count) {
            $rows[] = [
                'Section' => 'Booking Status',
                'Metric' => $status,
                'Value' => $count.' booking'.($count === 1 ? '' : 's'),
            ];
        }

        $revenueByType = $bookingScope(Booking::query())
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->where('bookings.status', 'completed')
            ->where('services.is_active', true)
            ->selectRaw('services.name as service_name, COUNT(bookings.id) as total, SUM(bookings.price) as revenue')
            ->groupBy('services.name')
            ->orderByDesc('revenue')
            ->get();

        foreach ($revenueByType as $type) {
            $rows[] = [
                'Section' => 'Revenue by Service',
                'Metric' => $type->service_name.' ('.$type->total.' completed)',
                'Value' => 'PHP '.number_format((float) $type->revenue, 2),
            ];
        }

        $recentBookings = $bookingScope(Booking::with(['user', 'staff', 'service']))
            ->orderByDesc('created_at')
            ->take(25)
            ->get();

        foreach ($recentBookings as $booking) {
            $rows[] = [
                'Section' => 'Recent Bookings',
                'Metric' => 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' - '.$booking->service_label,
                'Value' => trim(($booking->user?->display_name ?? 'Unknown client').' | '.$booking->status.' | PHP '.number_format((float) $booking->price, 2)),
            ];
        }

        return $rows;
    }

    private function excelTable(string $title, array $rows): string
    {
        $headers = $rows ? array_keys($rows[0]) : ['Message'];
        $rows = $rows ?: [['Message' => 'No report data found']];

        return view('admin.reports-export-excel', compact('title', 'headers', 'rows'))->render();
    }

    private function simplePdf(string $title, array $rows): string
    {
        $lines = [$title, 'Generated: '.Carbon::now($this->reportTimezone())->format('Y-m-d H:i:s'), ''];

        foreach ($rows ?: [['Message' => 'No report data found']] as $row) {
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

    private function monthlyBookingsMonthExpression(): string
    {
        return match (Booking::query()->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            default => 'MONTH(created_at)',
        };
    }

    private function buildAdvancedAnalytics(): array
    {
        $reportTimezone = $this->reportTimezone();
        $now = Carbon::now($reportTimezone);
        $monthBuckets = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => $now->copy()->startOfMonth()->subMonths($monthsAgo))
            ->values();

        $rangeStart = $monthBuckets->first()->copy()->startOfMonth();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subMonth()->endOfMonth();

        $bookingsInRange = Booking::query()
            ->where('created_at', '>=', $rangeStart->copy()->utc())
            ->get(['id', 'status', 'price', 'created_at', 'scheduled_date', 'scheduled_time']);

        $monthlyBookingTrend = $monthBuckets->map(function (Carbon $month) use ($bookingsInRange, $reportTimezone) {
            $monthBookings = $bookingsInRange->filter(fn (Booking $booking) => $booking->created_at?->copy()->timezone($reportTimezone)->format('Y-m') === $month->format('Y-m'));
            $completedMonthBookings = $monthBookings->where('status', 'completed');

            return (object) [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'total' => $monthBookings->count(),
                'completed' => $completedMonthBookings->count(),
                'revenue' => round((float) $completedMonthBookings->sum('price'), 2),
                'completion_rate' => $monthBookings->count() > 0
                    ? round(($completedMonthBookings->count() / $monthBookings->count()) * 100, 1)
                    : 0.0,
            ];
        })->values();

        $previousMonthTotal = null;
        $monthlyBookingTrend = $monthlyBookingTrend->map(function (object $month) use (&$previousMonthTotal) {
            $month->growth = $previousMonthTotal === null
                ? null
                : ($previousMonthTotal > 0
                    ? round((($month->total - $previousMonthTotal) / $previousMonthTotal) * 100, 1)
                    : ($month->total > 0 ? 100.0 : 0.0));
            $previousMonthTotal = $month->total;

            return $month;
        })->values();

        $bookingsForDemand = Booking::query()
            ->whereDate('scheduled_date', '>=', $rangeStart->toDateString())
            ->get(['id', 'status', 'scheduled_date', 'scheduled_time']);

        $timeSlotTrends = $bookingsForDemand
            ->filter(fn (Booking $booking) => filled($booking->scheduled_time))
            ->groupBy(fn (Booking $booking) => Carbon::parse($booking->scheduled_time)->format('g:i A'))
            ->map(function ($slotBookings, string $label) {
                $bookings = collect($slotBookings);

                return (object) [
                    'label' => $label,
                    'total' => $bookings->count(),
                    'completed' => $bookings->where('status', 'completed')->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(5);

        $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $weekdayTrends = collect($weekdayOrder)->map(function (string $weekday) use ($bookingsForDemand, $reportTimezone) {
            $weekdayBookings = $bookingsForDemand->filter(
                fn (Booking $booking) => Carbon::parse($booking->scheduled_date->toDateString(), $reportTimezone)->format('l') === $weekday
            );

            return (object) [
                'label' => $weekday,
                'short_label' => substr($weekday, 0, 3),
                'total' => $weekdayBookings->count(),
            ];
        })->values();

        $ratingsInRange = Rating::query()
            ->where('created_at', '>=', $rangeStart->copy()->utc())
            ->get(['id', 'stars', 'created_at']);

        $satisfactionTrend = $monthBuckets->map(function (Carbon $month) use ($ratingsInRange, $reportTimezone) {
            $monthRatings = $ratingsInRange->filter(fn (Rating $rating) => $rating->created_at?->copy()->timezone($reportTimezone)->format('Y-m') === $month->format('Y-m'));
            $reviewCount = $monthRatings->count();
            $positiveReviews = $monthRatings->filter(fn (Rating $rating) => (int) $rating->stars >= 4)->count();

            return (object) [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'average' => $reviewCount > 0 ? round((float) $monthRatings->avg('stars'), 1) : null,
                'reviews' => $reviewCount,
                'positive_share' => $reviewCount > 0 ? round(($positiveReviews / $reviewCount) * 100, 1) : 0.0,
            ];
        })->values();

        $overallRatingStats = DB::table('ratings')->selectRaw('COUNT(*) as total, AVG(stars) as avg_stars')->first();
        $overallReviewCount = (int) ($overallRatingStats->total ?? 0);
        $overallAvgStars = $overallReviewCount > 0 ? round((float) ($overallRatingStats->avg_stars ?? 0), 1) : null;
        $currentMonthAverageRating = $satisfactionTrend->last()->average;
        $previousMonthAverageRating = $satisfactionTrend->slice(-2, 1)->first()->average ?? null;

        $staffPerformanceBookings = Booking::query()
            ->with(['rating', 'staffAssignments:id,booking_id,staff_id'])
            ->get(['id', 'staff_id', 'status', 'price', 'scheduled_date']);

        $staffPerformance = User::where('role', 'staff')
            ->get()
            ->map(function (User $staff) use ($staffPerformanceBookings, $currentMonthStart, $currentMonthEnd, $previousMonthStart, $previousMonthEnd, $reportTimezone) {
                $assigned = $staffPerformanceBookings
                    ->filter(fn (Booking $booking): bool => $booking->isAssignedToStaff((int) $staff->id));
                $completed = $assigned->where('status', 'completed');
                $ratings = $completed
                    ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === (int) $staff->id)
                    ->pluck('rating')
                    ->filter();
                $currentMonthCompleted = $completed->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date->toDateString(), $reportTimezone)->betweenIncluded($currentMonthStart, $currentMonthEnd)
                );
                $previousMonthCompleted = $completed->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date->toDateString(), $reportTimezone)->betweenIncluded($previousMonthStart, $previousMonthEnd)
                );
                $currentMonthRatings = $ratings->filter(
                    fn (Rating $rating) => $rating->created_at
                        && $rating->created_at->copy()->timezone($reportTimezone)->betweenIncluded($currentMonthStart, $currentMonthEnd)
                );

                $staff->total_assigned = $assigned->count();
                $staff->total_completed = $completed->count();
                $staff->completion_rate = $assigned->count() > 0
                    ? round(($completed->count() / $assigned->count()) * 100, 1)
                    : 0.0;
                $staff->avg_rating = $ratings->count() > 0
                    ? round((float) $ratings->avg('stars'), 1)
                    : null;
                $staff->total_ratings = $ratings->count();
                $staff->current_month_completed = $currentMonthCompleted->count();
                $staff->previous_month_completed = $previousMonthCompleted->count();
                $staff->trend_change = $staff->current_month_completed - $staff->previous_month_completed;
                // Booking price belongs to the legacy primary cleaner until a split policy is defined.
                $staff->current_month_revenue = round((float) $currentMonthCompleted
                    ->where('staff_id', $staff->id)
                    ->sum('price'), 2);
                $staff->current_month_avg_rating = $currentMonthRatings->count() > 0
                    ? round((float) $currentMonthRatings->avg('stars'), 1)
                    : null;

                return $staff;
            })
            ->values();

        $staffPerformance = $staffPerformance
            ->sort(function ($left, $right) {
                $leftRank = [
                    $left->current_month_completed > 0 ? 1 : 0,
                    $left->current_month_completed,
                    $left->avg_rating ?? 0,
                    $left->completion_rate,
                    $left->total_completed,
                    strtolower(trim($left->last_name.' '.$left->first_name)),
                ];

                $rightRank = [
                    $right->current_month_completed > 0 ? 1 : 0,
                    $right->current_month_completed,
                    $right->avg_rating ?? 0,
                    $right->completion_rate,
                    $right->total_completed,
                    strtolower(trim($right->last_name.' '.$right->first_name)),
                ];

                return $rightRank <=> $leftRank;
            })
            ->values();

        $topStaffLeaders = $staffPerformance
            ->filter(fn (User $staff) => $staff->current_month_completed > 0)
            ->take(5)
            ->values();

        $totalBookings = DB::table('bookings')->count();
        $completedBookings = DB::table('bookings')->where('status', 'completed')->count();
        $currentMonthBookings = $monthlyBookingTrend->last();
        $previousMonthBookings = $monthlyBookingTrend->slice(-2, 1)->first();
        $busiestTimeSlot = $timeSlotTrends->first();
        $busiestWeekday = $weekdayTrends->sortByDesc('total')->first();

        $analyticsOverview = [
            'completion_rate' => $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0.0,
            'booking_growth' => $currentMonthBookings?->growth,
            'current_month_bookings' => $currentMonthBookings?->total ?? 0,
            'previous_month_bookings' => $previousMonthBookings?->total ?? 0,
            'average_satisfaction' => $overallAvgStars,
            'total_reviews' => $overallReviewCount,
            'satisfaction_delta' => ($currentMonthAverageRating !== null && $previousMonthAverageRating !== null)
                ? round($currentMonthAverageRating - $previousMonthAverageRating, 1)
                : null,
            'peak_time_label' => $busiestTimeSlot?->label,
            'peak_time_total' => $busiestTimeSlot?->total ?? 0,
            'peak_day_label' => $busiestWeekday?->label,
            'peak_day_total' => $busiestWeekday?->total ?? 0,
        ];

        return [
            'analyticsOverview' => $analyticsOverview,
            'monthlyBookingTrend' => $monthlyBookingTrend,
            'timeSlotTrends' => $timeSlotTrends,
            'weekdayTrends' => $weekdayTrends,
            'staffPerformance' => $staffPerformance,
            'topStaffLeaders' => $topStaffLeaders,
            'satisfactionTrend' => $satisfactionTrend,
        ];
    }
}
