<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingStaffAssignment extends Model
{
    use HasFactory;

    public const TASK_GROUPS = [
        'general_cleaning' => 'General cleaning',
        'kitchen_bathroom' => 'Kitchen and bathroom',
        'floors_surfaces' => 'Floors and surfaces',
        'windows_detailing' => 'Windows and detailed work',
        'move_in_out' => 'Move-in / move-out tasks',
        'post_construction' => 'Post-construction cleanup',
        'office_workstations' => 'Office and workstations',
    ];

    public static function taskGroupsForService(?Service $service): array
    {
        $taskGroups = [];
        $taskGroupKeys = $service?->specialistTaskGroupKeys()
            ?? Service::specialistTaskGroupKeysForSlug(null);

        foreach ($taskGroupKeys as $taskGroupKey) {
            if (array_key_exists($taskGroupKey, self::TASK_GROUPS)) {
                $taskGroups[$taskGroupKey] = self::TASK_GROUPS[$taskGroupKey];
            }
        }

        return $taskGroups;
    }

    protected $fillable = [
        'booking_id',
        'staff_id',
        'task_group',
        'task_notes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function taskGroupLabel(): string
    {
        return self::TASK_GROUPS[$this->task_group] ?? ucfirst(str_replace('_', ' ', $this->task_group));
    }
}
