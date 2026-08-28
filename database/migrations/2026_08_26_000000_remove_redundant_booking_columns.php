<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        $this->backfillCanonicalPrices();

        if (Schema::hasColumn('bookings', 'address')) {
            DB::table('bookings')
                ->whereNotNull('address')
                ->where(function ($query): void {
                    $query->whereNull('street_address')->orWhere('street_address', '');
                })
                ->update(['street_address' => DB::raw('address')]);
        }

        $redundantColumns = array_values(array_filter([
            Schema::hasColumn('bookings', 'address') ? 'address' : null,
            Schema::hasColumn('bookings', 'property_adjustment') ? 'property_adjustment' : null,
            Schema::hasColumn('bookings', 'room_bathroom_fees') ? 'room_bathroom_fees' : null,
            Schema::hasColumn('bookings', 'floor_area_fees') ? 'floor_area_fees' : null,
            Schema::hasColumn('bookings', 'add_on_fees') ? 'add_on_fees' : null,
        ]));

        if ($redundantColumns !== []) {
            Schema::table('bookings', function ($table) use ($redundantColumns): void {
                $table->dropColumn($redundantColumns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function ($table): void {
            if (! Schema::hasColumn('bookings', 'address')) {
                $table->string('address')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'property_adjustment')) {
                $table->decimal('property_adjustment', 10, 2)->nullable()->default(0);
            }
            if (! Schema::hasColumn('bookings', 'room_bathroom_fees')) {
                $table->decimal('room_bathroom_fees', 10, 2)->nullable()->default(0);
            }
            if (! Schema::hasColumn('bookings', 'floor_area_fees')) {
                $table->decimal('floor_area_fees', 10, 2)->nullable()->default(0);
            }
            if (! Schema::hasColumn('bookings', 'add_on_fees')) {
                $table->decimal('add_on_fees', 10, 2)->nullable()->default(0);
            }
        });
    }

    private function backfillCanonicalPrices(): void
    {
        if (Schema::hasColumn('bookings', 'property_adjustment') && Schema::hasColumn('bookings', 'property_fee')) {
            DB::table('bookings')
                ->whereNotNull('property_adjustment')
                ->where(function ($query): void {
                    $query->whereNull('property_fee')->orWhere('property_fee', 0);
                })
                ->update(['property_fee' => DB::raw('property_adjustment')]);
        }

        if (Schema::hasColumn('bookings', 'room_bathroom_fees')
            && Schema::hasColumn('bookings', 'rooms_fee')
            && Schema::hasColumn('bookings', 'bathrooms_fee')) {
            DB::table('bookings')
                ->whereNotNull('room_bathroom_fees')
                ->where(function ($query): void {
                    $query->where(function ($nested): void {
                        $nested->whereNull('rooms_fee')->orWhere('rooms_fee', 0);
                    })->where(function ($nested): void {
                        $nested->whereNull('bathrooms_fee')->orWhere('bathrooms_fee', 0);
                    });
                })
                ->update(['rooms_fee' => DB::raw('room_bathroom_fees')]);
        }

        foreach ([
            'floor_area_fees' => 'floor_area_fee',
            'add_on_fees' => 'add_ons_fee',
        ] as $legacyColumn => $canonicalColumn) {
            if (! Schema::hasColumn('bookings', $legacyColumn) || ! Schema::hasColumn('bookings', $canonicalColumn)) {
                continue;
            }

            DB::table('bookings')
                ->whereNotNull($legacyColumn)
                ->where(function ($query) use ($canonicalColumn): void {
                    $query->whereNull($canonicalColumn)->orWhere($canonicalColumn, 0);
                })
                ->update([$canonicalColumn => DB::raw($legacyColumn)]);
        }
    }
};
