<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'refund_status')) {
                $table->string('refund_status', 20)->default('none')->after('status');
            }
            if (! Schema::hasColumn('payments', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('payments', 'refund_reference')) {
                $table->string('refund_reference', 120)->nullable()->after('provider_payment_id');
            }
            if (! Schema::hasColumn('payments', 'refund_requested_at')) {
                $table->timestamp('refund_requested_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_requested_at');
            }
            if (! Schema::hasColumn('payments', 'refund_failure_reason')) {
                $table->text('refund_failure_reason')->nullable()->after('refunded_at');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_refund_status_valid CHECK (refund_status IN ('none', 'pending', 'processing', 'succeeded', 'failed'))");
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['refund_status', 'refunded_at'], 'payments_refund_status_idx');
            $table->index('refund_reference', 'payments_refund_reference_idx');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql' && Schema::hasTable('payments')) {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_refund_status_valid');
        }

        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            foreach ([
                'payments_refund_status_idx',
                'payments_refund_reference_idx',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (Throwable) {
                    // Keep rollback tolerant of databases where a partial migration ran.
                }
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('payments', 'refund_status') ? 'refund_status' : null,
                Schema::hasColumn('payments', 'refund_amount') ? 'refund_amount' : null,
                Schema::hasColumn('payments', 'refund_reference') ? 'refund_reference' : null,
                Schema::hasColumn('payments', 'refund_requested_at') ? 'refund_requested_at' : null,
                Schema::hasColumn('payments', 'refunded_at') ? 'refunded_at' : null,
                Schema::hasColumn('payments', 'refund_failure_reason') ? 'refund_failure_reason' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
