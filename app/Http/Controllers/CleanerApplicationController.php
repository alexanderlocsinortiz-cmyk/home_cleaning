<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class CleanerApplicationController extends Controller
{
    public function create()
    {
        $coverageAreas = config('cleanflow.bukidnon_coverage_areas', []);
        $serviceOfferings = CleanerApplication::SERVICE_OFFERINGS;
        $governmentIdTypes = CleanerApplication::GOVERNMENT_ID_LABELS;
        $availableDays = CleanerApplication::AVAILABLE_DAY_LABELS;

        return view('cleaner-applications.create', compact(
            'availableDays',
            'coverageAreas',
            'governmentIdTypes',
            'serviceOfferings',
        ));
    }

    public function store(Request $request)
    {
        $coverageAreas = config('cleanflow.bukidnon_coverage_areas', []);

        $validated = $request->validate([
            'applicant_type' => ['required', Rule::in([
                CleanerApplication::TYPE_INDIVIDUAL,
                CleanerApplication::TYPE_TEAM,
            ])],
            'individual_name' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'string', 'max:150'],
            'team_business_name' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:150'],
            'contact_person' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'digits_between:1,30'],
            'date_of_birth' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'date', 'before:today'],
            'individual_current_address' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_INDIVIDUAL, 'string', 'max:255'],
            'business_address' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'business_logo' => ['nullable', 'image', 'max:5120'],
            'coverage_mode' => ['required', Rule::in(['all', 'specific'])],
            'coverage_barangays' => ['nullable', 'array', 'required_if:coverage_mode,specific'],
            'coverage_barangays.*' => ['string', Rule::in(array_keys($coverageAreas))],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'team_size' => ['nullable', 'required_if:applicant_type,'.CleanerApplication::TYPE_TEAM, 'integer', 'min:2', 'max:100'],
            'services_offered' => ['required', 'array', 'min:1'],
            'services_offered.*' => ['string', Rule::in(array_keys(CleanerApplication::SERVICE_OFFERINGS))],
            'government_id_type' => ['required', Rule::in(array_keys(CleanerApplication::GOVERNMENT_ID_LABELS))],
            'government_id_number' => ['required', 'digits_between:1,100'],
            'government_id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
        ]);

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
            $validated['government_id_document'],
            $validated['individual_current_address'],
            $validated['individual_name'],
            $validated['nbi_clearance_document'],
            $validated['profile_photo'],
            $validated['selfie_with_id'],
            $validated['team_business_name'],
        );

        $application = CleanerApplication::create($validated);

        $this->storeApplicationFile($application, 'profile_photo', $request->file('profile_photo'));
        $this->storeApplicationFile($application, 'business_logo', $request->file('business_logo'));
        $this->storeApplicationFile($application, 'government_id_document', $request->file('government_id_document'));
        $this->storeApplicationFile($application, 'nbi_clearance_document', $request->file('nbi_clearance_document'));
        $this->storeApplicationFile($application, 'selfie_with_id', $request->file('selfie_with_id'));

        return redirect()
            ->route('cleaner-applications.create')
            ->with('success', 'Application submitted. CleanFlow will review your details before allowing bookings.');
    }

    private function storeApplicationFile(CleanerApplication $application, string $field, ?UploadedFile $file): void
    {
        if (! $file) {
            return;
        }

        $path = $file->store('cleaner-applications/'.$application->id, config('filesystems.private_uploads_disk'));

        $application->forceFill([
            $field.'_path' => $path,
            $field.'_original_filename' => $file->getClientOriginalName(),
        ])->save();
    }
}
