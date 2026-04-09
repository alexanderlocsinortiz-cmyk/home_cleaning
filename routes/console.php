<?php

use App\Models\Device;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:register-device
    {serial : Unique serial number for the ESP32 unit}
    {name : Friendly device name shown in the admin UI}
    {--location= : Optional install location like Front Desk}
    {--token= : Provide a token manually instead of generating one}
    {--rotate-token : Replace the existing token for this serial number}', function () {
    $serial = (string) $this->argument('serial');
    $name = (string) $this->argument('name');
    $location = $this->option('location');
    $providedToken = $this->option('token');
    $rotateToken = (bool) $this->option('rotate-token');

    $device = Device::firstOrNew(['serial_number' => $serial]);
    $device->name = $name;

    if ($location !== null) {
        $device->location = $location;
    }

    $device->is_active = true;

    if (! $device->exists || $rotateToken || $providedToken) {
        $token = $providedToken ?: Str::random(64);

        $conflictingToken = Device::query()
            ->where('api_token', $token)
            ->when($device->exists, fn ($query) => $query->where('id', '!=', $device->id))
            ->exists();

        if ($conflictingToken) {
            $this->error('The provided token is already assigned to another device.');

            return Command::FAILURE;
        }

        $device->api_token = $token;
    }

    $device->save();

    $this->table(
        ['Field', 'Value'],
        [
            ['Action', $device->wasRecentlyCreated ? 'Created' : 'Updated'],
            ['Name', $device->name],
            ['Serial', $device->serial_number],
            ['Location', $device->location ?: '-'],
            ['Token', $device->api_token],
            ['Active', $device->is_active ? 'yes' : 'no'],
        ]
    );

    $this->newLine();
    $this->warn('Keep the token private. Put it into the ESP32 sketch as DEVICE_TOKEN.');

    return Command::SUCCESS;
})->purpose('Create or update an ESP32 attendance device and print its API token');
