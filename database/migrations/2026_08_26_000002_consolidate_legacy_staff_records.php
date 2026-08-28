<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        $this->createArchiveTable();

        $columns = Schema::getColumnListing('staff');
        $rows = DB::table('staff')->get();

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('legacy_staff_records')->insert(
                $chunk->map(fn (object $row): array => [
                    'legacy_id' => $row->id,
                    'user_id' => $this->value($row, $columns, 'user_id'),
                    'employee_id' => $this->value($row, $columns, 'employee_id'),
                    'first_name' => $this->value($row, $columns, 'first_name'),
                    'last_name' => $this->value($row, $columns, 'last_name'),
                    'role' => $this->value($row, $columns, 'role'),
                    'phone' => $this->value($row, $columns, 'phone'),
                    'barangay' => $this->value($row, $columns, 'barangay'),
                    'status' => $this->value($row, $columns, 'status'),
                    'hourly_rate' => $this->value($row, $columns, 'hourly_rate'),
                    'bio' => $this->value($row, $columns, 'bio'),
                    'years_of_experience' => $this->value($row, $columns, 'years_of_experience'),
                    'is_active' => $this->value($row, $columns, 'is_active'),
                    'source_created_at' => $this->value($row, $columns, 'created_at'),
                    'source_updated_at' => $this->value($row, $columns, 'updated_at'),
                    'archived_at' => now(),
                ])->all()
            );
        }

        // The current application identifies staff by users.id. Preserve that identity before removing the old FK.
        if (Schema::hasTable('attendance_logs')
            && Schema::hasColumn('attendance_logs', 'staff_id')
            && in_array('user_id', $columns, true)
            && Schema::hasColumn('attendance_logs', 'user_id')) {
            DB::table('staff')->select(['id', 'user_id'])->whereNotNull('user_id')->orderBy('id')->get()->each(
                function (object $staff): void {
                    DB::table('attendance_logs')
                        ->where('staff_id', $staff->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $staff->user_id]);
                }
            );
        }

        if (Schema::hasTable('attendance_logs') && Schema::hasColumn('attendance_logs', 'staff_id')) {
            Schema::table('attendance_logs', function (Blueprint $table): void {
                $table->dropForeign(['staff_id']);
                $table->dropColumn('staff_id');
            });
        }

        if (Schema::hasTable('attendance_logs') && Schema::hasColumn('attendance_logs', 'punched_at')) {
            if (Schema::hasColumn('attendance_logs', 'logged_at')) {
                DB::table('attendance_logs')
                    ->whereNull('logged_at')
                    ->whereNotNull('punched_at')
                    ->update(['logged_at' => DB::raw('punched_at')]);
            }

            Schema::table('attendance_logs', function (Blueprint $table): void {
                $table->dropColumn('punched_at');
            });
        }

        if (Schema::hasTable('attendance_logs') && Schema::hasColumn('attendance_logs', 'fingerprint_template_id')) {
            Schema::table('attendance_logs', function (Blueprint $table): void {
                $table->dropColumn('fingerprint_template_id');
            });
        }

        Schema::drop('staff');
    }

    public function down(): void
    {
        if (Schema::hasTable('staff')) {
            return;
        }

        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_id')->unique();
            $table->decimal('hourly_rate', 8, 2);
            $table->text('bio')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('legacy_staff_records')) {
            $rows = DB::table('legacy_staff_records')->get();

            foreach ($rows as $row) {
                DB::table('staff')->insert([
                    'id' => $row->legacy_id,
                    'user_id' => $row->user_id,
                    'employee_id' => $row->employee_id ?: 'LEGACY-'.$row->legacy_id,
                    'hourly_rate' => $row->hourly_rate ?? 0,
                    'bio' => $row->bio,
                    'years_of_experience' => $row->years_of_experience ?? 0,
                    'is_active' => $row->is_active ?? true,
                    'created_at' => $row->source_created_at,
                    'updated_at' => $row->source_updated_at,
                ]);
            }
        }
    }

    private function createArchiveTable(): void
    {
        if (Schema::hasTable('legacy_staff_records')) {
            return;
        }

        Schema::create('legacy_staff_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('role')->nullable();
            $table->string('phone')->nullable();
            $table->string('barangay')->nullable();
            $table->string('status')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->text('bio')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('archived_at');

            $table->unique('legacy_id');
        });
    }

    private function value(object $row, array $columns, string $column): mixed
    {
        return in_array($column, $columns, true) ? $row->{$column} : null;
    }
};
