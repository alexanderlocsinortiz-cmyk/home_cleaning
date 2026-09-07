<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'dispute_status',
        'dispute_reason',
        'dispute_description',
        'disputed_at',
        'dispute_resolution',
        'dispute_admin_notes',
        'dispute_reviewed_by',
        'dispute_resolved_at',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('booking_disputes')) {
            Schema::create('booking_disputes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
                $table->string('status', 20);
                $table->string('reason', 80)->nullable();
                $table->text('description')->nullable();
                $table->timestamp('disputed_at')->nullable();
                $table->string('resolution', 40)->nullable();
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'disputed_at']);
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'dispute_status')) {
            DB::table('bookings')
                ->whereNotNull('dispute_status')
                ->orderBy('id')
                ->chunkById(500, function ($bookings): void {
                    foreach ($bookings as $booking) {
                        DB::table('booking_disputes')->updateOrInsert(
                            ['booking_id' => $booking->id],
                            [
                                'status' => $booking->dispute_status,
                                'reason' => $booking->dispute_reason,
                                'description' => $booking->dispute_description,
                                'disputed_at' => $booking->disputed_at,
                                'resolution' => $booking->dispute_resolution,
                                'admin_notes' => $booking->dispute_admin_notes,
                                'reviewed_by' => $booking->dispute_reviewed_by,
                                'resolved_at' => $booking->dispute_resolved_at,
                                'created_at' => $booking->created_at ?? now(),
                                'updated_at' => $booking->updated_at ?? now(),
                            ],
                        );
                    }
                });

            Schema::table('bookings', function (Blueprint $table): void {
                if (Schema::hasColumn('bookings', 'dispute_reviewed_by')) {
                    $table->dropConstrainedForeignId('dispute_reviewed_by');
                }

                $columns = array_values(array_filter(
                    self::COLUMNS,
                    fn (string $column): bool => $column !== 'dispute_reviewed_by'
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
            if (! Schema::hasColumn('bookings', 'dispute_status')) {
                $table->string('dispute_status', 20)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'dispute_reason')) {
                $table->string('dispute_reason', 80)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'dispute_description')) {
                $table->text('dispute_description')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'dispute_resolution')) {
                $table->string('dispute_resolution', 40)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'dispute_admin_notes')) {
                $table->text('dispute_admin_notes')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'dispute_reviewed_by')) {
                $table->foreignId('dispute_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'dispute_resolved_at')) {
                $table->timestamp('dispute_resolved_at')->nullable();
            }
        });

        if (Schema::hasTable('booking_disputes')) {
            DB::table('booking_disputes')->orderBy('id')->chunkById(500, function ($disputes): void {
                foreach ($disputes as $dispute) {
                    DB::table('bookings')->where('id', $dispute->booking_id)->update([
                        'dispute_status' => $dispute->status,
                        'dispute_reason' => $dispute->reason,
                        'dispute_description' => $dispute->description,
                        'disputed_at' => $dispute->disputed_at,
                        'dispute_resolution' => $dispute->resolution,
                        'dispute_admin_notes' => $dispute->admin_notes,
                        'dispute_reviewed_by' => $dispute->reviewed_by,
                        'dispute_resolved_at' => $dispute->resolved_at,
                    ]);
                }
            });

            Schema::drop('booking_disputes');
        }
    }
};
