<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'provider_gross_amount',
        'platform_commission_rate',
        'platform_commission_amount',
        'provider_payout_amount',
        'provider_payout_status',
        'provider_payout_reference',
        'provider_payout_paid_at',
        'provider_payout_processed_by',
        'provider_payout_proof_path',
        'provider_payout_proof_original_filename',
        'cash_collected_amount',
        'provider_commission_due',
        'provider_commission_status',
        'provider_commission_reference',
        'provider_commission_paid_at',
        'provider_commission_collected_by',
        'provider_commission_proof_path',
        'provider_commission_proof_original_filename',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('booking_payouts')) {
            Schema::create('booking_payouts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
                $table->decimal('provider_gross_amount', 10, 2)->nullable();
                $table->decimal('platform_commission_rate', 5, 4)->nullable();
                $table->decimal('platform_commission_amount', 10, 2)->nullable();
                $table->decimal('provider_payout_amount', 10, 2)->nullable();
                $table->string('provider_payout_status', 20)->nullable();
                $table->string('provider_payout_reference', 120)->nullable();
                $table->timestamp('provider_payout_paid_at')->nullable();
                $table->foreignId('provider_payout_processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('provider_payout_proof_path')->nullable();
                $table->string('provider_payout_proof_original_filename')->nullable();
                $table->decimal('cash_collected_amount', 10, 2)->nullable();
                $table->decimal('provider_commission_due', 10, 2)->nullable();
                $table->string('provider_commission_status', 20)->nullable();
                $table->string('provider_commission_reference', 120)->nullable();
                $table->timestamp('provider_commission_paid_at')->nullable();
                $table->foreignId('provider_commission_collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('provider_commission_proof_path')->nullable();
                $table->string('provider_commission_proof_original_filename')->nullable();
                $table->timestamps();

                $table->index(['provider_payout_status', 'provider_payout_paid_at']);
                $table->index(['provider_commission_status', 'provider_commission_paid_at']);
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'provider_gross_amount')) {
            DB::table('bookings')
                ->where(function ($query): void {
                    foreach (self::COLUMNS as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->orderBy('id')
                ->chunkById(500, function ($bookings): void {
                    foreach ($bookings as $booking) {
                        $attributes = ['booking_id' => $booking->id];

                        foreach (self::COLUMNS as $column) {
                            $attributes[$column] = $booking->{$column};
                        }

                        $attributes['created_at'] = $booking->created_at ?? now();
                        $attributes['updated_at'] = $booking->updated_at ?? now();
                        DB::table('booking_payouts')->updateOrInsert(['booking_id' => $booking->id], $attributes);
                    }
                });

            Schema::table('bookings', function (Blueprint $table): void {
                foreach (['provider_payout_processed_by', 'provider_commission_collected_by'] as $foreignColumn) {
                    if (Schema::hasColumn('bookings', $foreignColumn)) {
                        $table->dropConstrainedForeignId($foreignColumn);
                    }
                }

                $columns = array_values(array_filter(
                    self::COLUMNS,
                    fn (string $column): bool => ! in_array($column, ['provider_payout_processed_by', 'provider_commission_collected_by'], true)
                        && Schema::hasColumn('bookings', $column),
                ));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    continue;
                }

                if (in_array($column, ['provider_payout_processed_by', 'provider_commission_collected_by'], true)) {
                    $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
                } elseif (str_ends_with($column, '_amount')) {
                    $table->decimal($column, 10, 2)->nullable();
                } elseif (str_ends_with($column, '_rate')) {
                    $table->decimal($column, 5, 4)->nullable();
                } elseif (str_ends_with($column, '_at')) {
                    $table->timestamp($column)->nullable();
                } else {
                    $table->string($column, str_contains($column, 'status') ? 20 : 120)->nullable();
                }
            }
        });

        if (Schema::hasTable('booking_payouts')) {
            DB::table('booking_payouts')->orderBy('id')->chunkById(500, function ($payouts): void {
                foreach ($payouts as $payout) {
                    $attributes = [];
                    foreach (self::COLUMNS as $column) {
                        $attributes[$column] = $payout->{$column};
                    }
                    DB::table('bookings')->where('id', $payout->booking_id)->update($attributes);
                }
            });

            Schema::drop('booking_payouts');
        }
    }
};
