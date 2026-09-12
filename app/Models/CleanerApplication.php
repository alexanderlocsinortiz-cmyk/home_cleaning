<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CleanerApplication extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_NEEDS_CHANGES = 'needs_changes';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_TEAM = 'team';

    public const AVAILABILITY_AVAILABLE = 'available';

    public const AVAILABILITY_PAUSED = 'paused';

    public const AVAILABILITY_UNAVAILABLE = 'unavailable';

    public const PAYOUT_METHOD_GCASH = 'gcash';

    public const PAYOUT_METHOD_MAYA = 'maya';

    public const PAYOUT_METHOD_BANK_TRANSFER = 'bank_transfer';

    public const PAYOUT_VERIFICATION_PENDING = 'pending';

    public const PAYOUT_VERIFICATION_VERIFIED = 'verified';

    public const PAYOUT_VERIFICATION_REJECTED = 'rejected';

    public const GOVERNMENT_ID_NATIONAL_ID = 'national_id';

    public const GOVERNMENT_ID_DRIVERS_LICENSE = 'drivers_license';

    public const GOVERNMENT_ID_PASSPORT = 'passport';

    public const GOVERNMENT_ID_UMID = 'umid';

    public const GOVERNMENT_ID_PHILHEALTH = 'philhealth_id';

    public const AVAILABILITY_LABELS = [
        self::AVAILABILITY_AVAILABLE => 'Available',
        self::AVAILABILITY_PAUSED => 'Paused',
        self::AVAILABILITY_UNAVAILABLE => 'Unavailable',
    ];

    public const PAYOUT_METHOD_LABELS = [
        self::PAYOUT_METHOD_GCASH => 'GCash',
        self::PAYOUT_METHOD_MAYA => 'Maya',
        self::PAYOUT_METHOD_BANK_TRANSFER => 'Bank transfer',
    ];

    public const PAYOUT_VERIFICATION_LABELS = [
        self::PAYOUT_VERIFICATION_PENDING => 'Pending verification',
        self::PAYOUT_VERIFICATION_VERIFIED => 'Verified',
        self::PAYOUT_VERIFICATION_REJECTED => 'Rejected',
    ];

    public const SERVICE_OFFERINGS = [
        'basic_cleaning' => 'Basic Cleaning',
        'deep_cleaning' => 'Deep Cleaning',
        'move_in_move_out_cleaning' => 'Move-in / Move-out Cleaning',
        'post_construction_cleaning' => 'Post Construction Cleaning',
        'office_cleaning' => 'Office Cleaning',
    ];

    public const GOVERNMENT_ID_LABELS = [
        self::GOVERNMENT_ID_NATIONAL_ID => 'National ID',
        self::GOVERNMENT_ID_DRIVERS_LICENSE => "Driver's License",
        self::GOVERNMENT_ID_PASSPORT => 'Passport',
        self::GOVERNMENT_ID_UMID => 'UMID',
        self::GOVERNMENT_ID_PHILHEALTH => 'PhilHealth ID',
    ];

    public const AVAILABLE_DAY_LABELS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    protected $fillable = [
        'applicant_type',
        'business_name',
        'contact_person',
        'email',
        'phone',
        'date_of_birth',
        'current_address',
        'location_area',
        'location_latitude',
        'location_longitude',
        'profile_photo_path',
        'profile_photo_original_filename',
        'business_logo_path',
        'business_logo_original_filename',
        'service_area',
        'coverage_barangays',
        'years_experience',
        'experience_unit',
        'team_size',
        'services_offered',
        'government_id_type',
        'government_id_number',
        'government_id_front_document_path',
        'government_id_front_document_original_filename',
        'government_id_back_document_path',
        'government_id_back_document_original_filename',
        'government_id_document_path',
        'government_id_document_original_filename',
        'nbi_clearance_number',
        'nbi_clearance_document_path',
        'nbi_clearance_document_original_filename',
        'selfie_with_id_path',
        'selfie_with_id_original_filename',
        'verification_notes',
        'worked_as_cleaner_before',
        'worked_for_cleaning_company_before',
        'has_cleaning_certifications',
        'owns_cleaning_equipment',
        'status',
        'admin_notes',
        'reviewed_by',
        'user_id',
        'reviewed_at',
        'activation_token_hash',
        'activation_token_expires_at',
        'activated_at',
        'availability_status',
        'availability_notes',
        'max_daily_bookings',
        'available_days',
        'terms_certify_accurate',
        'terms_agree_verification',
        'terms_approval_not_guaranteed',
        'terms_service_standards',
        'payout_method',
        'payout_account_name',
        'payout_account_number',
        'payout_verification_status',
        'valid_id_submitted',
        'business_permit_submitted',
        'payout_account_proof_submitted',
        'payout_verified_at',
        'sensitive_data_purged_at',
        'tracking_token_hash',
        'tracking_token_expires_at',
        'tracking_token_previous_hash',
        'tracking_token_previous_expires_at',
        'payout_verified_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'location_latitude' => 'decimal:7',
        'location_longitude' => 'decimal:7',
        'years_experience' => 'integer',
        'team_size' => 'integer',
        'coverage_barangays' => 'array',
        'max_daily_bookings' => 'integer',
        'available_days' => 'array',
        'worked_as_cleaner_before' => 'boolean',
        'worked_for_cleaning_company_before' => 'boolean',
        'has_cleaning_certifications' => 'boolean',
        'owns_cleaning_equipment' => 'boolean',
        'terms_certify_accurate' => 'boolean',
        'terms_agree_verification' => 'boolean',
        'terms_approval_not_guaranteed' => 'boolean',
        'terms_service_standards' => 'boolean',
        'valid_id_submitted' => 'boolean',
        'business_permit_submitted' => 'boolean',
        'payout_account_proof_submitted' => 'boolean',
        'reviewed_at' => 'datetime',
        'activation_token_expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'payout_verified_at' => 'datetime',
        'sensitive_data_purged_at' => 'datetime',
        'tracking_token_expires_at' => 'datetime',
        'tracking_token_previous_expires_at' => 'datetime',
        'government_id_number' => 'encrypted',
        'nbi_clearance_number' => 'encrypted',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payoutVerifier()
    {
        return $this->belongsTo(User::class, 'payout_verified_by');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(CleanerTeamMember::class);
    }

    public function approvedTeamMembers()
    {
        return $this->teamMembers()->where('status', CleanerTeamMember::STATUS_APPROVED);
    }

    public function approvedTeamMemberCount(): int
    {
        return $this->approvedTeamMembers()->count();
    }

    public function assignableTeamMemberCount(): int
    {
        return $this->approvedTeamMembers()
            ->where('availability_status', CleanerTeamMember::AVAILABILITY_AVAILABLE)
            ->count();
    }

    public function effectiveTeamCapacity(): int
    {
        if (! $this->isTeam()) {
            return 1;
        }

        // Legacy teams may predate the roster workflow. Keep their declared
        // capacity until they create their first member record; once a roster
        // exists, only approved and available cleaners count.
        return $this->teamMembers()->exists()
            ? $this->assignableTeamMemberCount()
            : max(1, (int) ($this->team_size ?: 1));
    }

    public function documents()
    {
        return $this->hasMany(CleanerApplicationDocument::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(CleanerApplicationActivityLog::class)->latest();
    }

    public function missingVerificationDocuments(): array
    {
        return collect([
            'Government ID front' => $this->governmentIdFrontDocumentPath(),
            'Government ID back' => $this->governmentIdBackDocumentPath(),
            'Selfie with ID' => $this->selfie_with_id_path,
        ])->filter(fn (?string $path): bool => blank($path))->keys()->all();
    }

    public function governmentIdFrontDocumentPath(): ?string
    {
        return $this->government_id_front_document_path ?: $this->government_id_document_path;
    }

    public function governmentIdBackDocumentPath(): ?string
    {
        return $this->government_id_back_document_path ?: $this->government_id_document_path;
    }

    public function verificationDocumentsComplete(): bool
    {
        return $this->missingVerificationDocuments() === [];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isTeam(): bool
    {
        return $this->applicant_type === self::TYPE_TEAM;
    }

    public function getExperienceLabelAttribute(): string
    {
        $value = max(0, (int) $this->years_experience);
        $unit = $this->experience_unit === 'months' ? 'month' : 'year';

        return $value.' '.$unit.($value === 1 ? '' : 's').' experience';
    }

    public function coversBarangay(?string $barangay): bool
    {
        $target = self::normalizeCoverageText($barangay);
        $valenciaBarangays = collect(config('cleanflow.barangays', []))
            ->keys()
            ->map(fn (string $area) => self::normalizeCoverageText($area));

        if ($target === '') {
            return false;
        }

        if (is_array($this->coverage_barangays) && $this->coverage_barangays !== []) {
            $coverageAreas = collect($this->coverage_barangays)
                ->map(fn (string $area) => self::normalizeCoverageText($area));

            if ($coverageAreas->contains(fn (string $area): bool => self::isAllBukidnonCoverage($area))) {
                return true;
            }

            return $coverageAreas->contains($target)
                || ($coverageAreas->contains(fn (string $area): bool => self::isValenciaWideCoverage($area))
                    && $valenciaBarangays->contains($target));
        }

        $coverage = self::normalizeCoverageText($this->service_area);

        if ($coverage === '') {
            return false;
        }

        if (self::isAllBukidnonCoverage($coverage)) {
            return true;
        }

        if (self::isValenciaWideCoverage($coverage)) {
            return $valenciaBarangays->contains($target);
        }

        $areas = collect(preg_split('/[,;\/|]+/', $coverage) ?: [])
            ->map(fn (string $area) => self::normalizeCoverageText($area))
            ->filter()
            ->values();

        return $areas->contains($target)
            || ($areas->contains(fn (string $area): bool => self::isAllBukidnonCoverage($area)))
            || ($areas->contains(fn (string $area): bool => self::isValenciaWideCoverage($area))
                && $valenciaBarangays->contains($target));
    }

    private static function isValenciaWideCoverage(string $coverage): bool
    {
        return in_array($coverage, [
            'valencia',
            'valencia city',
            'all valencia city',
            'all barangays',
            'valencia city bukidnon',
        ], true);
    }

    private static function isAllBukidnonCoverage(string $coverage): bool
    {
        return $coverage === 'all bukidnon cities and municipalities';
    }

    public function coverageLabel(): string
    {
        if (! is_array($this->coverage_barangays) || $this->coverage_barangays === []) {
            return $this->service_area;
        }

        $allBarangays = array_values(config('cleanflow.bukidnon_coverage_areas', []));

        if (count($this->coverage_barangays) === count($allBarangays)) {
            return 'All Bukidnon cities and municipalities';
        }

        return collect($this->coverage_barangays)->join(', ');
    }

    public static function availabilityStatuses(): array
    {
        return array_keys(self::AVAILABILITY_LABELS);
    }

    public static function payoutMethods(): array
    {
        return array_keys(self::PAYOUT_METHOD_LABELS);
    }

    public static function payoutVerificationStatuses(): array
    {
        return array_keys(self::PAYOUT_VERIFICATION_LABELS);
    }

    public function payoutMethodLabel(): string
    {
        return self::PAYOUT_METHOD_LABELS[$this->payout_method] ?? 'Not set';
    }

    public function payoutVerificationStatusLabel(): string
    {
        return self::PAYOUT_VERIFICATION_LABELS[$this->payout_verification_status ?: self::PAYOUT_VERIFICATION_PENDING] ?? Str::of((string) $this->payout_verification_status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function payoutVerificationBadgeClass(): string
    {
        return match ($this->payout_verification_status ?: self::PAYOUT_VERIFICATION_PENDING) {
            self::PAYOUT_VERIFICATION_VERIFIED => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            self::PAYOUT_VERIFICATION_REJECTED => 'bg-rose-100 text-rose-700 ring-rose-200',
            default => 'bg-amber-100 text-amber-700 ring-amber-200',
        };
    }

    public function hasPayoutDetails(): bool
    {
        return filled($this->payout_method)
            && filled($this->payout_account_name)
            && filled($this->payout_account_number);
    }

    public function hasRequiredPayoutDocuments(): bool
    {
        if (! $this->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_FRONT)
            || ! $this->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_BACK)
            || ! $this->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF)) {
            return false;
        }

        return ! $this->isTeam() || $this->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_BUSINESS_PERMIT);
    }

    public function hasUploadedPayoutDocument(string $documentType): bool
    {
        if ($this->relationLoaded('documents')) {
            return $this->documents->contains('document_type', $documentType);
        }

        return $this->documents()->where('document_type', $documentType)->exists();
    }

    public function latestPayoutDocument(string $documentType): ?CleanerApplicationDocument
    {
        if ($this->relationLoaded('documents')) {
            return $this->documents
                ->where('document_type', $documentType)
                ->sortByDesc('created_at')
                ->first();
        }

        return $this->documents()
            ->where('document_type', $documentType)
            ->latest()
            ->first();
    }

    public function requiredPayoutDocumentTypes(): array
    {
        $types = [
            CleanerApplicationDocument::TYPE_VALID_ID_FRONT,
            CleanerApplicationDocument::TYPE_VALID_ID_BACK,
            CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF,
        ];

        if ($this->isTeam()) {
            $types[] = CleanerApplicationDocument::TYPE_BUSINESS_PERMIT;
        }

        return $types;
    }

    public function isPayoutReadyForAdmin(): bool
    {
        return $this->hasPayoutDetails()
            && $this->hasRequiredPayoutDocuments()
            && ($this->payout_verification_status ?: self::PAYOUT_VERIFICATION_PENDING) === self::PAYOUT_VERIFICATION_VERIFIED;
    }

    public function payoutSetupWarnings(): array
    {
        $warnings = [];

        if (! $this->hasPayoutDetails()) {
            $warnings[] = 'Payout details missing';
        }

        if (! $this->hasRequiredPayoutDocuments()) {
            $warnings[] = 'Documents not verified';
        }

        if ($this->hasPayoutDetails()
            && $this->hasRequiredPayoutDocuments()
            && ($this->payout_verification_status ?: self::PAYOUT_VERIFICATION_PENDING) !== self::PAYOUT_VERIFICATION_VERIFIED) {
            $warnings[] = $this->payout_verification_status === self::PAYOUT_VERIFICATION_REJECTED
                ? 'Payout verification rejected'
                : 'Payout details not verified';
        }

        return $warnings;
    }

    public function payoutHoldReason(): ?string
    {
        $warnings = $this->payoutSetupWarnings();

        if ($warnings === []) {
            return null;
        }

        return implode('; ', $warnings);
    }

    public function availabilityLabel(): string
    {
        return self::AVAILABILITY_LABELS[$this->availability_status ?: self::AVAILABILITY_AVAILABLE] ?? Str::of((string) $this->availability_status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function availabilityBadgeClass(): string
    {
        return match ($this->availability_status ?: self::AVAILABILITY_AVAILABLE) {
            self::AVAILABILITY_AVAILABLE => 'bg-emerald-100 text-emerald-700',
            self::AVAILABILITY_PAUSED => 'bg-amber-100 text-amber-700',
            self::AVAILABILITY_UNAVAILABLE => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function isAvailableForAssignment(): bool
    {
        return ($this->availability_status ?: self::AVAILABILITY_AVAILABLE) === self::AVAILABILITY_AVAILABLE;
    }

    public function activeAssignmentCountForDate(mixed $scheduledDate, ?int $exceptBookingId = null): int
    {
        return $this->bookings()
            ->whereDate('scheduled_date', Booking::normalizeScheduleDate($scheduledDate))
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($query) {
                $query
                    ->whereNull('provider_assignment_status')
                    ->orWhereIn('provider_assignment_status', ['pending', 'accepted']);
            })
            ->when($exceptBookingId !== null, fn ($query) => $query->where('id', '!=', $exceptBookingId))
            ->count();
    }

    public function hasDailyCapacityFor(mixed $scheduledDate, ?int $exceptBookingId = null): bool
    {
        if (! $this->max_daily_bookings) {
            return true;
        }

        return $this->activeAssignmentCountForDate($scheduledDate, $exceptBookingId) < $this->max_daily_bookings;
    }

    public function hasScheduleConflictFor(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $targetDurationMinutes = null,
        ?int $exceptBookingId = null,
        int $requiredCleaners = 1,
    ): bool {
        // A team can handle overlapping bookings when different approved
        // members are available. Keep the legacy provider-level check for
        // older team applications that have not created a member roster yet.
        if ($this->isTeam() && $this->teamMembers()->exists()) {
            $availableMembers = $this->approvedTeamMembers()
                ->where('availability_status', CleanerTeamMember::AVAILABILITY_AVAILABLE)
                ->get();

            $freeMembers = $availableMembers->reject(fn (CleanerTeamMember $member): bool => $member->hasScheduleConflictFor(
                $scheduledDate,
                $scheduledTime,
                $targetDurationMinutes,
                $exceptBookingId,
            ));

            return $freeMembers->count() < max(1, $requiredCleaners);
        }

        return $this->bookings()
            ->whereIn('status', Booking::ACTIVE_SCHEDULE_STATUSES)
            ->where(function ($query): void {
                $query->whereNull('provider_assignment_status')
                    ->orWhereIn('provider_assignment_status', ['pending', 'accepted']);
            })
            ->whereDate('scheduled_date', Booking::normalizeScheduleDate($scheduledDate))
            ->when($exceptBookingId !== null, fn ($query) => $query->where('id', '!=', $exceptBookingId))
            ->with('service:id,slug')
            ->get(['id', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'service_id'])
            ->contains(fn (Booking $booking): bool => Booking::assignmentWindowsOverlap(
                $scheduledDate,
                $scheduledTime,
                $targetDurationMinutes,
                $booking->scheduled_date,
                $booking->scheduled_time,
                (int) ($booking->duration_minutes ?: Service::durationForSlug($booking->service?->slug)),
            ));
    }

    public function dailyCapacityLabel(mixed $scheduledDate, ?int $exceptBookingId = null): ?string
    {
        if (! $this->max_daily_bookings) {
            return null;
        }

        return $this->activeAssignmentCountForDate($scheduledDate, $exceptBookingId).'/'.$this->max_daily_bookings.' assigned';
    }

    public static function normalizeCoverageText(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replace(['_', '-'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    /**
     * Return the normalized service-offering keys stored by the application.
     *
     * Applications created by the current form store the human-readable labels,
     * while older records and tests may contain the original keys or an array.
     * Keeping the normalization here makes the booking checks consistent for
     * both formats.
     */
    public function serviceOfferingKeys(): array
    {
        $values = is_array($this->services_offered)
            ? $this->services_offered
            : (preg_split('/[,;|]+/', (string) $this->services_offered) ?: []);

        $normalizedLabels = collect(self::SERVICE_OFFERINGS)
            ->mapWithKeys(fn (string $label, string $key): array => [
                self::normalizeCoverageText($label) => $key,
            ]);

        return collect($values)
            ->flatMap(function ($value) use ($normalizedLabels): array {
                $value = (string) $value;

                if (array_key_exists($value, self::SERVICE_OFFERINGS)) {
                    return [$value];
                }

                $normalized = self::normalizeCoverageText($value);

                return $normalizedLabels->has($normalized)
                    ? [$normalizedLabels->get($normalized)]
                    : [$normalized];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function offersService(?string $serviceSlug): bool
    {
        $catalogSlug = Service::catalogSlug($serviceSlug);

        $requiredOffering = match ($catalogSlug) {
            'basic', 'weeklymaintenance' => 'basic_cleaning',
            'deep' => 'deep_cleaning',
            'moveinout' => 'move_in_move_out_cleaning',
            'postconstruction' => 'post_construction_cleaning',
            'commercial', 'office-basic', 'office-deep' => 'office_cleaning',
            default => null,
        };

        if (! $requiredOffering) {
            return false;
        }

        $offeredServices = $this->serviceOfferingKeys();

        // Preserve compatibility with applications submitted before the
        // service-specific checklist existed.
        if (in_array('residential cleaning', $offeredServices, true)) {
            return $requiredOffering !== 'office_cleaning';
        }

        return in_array($requiredOffering, $offeredServices, true);
    }

    public function issueActivationToken(int $expiresInDays = 7): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'activation_token_hash' => self::activationTokenHash($token),
            'activation_token_expires_at' => now()->addDays($expiresInDays),
        ])->save();

        return $token;
    }

    public static function activationTokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function issueTrackingToken(int $expiresInDays = 365): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'tracking_token_previous_hash' => $this->tracking_token_hash,
            'tracking_token_previous_expires_at' => $this->tracking_token_expires_at,
            'tracking_token_hash' => self::trackingTokenHash($token),
            'tracking_token_expires_at' => now()->addDays($expiresInDays),
        ])->save();

        return $token;
    }

    public static function trackingTokenHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
