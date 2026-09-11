<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_only_use_canonical_services_for_service_analytics(): void
    {
        $this->canonicalService([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'description' => 'Detailed cleaning',
            'price' => 1200,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-reports@example.com',
            'username' => 'adminreports',
            'role' => 'admin',
        ]);

        $client = $this->createUser([
            'email' => 'client-reports@example.com',
            'username' => 'clientreports',
            'role' => 'client',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 45,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1180,
            'status' => 'completed',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'deep',
            'property_type' => 'apartment',
            'rooms' => 1,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 1200,
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'office',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 80,
            'barangay' => 'Poblacion',
            'street_address' => '789 Quezon Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '11:00',
            'price' => 3000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports'));

        $response->assertOk();
        $response->assertViewHas('revenueByType', function (Collection $revenueByType) {
            return $revenueByType->count() === 1
                && $revenueByType->pluck('service_type')->all() === ['basic']
                && $revenueByType->pluck('service_name')->all() === ['Basic Clean'];
        });
        $response->assertViewHas('bookingsByType', function (Collection $bookingsByType) {
            $serviceTypes = $bookingsByType->pluck('service_type');

            return $serviceTypes->contains('basic')
                && $serviceTypes->contains('deep')
                && ! $serviceTypes->contains('office');
        });
        $response->assertViewHas('invalidServiceBookings', 1);
    }

    public function test_admin_reports_expose_advanced_analytics_and_trend_sections(): void
    {
        $this->canonicalService([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-advanced-reports@example.com',
            'username' => 'adminadvancedreports',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-advanced-reports@example.com',
            'username' => 'clientadvancedreports',
            'role' => 'client',
        ]);
        $staff = $this->createUser([
            'email' => 'staff-advanced-reports@example.com',
            'username' => 'staffadvancedreports',
            'role' => 'staff',
            'first_name' => 'Trend',
            'last_name' => 'Leader',
        ]);
        $secondaryStaff = $this->createUser([
            'email' => 'secondary-advanced-reports@example.com',
            'username' => 'secondaryadvancedreports',
            'role' => 'staff',
            'first_name' => 'Secondary',
            'last_name' => 'Leader',
        ]);

        $currentBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 35,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1180,
            'status' => 'completed',
            'staff_id' => $staff->id,
        ]);
        $currentBooking->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
        ])->save();
        $currentBooking->staffAssignments()->create([
            'staff_id' => $secondaryStaff->id,
            'task_group' => 'floors_surfaces',
        ]);

        $previousMonthBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->subMonth()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 960,
            'status' => 'completed',
            'staff_id' => $staff->id,
        ]);
        $previousMonthBooking->forceFill([
            'created_at' => now()->subMonth()->addDays(1),
            'updated_at' => now()->subMonth()->addDays(2),
        ])->save();

        Rating::create([
            'booking_id' => $currentBooking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Excellent service.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Rating::create([
            'booking_id' => $previousMonthBooking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 4,
            'comment' => 'Very good service.',
            'created_at' => now()->subMonth()->addDays(3),
            'updated_at' => now()->subMonth()->addDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Booking Trends');
        $response->assertSee('Customer Satisfaction Trends');
        $response->assertSee('Top Staff Trends');
        $response->assertSee('Busiest Booking Time');
        $response->assertViewHas('analyticsOverview', function (array $analyticsOverview) {
            return array_key_exists('completion_rate', $analyticsOverview)
                && array_key_exists('average_satisfaction', $analyticsOverview)
                && array_key_exists('peak_time_label', $analyticsOverview);
        });
        $response->assertViewHas('monthlyBookingTrend', function (Collection $monthlyBookingTrend) {
            return $monthlyBookingTrend->count() === 6;
        });
        $response->assertViewHas('topStaffLeaders', function (Collection $topStaffLeaders) use ($staff) {
            return $topStaffLeaders->pluck('id')->contains($staff->id);
        });
        $response->assertViewHas('staffPerformance', function (Collection $staffPerformance) use ($secondaryStaff) {
            $secondary = $staffPerformance->firstWhere('id', $secondaryStaff->id);

            return $secondary !== null
                && $secondary->total_assigned === 1
                && $secondary->total_completed === 1
                && $secondary->current_month_completed === 1
                && $secondary->current_month_revenue === 0.0;
        });
    }

    public function test_admin_reports_can_filter_summary_by_custom_date_range(): void
    {
        $this->canonicalService([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-filtered-reports@example.com',
            'username' => 'adminfilteredreports',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-filtered-reports@example.com',
            'username' => 'clientfilteredreports',
            'role' => 'client',
        ]);

        $included = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1000,
            'status' => 'completed',
        ]);
        $included->forceFill(['created_at' => '2026-05-05 10:00:00'])->save();

        $excluded = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 2000,
            'status' => 'completed',
        ]);
        $excluded->forceFill(['created_at' => '2026-04-01 10:00:00'])->save();

        $response = $this->actingAs($admin)->get(route('admin.reports', [
            'period' => 'custom',
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('totalBookings', 1);
        $response->assertViewHas('totalRevenue', fn ($totalRevenue) => (float) $totalRevenue === 1000.0);
        $response->assertViewHas('filters', function (array $filters) {
            return $filters['period'] === 'custom'
                && $filters['date_from'] === '2026-05-01'
                && $filters['date_to'] === '2026-05-31';
        });
    }

    public function test_admin_can_export_reports_as_pdf_and_excel(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-export-reports@example.com',
            'username' => 'adminexportreports',
            'role' => 'admin',
        ]);

        $pdfResponse = $this->actingAs($admin)->get(route('admin.reports.export', [
            'format' => 'pdf',
            'period' => 'this_month',
        ]));

        $pdfResponse->assertOk();
        $this->assertSame('application/pdf', $pdfResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="reports_', $pdfResponse->headers->get('content-disposition'));

        $excelResponse = $this->actingAs($admin)->get(route('admin.reports.export', [
            'format' => 'excel',
            'period' => 'this_month',
        ]));

        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelResponse->headers->get('content-type'));
        $excelResponse->assertSee('Reports &amp; Analytics', false);
    }

    public function test_admin_provider_payouts_show_summary_and_rows(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-provider-payouts@example.com',
            'username' => 'adminproviderpayouts',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-provider-payouts@example.com',
            'username' => 'clientproviderpayouts',
            'role' => 'client',
        ]);
        $provider = $this->createCleanerApplication('Payout Team Cleaners', 'payout-team@example.com');
        $booking = $this->createMarketplaceBooking($client, $provider, [
            'price' => 2000,
            'provider_payout_status' => 'ready',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.provider-payouts'));

        $response->assertOk();
        $response->assertSee('Provider Payouts');
        $response->assertSee('Payout Team Cleaners');
        $response->assertSee('CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('&#8369;2,000.00', false);
        $response->assertSee('&#8369;300.00', false);
        $response->assertSee('&#8369;1,700.00', false);
        $response->assertViewHas('payoutSummary', function (array $summary) {
            return $summary['gross'] === 2000.0
                && $summary['commission'] === 300.0
                && $summary['payout'] === 1700.0
                && $summary['status_counts']['ready'] === 1;
        });
    }

    public function test_admin_provider_payouts_can_filter_by_provider_status_and_date(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-provider-payout-filters@example.com',
            'username' => 'adminproviderpayoutfilters',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-provider-payout-filters@example.com',
            'username' => 'clientproviderpayoutfilters',
            'role' => 'client',
        ]);
        $includedProvider = $this->createCleanerApplication('Included Provider', 'included-provider@example.com');
        $otherProvider = $this->createCleanerApplication('Other Provider', 'other-provider@example.com');
        $included = $this->createMarketplaceBooking($client, $includedProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_payout_status' => 'paid',
        ]);
        $wrongStatus = $this->createMarketplaceBooking($client, $includedProvider, [
            'scheduled_date' => '2026-06-11',
            'provider_payout_status' => 'pending',
        ]);
        $wrongProvider = $this->createMarketplaceBooking($client, $otherProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_payout_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.provider-payouts', [
            'provider_id' => $includedProvider->id,
            'payout_status' => 'paid',
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-10',
        ]));

        $response->assertOk();
        $response->assertSee('Included Provider');
        $response->assertSee('CF-'.str_pad($included->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-'.str_pad($wrongStatus->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-'.str_pad($wrongProvider->id, 5, '0', STR_PAD_LEFT));
        $response->assertViewHas('payoutSummary', function (array $summary) {
            return $summary['count'] === 1
                && $summary['status_counts']['paid'] === 1;
        });
    }

    public function test_admin_provider_payouts_warn_when_provider_payout_setup_is_incomplete(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-provider-payout-warnings@example.com',
            'username' => 'adminproviderpayoutwarnings',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-provider-payout-warnings@example.com',
            'username' => 'clientproviderpayoutwarnings',
            'role' => 'client',
        ]);
        $provider = $this->createCleanerApplication('Warning Provider', 'warning-provider@example.com');
        $this->createMarketplaceBooking($client, $provider, [
            'provider_payout_status' => 'ready',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.provider-payouts'));

        $response->assertOk();
        $response->assertSee('Warning Provider');
        $response->assertSee('Payout details missing');
        $response->assertSee('Documents not verified');
    }

    public function test_admin_can_export_filtered_provider_payouts_as_csv(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-provider-payout-export@example.com',
            'username' => 'adminproviderpayoutexport',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-provider-payout-export@example.com',
            'username' => 'clientproviderpayoutexport',
            'role' => 'client',
        ]);
        $includedProvider = $this->createCleanerApplication('CSV Included Provider', 'csv-included-provider@example.com');
        $otherProvider = $this->createCleanerApplication('CSV Other Provider', 'csv-other-provider@example.com');
        $included = $this->createMarketplaceBooking($client, $includedProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_payout_status' => 'paid',
        ]);
        $included->forceFill([
            'provider_payout_reference' => 'CSV-PAYOUT-001',
            'provider_payout_paid_at' => '2026-06-11 09:30:00',
        ])->save();
        $wrongStatus = $this->createMarketplaceBooking($client, $includedProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_payout_status' => 'ready',
        ]);
        $wrongProvider = $this->createMarketplaceBooking($client, $otherProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_payout_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.provider-payouts.export', [
            'provider_id' => $includedProvider->id,
            'payout_status' => 'paid',
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-10',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Booking,Provider,Customer,Service', $content);
        $this->assertStringContainsString('CF-'.str_pad($included->id, 5, '0', STR_PAD_LEFT), $content);
        $this->assertStringContainsString('CSV Included Provider', $content);
        $this->assertStringContainsString('CSV-PAYOUT-001', $content);
        $this->assertStringNotContainsString('CF-'.str_pad($wrongStatus->id, 5, '0', STR_PAD_LEFT), $content);
        $this->assertStringNotContainsString('CF-'.str_pad($wrongProvider->id, 5, '0', STR_PAD_LEFT), $content);
    }

    public function test_admin_provider_performance_shows_operating_metrics(): void
    {
        $admin = $this->createUser([
            'email' => 'admin-provider-performance@example.com',
            'username' => 'adminproviderperformance',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-provider-performance@example.com',
            'username' => 'clientproviderperformance',
            'role' => 'client',
        ]);
        $staff = $this->createUser([
            'email' => 'staff-provider-performance@example.com',
            'username' => 'staffproviderperformance',
            'role' => 'staff',
        ]);
        $provider = $this->createCleanerApplication('Reliable Provider', 'reliable-provider@example.com');
        $otherProvider = $this->createCleanerApplication('Filtered Provider', 'filtered-provider@example.com');
        $completed = $this->createMarketplaceBooking($client, $provider, [
            'scheduled_date' => '2026-06-10',
            'provider_assignment_status' => 'accepted',
            'provider_payout_status' => 'paid',
            'on_time_status' => 'on_time',
        ]);
        $late = $this->createMarketplaceBooking($client, $provider, [
            'scheduled_date' => '2026-06-11',
            'provider_assignment_status' => 'accepted',
            'provider_payout_status' => 'ready',
            'on_time_status' => 'late',
        ]);
        $declined = $this->createMarketplaceBooking($client, $provider, [
            'scheduled_date' => '2026-06-12',
            'provider_assignment_status' => 'declined',
            'provider_payout_status' => 'pending',
            'status' => 'cancelled',
            'dispute_status' => 'open',
        ]);
        $this->createMarketplaceBooking($client, $otherProvider, [
            'scheduled_date' => '2026-06-10',
            'provider_assignment_status' => 'accepted',
        ]);

        Rating::create([
            'booking_id' => $completed->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Excellent work.',
        ]);
        Rating::create([
            'booking_id' => $late->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 3,
            'comment' => 'Late but completed.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.provider-performance', [
            'provider_id' => $provider->id,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-12',
        ]));

        $response->assertOk();
        $response->assertSee('Provider Performance');
        $response->assertSee('Reliable Provider');
        $response->assertSee('66.7% acceptance');
        $response->assertSee('66.7% completion');
        $response->assertSee('4.0');
        $response->assertSee('50.0% late');
        $response->assertSee('33.3% dispute rate');
        $response->assertSee('Commission &#8369;540.00', false);
        $response->assertViewHas('summary', function (array $summary) {
            return $summary['providers'] === 1
                && $summary['assigned'] === 3
                && $summary['completed'] === 2
                && $summary['open_disputes'] === 1
                && $summary['commission'] === 540.0;
        });
        $response->assertViewHas('providerRows', function ($providerRows) {
            return $providerRows->count() === 1
                && $providerRows->first()->business_name === 'Reliable Provider';
        });
    }

    private function createUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'testuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createCleanerApplication(string $businessName, string $email): CleanerApplication
    {
        return CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => $businessName,
            'contact_person' => 'Provider Contact',
            'email' => $email,
            'phone' => '09171234567',
            'service_area' => 'All Valencia City barangays',
            'coverage_barangays' => array_values(config('cleanflow.barangays', [])),
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'activated_at' => now(),
        ]);
    }

    private function createMarketplaceBooking(User $client, CleanerApplication $provider, array $overrides = []): Booking
    {
        $booking = Booking::create(array_merge([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => '2026-06-10',
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => 'completed',
            'payment_method' => 'on_site_cash',
            'payment_status' => 'paid',
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'accepted',
        ], collect($overrides)->except([
            'provider_payout_status',
        ])->all()));

        $booking->forceFill(array_merge(
            $booking->calculateMarketplaceCommission(),
            ['provider_payout_status' => $overrides['provider_payout_status'] ?? 'pending']
        ))->save();

        return $booking->fresh();
    }
}
