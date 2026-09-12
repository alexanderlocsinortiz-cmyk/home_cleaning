<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CleanerTeamMember extends Model
{
    use HasFactory;

    public const STATUS_INVITED = 'invited';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const AVAILABILITY_AVAILABLE = 'available';

    public const AVAILABILITY_PAUSED = 'paused';

    public const STATUS_LABELS = [
        self::STATUS_INVITED => 'Invited',
        self::STATUS_PENDING => 'Pending review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    protected $fillable = [
        'cleaner_application_id',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'current_address',
        'government_id_type',
        'government_id_number',
        'government_id_front_document_path',
        'government_id_front_document_original_filename',
        'government_id_back_document_path',
        'government_id_back_document_original_filename',
        'nbi_clearance_number',
        'nbi_clearance_document_path',
        'nbi_clearance_document_original_filename',
        'selfie_with_id_path',
        'selfie_with_id_original_filename',
        'status',
        'verification_notes',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'verification_token_hash',
        'verification_token_expires_at',
        'consent_at',
        'submitted_at',
        'availability_status',
        'availability_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
        'verification_token_expires_at' => 'datetime',
        'consent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'government_id_number' => 'encrypted',
        'nbi_clearance_number' => 'encrypted',
    ];

    public function cleanerApplication()
    {
        return $this->belongsTo(CleanerApplication::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_cleaner_team_members')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public static function statuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::of((string) $this->status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
            self::STATUS_PENDING => 'bg-amber-100 text-amber-700',
            self::STATUS_REJECTED, self::STATUS_SUSPENDED => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeAssigned(): bool
    {
        return $this->isApproved()
            && ($this->availability_status ?: self::AVAILABILITY_AVAILABLE) === self::AVAILABILITY_AVAILABLE;
    }

    public function missingVerificationDocuments(): array
    {
        return collect([
            'Government ID front' => $this->government_id_front_document_path,
            'Government ID back' => $this->government_id_back_document_path,
            'NBI / Police clearance' => $this->nbi_clearance_document_path,
            'Selfie with ID' => $this->selfie_with_id_path,
        ])->filter(fn (?string $path): bool => blank($path))->keys()->all();
    }

    public function verificationDocumentsComplete(): bool
    {
        return $this->missingVerificationDocuments() === []
            && filled($this->government_id_type)
            && filled($this->government_id_number)
            && filled($this->nbi_clearance_number);
    }

    public function issueVerificationToken(int $expiresInDays = 7): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'verification_token_hash' => hash('sha256', $token),
            'verification_token_expires_at' => now()->addDays($expiresInDays),
        ])->save();

        return $token;
    }

    public static function verificationTokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function hasActiveVerificationToken(): bool
    {
        return filled($this->verification_token_hash)
            && $this->verification_token_expires_at?->isFuture();
    }

    public function hasScheduleConflictFor(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $durationMinutes = null,
        ?int $exceptBookingId = null,
    ): bool {
        return $this->bookings()
            ->whereIn('status', Booking::ACTIVE_SCHEDULE_STATUSES)
            ->when($exceptBookingId !== null, fn ($query) => $query->where('bookings.id', '!=', $exceptBookingId))
            ->get(['bookings.id', 'scheduled_date', 'scheduled_time', 'duration_minutes'])
            ->contains(fn (Booking $booking): bool => Booking::assignmentWindowsOverlap(
                $scheduledDate,
                $scheduledTime,
                $durationMinutes,
                $booking->scheduled_date,
                $booking->scheduled_time,
                $booking->duration_minutes,
            ));
    }

    public function filePathFor(string $type): ?string
    {
        return match ($type) {
            'government-id-front' => $this->government_id_front_document_path,
            'government-id-back' => $this->government_id_back_document_path,
            'clearance' => $this->nbi_clearance_document_path,
            'selfie-with-id' => $this->selfie_with_id_path,
            default => null,
        };
    }

    public function originalFilenameFor(string $type): ?string
    {
        return match ($type) {
            'government-id-front' => $this->government_id_front_document_original_filename,
            'government-id-back' => $this->government_id_back_document_original_filename,
            'clearance' => $this->nbi_clearance_document_original_filename,
            'selfie-with-id' => $this->selfie_with_id_original_filename,
            default => null,
        };
    }
}
