<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class CleanerApplicationController extends Controller
{
    public function create()
    {
        $coverageAreas = config('cleanflow.bukidnon_coverage_areas', []);
        $locationCenters = config('cleanflow.bukidnon_location_centers', []);
        $serviceOfferings = CleanerApplication::SERVICE_OFFERINGS;
        $governmentIdTypes = CleanerApplication::GOVERNMENT_ID_LABELS;
        $availableDays = CleanerApplication::AVAILABLE_DAY_LABELS;

        return view('cleaner-applications.create', compact(
            'availableDays',
            'coverageAreas',
            'locationCenters',
            'governmentIdTypes',
            'serviceOfferings',
        ));
    }

    public function status(string $token)
    {
        $application = CleanerApplication::query()
            ->where(function ($query) use ($token): void {
                $tokenHash = CleanerApplication::trackingTokenHash($token);

                $query->where(function ($currentTokenQuery) use ($tokenHash): void {
                    $currentTokenQuery->where('tracking_token_hash', $tokenHash)
                        ->where('tracking_token_expires_at', '>', now());
                })->orWhere(function ($previousTokenQuery) use ($tokenHash): void {
                    $previousTokenQuery->where('tracking_token_previous_hash', $tokenHash)
                        ->where('tracking_token_previous_expires_at', '>', now());
                });
            })
            ->firstOrFail();

        return view('cleaner-applications.status', compact('application'));
    }

    public function store(Request $request)
    {
        $coverageAreas = config('cleanflow.bukidnon_coverage_areas', []);
        $locationCenters = config('cleanflow.bukidnon_location_centers', []);
        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'experience_unit' => $request->input('experience_unit') ?: 'years',
        ]);

        $validated = $request->validate([
            'applicant_type' => ['required', Rule::in([
                CleanerApplication::TYPE_INDIVIDUAL,
                CleanerApplication::TYPE_TEAM,
            ])],
            'individual_name' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'string', 'max:150'],
            'team_business_name' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:150'],
            'contact_person' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('cleaner_applications', 'email')->where(fn ($query) => $query->whereIn('status', [
                    CleanerApplication::STATUS_PENDING,
                    CleanerApplication::STATUS_APPROVED,
                ])),
            ],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'date_format:Y-m-d', 'before_or_equal:'.$minimumBirthDate],
            'individual_current_address' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'string', 'max:255'],
            'business_address' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:255'],
            'location_area' => ['required', 'string', Rule::in(array_keys($locationCenters))],
            'location_latitude' => ['required', 'numeric', 'between:7.3,8.7'],
            'location_longitude' => ['required', 'numeric', 'between:124.4,125.6'],
            'location_confirmed' => ['required', 'accepted'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'business_logo' => ['nullable', 'image', 'max:5120'],
            'coverage_mode' => ['required', Rule::in(['all', 'specific'])],
            'coverage_barangays' => ['nullable', 'array', 'required_if:coverage_mode,specific'],
            'coverage_barangays.*' => ['string', Rule::in(array_keys($coverageAreas))],
            'years_experience' => [
                'required',
                'integer',
                'min:0',
                'max:720',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($request->input('experience_unit') === 'years' && (int) $value > 60) {
                        $fail('Experience cannot be more than 60 years.');
                    }
                },
            ],
            'experience_unit' => ['required', Rule::in(['years', 'months'])],
            'team_size' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'integer', 'min:2', 'max:100'],
            'services_offered' => ['required', 'array', 'min:1'],
            'services_offered.*' => ['string', Rule::in(array_keys(CleanerApplication::SERVICE_OFFERINGS))],
            'government_id_type' => ['required', Rule::in(array_keys(CleanerApplication::GOVERNMENT_ID_LABELS))],
            'government_id_number' => ['required', 'regex:/^[A-Za-z0-9][A-Za-z0-9 -]{0,99}$/'],
            'government_id_front_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'government_id_back_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'nbi_clearance_number' => ['nullable', 'string', 'max:100'],
            'nbi_clearance_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie_with_id' => ['required', 'image', 'max:5120'],
            'worked_as_cleaner_before' => ['required', 'boolean'],
            'worked_for_cleaning_company_before' => ['required', 'boolean'],
            'has_cleaning_certifications' => ['required', 'boolean'],
            'owns_cleaning_equipment' => ['required', 'boolean'],
            'available_days' => ['required', 'array', 'min:1'],
            'available_days.*' => ['string', Rule::in(array_keys(CleanerApplication::AVAILABLE_DAY_LABELS))],
            'max_daily_bookings' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'terms_certify_accurate' => ['accepted'],
            'terms_agree_verification' => ['accepted'],
            'terms_approval_not_guaranteed' => ['accepted'],
            'terms_service_standards' => ['accepted'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.unique' => 'An application for this email is already pending or approved.',
            'date_of_birth.before_or_equal' => 'Individual cleaners must be at least 18 years old to apply.',
            'date_of_birth.date_format' => 'Date of birth must use YYYY-MM-DD format.',
            'government_id_number.regex' => 'Government ID number may contain letters, numbers, spaces, and hyphens only.',
            'government_id_front_document.required' => 'A clear image or PDF of the front of your government ID is required.',
            'government_id_back_document.required' => 'A clear image or PDF of the back of your government ID is required.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
            'location_confirmed.required' => 'Confirm the provider base location before submitting your application.',
            'location_confirmed.accepted' => 'Confirm the provider base location before submitting your application.',
            'years_experience.max' => 'Enter no more than 60 years or 720 months of experience.',
        ]);

        if (User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$validated['email']])->exists()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This email already has a CleanFlow account. Use another email or contact CleanFlow admin before applying.']);
        }

        $isIndividual = $validated['applicant_type'] === CleanerApplication::TYPE_INDIVIDUAL;

        $validated['business_name'] = $isIndividual
            ? $validated['individual_name']
            : $validated['team_business_name'];
        $validated['current_address'] = $isIndividual
            ? $validated['individual_current_address']
            : $validated['business_address'];

        if ($isIndividual) {
            $validated['contact_person'] = $validated['business_name'];
            $validated['team_size'] = null;
        } else {
            $validated['date_of_birth'] = null;
        }

        $coverageBarangays = $validated['coverage_mode'] === 'all'
            ? array_values($coverageAreas)
            : collect($validated['coverage_barangays'] ?? [])->unique()->values()->all();

        $validated['coverage_barangays'] = $coverageBarangays;
        $validated['service_area'] = count($coverageBarangays) === count($coverageAreas)
            ? 'All Bukidnon cities and municipalities'
            : collect($coverageBarangays)->join(', ');
        $validated['services_offered'] = collect($validated['services_offered'])
            ->map(fn (string $service) => CleanerApplication::SERVICE_OFFERINGS[$service])
            ->join(', ');
        $validated['terms_certify_accurate'] = true;
        $validated['terms_agree_verification'] = true;
        $validated['terms_approval_not_guaranteed'] = true;
        $validated['terms_service_standards'] = true;

        unset(
            $validated['business_address'],
            $validated['business_logo'],
            $validated['coverage_mode'],
            $validated['government_id_front_document'],
            $validated['government_id_back_document'],
            $validated['individual_current_address'],
            $validated['individual_name'],
            $validated['location_confirmed'],
            $validated['nbi_clearance_document'],
            $validated['profile_photo'],
            $validated['selfie_with_id'],
            $validated['team_business_name'],
        );

        $storedPaths = [];
        $trackingToken = null;
        $failedUploadField = null;

        try {
            $application = DB::transaction(function () use ($request, $validated, &$storedPaths, &$trackingToken, &$failedUploadField): CleanerApplication {
                $application = CleanerApplication::create($validated);

                foreach ([
                    'profile_photo',
                    'business_logo',
                    'government_id_front_document',
                    'government_id_back_document',
                    'nbi_clearance_document',
                    'selfie_with_id',
                ] as $field) {
                    $failedUploadField = $field;
                    $path = $this->storeApplicationFile($application, $field, $request->file($field));

                    if ($path) {
                        $storedPaths[] = $path;
                    }
                }

                $failedUploadField = null;
                $trackingToken = $application->issueTrackingToken();

                return $application;
            });
        } catch (QueryException $exception) {
            if ($storedPaths !== []) {
                Storage::disk(config('filesystems.private_uploads_disk'))->delete($storedPaths);
            }

            $sqlState = (string) $exception->getCode();
            $isDuplicateApplication = $sqlState === '23505'
                || (in_array($sqlState, ['23000', '19'], true)
                    && preg_match('/duplicate|unique constraint|unique failed/i', $exception->getMessage()));

            if ($isDuplicateApplication) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'An active application for this email was submitted already. Check your tracking link or use another email.']);
            }

            throw $exception;
        } catch (\RuntimeException $exception) {
            if ($storedPaths !== []) {
                Storage::disk(config('filesystems.private_uploads_disk'))->delete($storedPaths);
            }

            report($exception);

            return back()
                ->withInput()
                ->withErrors([($failedUploadField ?: 'government_id_front_document') => 'We could not securely store the uploaded file. Please try again.']);
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk(config('filesystems.private_uploads_disk'))->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()
            ->route('cleaner-applications.create')
            ->with('success', 'Application submitted. CleanFlow will review your details before allowing bookings.')
            ->with('tracking_token', $trackingToken);
    }

    private function storeApplicationFile(CleanerApplication $application, string $field, ?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $disk = Storage::disk(config('filesystems.private_uploads_disk'));
        $path = $file->store('cleaner-applications/'.$application->id, config('filesystems.private_uploads_disk'));

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Unable to store cleaner application file.');
        }

        try {
            $application->forceFill([
                $field.'_path' => $path,
                $field.'_original_filename' => $file->getClientOriginalName(),
            ])->save();
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return $path;
    }
}
