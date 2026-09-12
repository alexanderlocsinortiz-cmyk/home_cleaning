<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('devices')
            ->select(['id', 'secret_key'])
            ->whereNotNull('secret_key')
            ->where('secret_key', '<>', '')
            ->orderBy('id')
            ->eachById(function (object $device): void {
                DB::table('devices')
                    ->where('id', $device->id)
                    ->update(['secret_key' => Crypt::encryptString((string) $device->secret_key)]);
            });
    }

    public function down(): void
    {
        throw new RuntimeException('Encrypted device secrets cannot be safely reverted to plaintext.');
    }
};
