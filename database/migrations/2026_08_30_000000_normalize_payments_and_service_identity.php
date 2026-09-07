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
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->string('method', 40)->default('on_site_cash');
                $table->string('status', 20)->default('pending');
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('collected_amount', 10, 2)->nullable();
                $table->char('currency', 3)->default('PHP');
                $table->string('provider', 40)->nullable();
                $table->string('provider_payment_id', 120)->nullable();
                $table->string('checkout_session_id', 120)->nullable();
                $table->string('reference', 120)->nullable();
                $table->string('receipt_number', 120)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('collected_at')->nullable();
                $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('receipt_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['booking_id', 'status'], 'payments_booking_status_idx');
                $table->index(['method', 'status', 'paid_at'], 'payments_method_status_paid_idx');
                $table->index('checkout_session_id', 'payments_checkout_session_idx');
                $table->index('reference', 'payments_reference_idx');
            });
        }

        $this->backfillPayments();
        $this->backfillServiceIds();
        $this->removeLegacyColumns();
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        $this->restoreLegacyColumns();

        if (Schema::hasTable('payments')) {
            Schema::drop('payments');
        }
    }

    private function backfillPayments(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('payments')) {
            return;
        }

        $legacyColumns = array_filter([
            'payment_method',
            'payment_status',
            'payment_reference',
            'payment_checkout_session_id',
            'paid_at',
            'cash_receipt_number',
            'payment_collected_amount',
            'payment_collected_at',
            'payment_collected_by',
            'payment_receipt_notes',
        ], fn (string $column): bool => Schema::hasColumn('bookings', $column));

        if ($legacyColumns === []) {
            return;
        }

        DB::table('bookings')->orderBy('id')->chunkById(500, function ($bookings): void {
            foreach ($bookings as $booking) {
                if (DB::table('payments')->where('booking_id', $booking->id)->exists()) {
                    continue;
                }

                $method = $booking->payment_method ?: 'on_site_cash';
                $status = $booking->payment_status ?: 'pending';
                $isCash = $method === 'on_site_cash';
                $paidAt = $booking->paid_at ?? ($isCash ? $booking->payment_collected_at : null);

                DB::table('payments')->insert([
                    'booking_id' => $booking->id,
                    'method' => $method,
                    'status' => $status,
                    'amount' => $booking->price ?? 0,
                    'collected_amount' => $booking->payment_collected_amount,
                    'currency' => 'PHP',
                    'provider' => $isCash ? 'manual' : 'paymongo',
                    'checkout_session_id' => $booking->payment_checkout_session_id,
                    'reference' => $booking->payment_reference,
                    'receipt_number' => $booking->cash_receipt_number,
                    'paid_at' => $paidAt,
                    'collected_at' => $booking->payment_collected_at,
                    'collected_by' => $booking->payment_collected_by,
                    'receipt_notes' => $booking->payment_receipt_notes,
                    'created_at' => $booking->created_at ?? now(),
                    'updated_at' => $booking->updated_at ?? now(),
                ]);
            }
        });
    }

    private function backfillServiceIds(): void
    {
        if (! Schema::hasColumn('bookings', 'service_type') || ! Schema::hasColumn('bookings', 'service_id')) {
            return;
        }

        $aliases = [
            'basic-clean' => 'basic',
        ];

        DB::table('bookings')
            ->whereNull('service_id')
            ->whereNotNull('service_type')
            ->orderBy('id')
            ->chunkById(500, function ($bookings) use ($aliases): void {
                foreach ($bookings as $booking) {
                    $slug = $aliases[$booking->service_type] ?? $booking->service_type;
                    $serviceId = DB::table('services')->where('slug', $slug)->value('id');

                    if ($serviceId) {
                        DB::table('bookings')->where('id', $booking->id)->update(['service_id' => $serviceId]);
                    }
                }
            });

        $unresolved = DB::table('bookings')
            ->whereNull('service_id')
            ->whereNotNull('service_type')
            ->count();

        if ($unresolved > 0) {
            throw new RuntimeException('Cannot remove service_type while '.$unresolved.' booking(s) have no matching service_id.');
        }
    }

    private function removeLegacyColumns(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (Schema::hasColumn('bookings', 'payment_collected_by')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropForeign(['payment_collected_by']);
            });
        }

        if (Schema::hasColumn('bookings', 'payment_checkout_session_id')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropIndex(['payment_checkout_session_id']);
            });
        }

        if (Schema::hasColumn('bookings', 'cash_receipt_number')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropUnique(['cash_receipt_number']);
            });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('bookings', 'service_type') ? 'service_type' : null,
            Schema::hasColumn('bookings', 'payment_method') ? 'payment_method' : null,
            Schema::hasColumn('bookings', 'payment_status') ? 'payment_status' : null,
            Schema::hasColumn('bookings', 'payment_reference') ? 'payment_reference' : null,
            Schema::hasColumn('bookings', 'payment_checkout_session_id') ? 'payment_checkout_session_id' : null,
            Schema::hasColumn('bookings', 'paid_at') ? 'paid_at' : null,
            Schema::hasColumn('bookings', 'cash_receipt_number') ? 'cash_receipt_number' : null,
            Schema::hasColumn('bookings', 'payment_collected_amount') ? 'payment_collected_amount' : null,
            Schema::hasColumn('bookings', 'payment_collected_at') ? 'payment_collected_at' : null,
            Schema::hasColumn('bookings', 'payment_collected_by') ? 'payment_collected_by' : null,
            Schema::hasColumn('bookings', 'payment_receipt_notes') ? 'payment_receipt_notes' : null,
        ]));

        if ($columns !== []) {
            Schema::table('bookings', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    private function restoreLegacyColumns(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'service_type')) {
                $table->string('service_type')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->default('on_site_cash');
            }
            if (! Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status')->default('pending');
            }
            if (! Schema::hasColumn('bookings', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'payment_checkout_session_id')) {
                $table->string('payment_checkout_session_id')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'cash_receipt_number')) {
                $table->string('cash_receipt_number')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_amount')) {
                $table->decimal('payment_collected_amount', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_by')) {
                $table->foreignId('payment_collected_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'payment_receipt_notes')) {
                $table->text('payment_receipt_notes')->nullable();
            }
        });

        if (! Schema::hasTable('payments')) {
            return;
        }

        DB::table('payments')->orderBy('id')->chunkById(500, function ($payments): void {
            foreach ($payments as $payment) {
                DB::table('bookings')->where('id', $payment->booking_id)->update([
                    'service_type' => DB::table('services')->where('id', DB::table('bookings')->where('id', $payment->booking_id)->value('service_id'))->value('slug'),
                    'payment_method' => $payment->method,
                    'payment_status' => $payment->status,
                    'payment_reference' => $payment->reference,
                    'payment_checkout_session_id' => $payment->checkout_session_id,
                    'paid_at' => $payment->paid_at,
                    'cash_receipt_number' => $payment->receipt_number,
                    'payment_collected_amount' => $payment->collected_amount,
                    'payment_collected_at' => $payment->collected_at,
                    'payment_collected_by' => $payment->collected_by,
                    'payment_receipt_notes' => $payment->receipt_notes,
                ]);
            }
        });
    }
};
