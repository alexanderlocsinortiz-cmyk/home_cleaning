<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->hardenPayments();
        $this->hardenUserEmails();
        $this->protectBookingHistory();
        $this->addChecks();
    }

    public function down(): void
    {
        $this->dropChecks();
        $this->restoreBookingCascade();
        $this->restoreUserEmailIndex();
        $this->dropPaymentIndexes();
    }

    private function hardenPayments(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $this->failOnPaymentDuplicates('checkout_session_id', 'payments_checkout_session_unique');
        $this->failOnPaymentDuplicates('provider_payment_id', 'payments_provider_payment_unique');
        $this->failOnPaymentDuplicates('reference', 'payments_reference_unique');
        $this->failOnDuplicates('payments', 'receipt_number', 'payments_receipt_number_unique');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_checkout_session_unique ON payments (booking_id, checkout_session_id) WHERE checkout_session_id IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_provider_payment_unique ON payments (booking_id, provider_payment_id) WHERE provider_payment_id IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_reference_unique ON payments (booking_id, reference) WHERE reference IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_receipt_number_unique ON payments (receipt_number) WHERE receipt_number IS NOT NULL');
        }
    }

    private function hardenUserEmails(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $duplicates = DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) AS normalized_email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Cannot enforce case-insensitive user email uniqueness. Duplicate emails: '.$duplicates->implode(', '));
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_ci_unique ON users (LOWER(TRIM(email)))');
        }
    }

    private function protectBookingHistory(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'user_id')) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_user_id_foreign');
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
    }

    private function addChecks(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('payments')) {
            return;
        }

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_nonnegative CHECK (amount >= 0)');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_collected_amount_nonnegative CHECK (collected_amount IS NULL OR collected_amount >= 0)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_valid CHECK (method IN ('on_site_cash', 'gcash', 'maya'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_valid CHECK (status IN ('pending', 'paid', 'refunded'))");

        if (Schema::hasTable('ratings')) {
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_stars_valid CHECK (stars BETWEEN 1 AND 5)');
        }
    }

    private function dropChecks(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'payments_amount_nonnegative',
            'payments_collected_amount_nonnegative',
            'payments_method_valid',
            'payments_status_valid',
        ] as $constraint) {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        if (Schema::hasTable('ratings')) {
            DB::statement('ALTER TABLE ratings DROP CONSTRAINT IF EXISTS ratings_stars_valid');
        }
    }

    private function restoreBookingCascade(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('bookings')) {
            return;
        }

        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_user_id_foreign');
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    private function restoreUserEmailIndex(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS users_email_ci_unique');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email)');
    }

    private function dropPaymentIndexes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'payments_checkout_session_unique',
            'payments_provider_payment_unique',
            'payments_reference_unique',
            'payments_receipt_number_unique',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }

    private function failOnDuplicates(string $table, string $column, string $index): void
    {
        if (! Schema::hasColumn($table, $column) || DB::getDriverName() !== 'pgsql') {
            return;
        }

        $duplicates = DB::table($table)
            ->whereNotNull($column)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException("Cannot create {$index}; duplicate {$column} values: ".$duplicates->implode(', '));
        }
    }

    private function failOnPaymentDuplicates(string $column, string $index): void
    {
        if (! Schema::hasColumn('payments', $column) || DB::getDriverName() !== 'pgsql') {
            return;
        }

        $duplicates = DB::table('payments')
            ->whereNotNull($column)
            ->select('booking_id', $column)
            ->groupBy('booking_id', $column)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException("Cannot create {$index}; duplicate payment values exist for booking IDs: ".$duplicates->pluck('booking_id')->implode(', '));
        }
    }
};
