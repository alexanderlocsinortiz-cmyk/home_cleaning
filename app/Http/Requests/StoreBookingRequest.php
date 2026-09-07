<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => $this->input('payment_method', 'on_site_cash'),
            'service_plan' => $this->input('service_plan', 'one_time'),
            // Keep older clients compatible while the booking form now
            // collects the real property counts.
            'rooms' => $this->input('rooms', 1),
            'bathrooms' => $this->input('bathrooms', 1),
        ]);

        if ($this->filled('notes')) {
            $this->merge(['notes' => strip_tags($this->notes)]);
        }
    }

    public function rules(): array
    {
        $validAddOns = array_keys(Booking::addOnCatalog());
        $paymentMethods = array_keys(Booking::paymentMethods());
        $servicePlans = array_keys(Booking::servicePlans());
        $subscriptionFrequencies = array_keys(Booking::subscriptionFrequencyLabels());
        $propertyTypes = array_keys(Booking::propertyTypeLabels());
        $validBarangays = array_keys(config('cleanflow.barangays', []));

        $timeSlots = ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];

        return [
            'service_type' => [
                'required',
                Rule::exists('services', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'property_type' => ['required', Rule::in($propertyTypes)],
            'rooms' => 'nullable|integer|min:1|max:20',
            'bathrooms' => 'nullable|integer|min:1|max:10',
            'floor_area' => 'required|integer|min:10|max:1000',
            'add_ons' => 'nullable|array',
            'add_ons.*' => ['string', Rule::in($validAddOns)],
            'add_on_quantities' => 'nullable|array',
            'add_on_quantities.*' => 'nullable|integer|min:1|max:50',
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'service_plan' => ['required', Rule::in($servicePlans)],
            'subscription_frequency' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('service_plan') === 'subscription'),
                Rule::in($subscriptionFrequencies),
            ],
            'subscription_occurrences' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('service_plan') === 'subscription'),
                'integer',
                'min:2',
                'max:12',
            ],
            'barangay' => ['required', Rule::in($validBarangays)],
            'street_address' => 'required|string|max:255',
            'service_latitude' => 'nullable|numeric|between:-90,90',
            'service_longitude' => 'nullable|numeric|between:-180,180',
            'preferred_staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => ['required', Rule::in($timeSlots)],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->filled(['service_type', 'property_type'])
                && ! Service::supportsPropertyType(
                    (string) $this->input('service_type'),
                    (string) $this->input('property_type')
                )
            ) {
                $validator->errors()->add(
                    'service_type',
                    'Please choose a service that matches the selected property type.'
                );
            }

            $service = Service::where('slug', $this->input('service_type'))
                ->where('is_active', true)
                ->first();

            if (! $service) {
                return;
            }

            if (
                $service?->scopeIsApproved()
                && $service->scope_max_floor_area
                && (int) $this->input('floor_area', 0) > (int) $service->scope_max_floor_area
            ) {
                $validator->errors()->add(
                    'floor_area',
                    "This service is approved for up to {$service->scope_max_floor_area} sqm. Please choose a smaller area or contact us for a manual quote."
                );
            }

            if (! $this->filled(['scheduled_date', 'scheduled_time'])) {
                return;
            }

            $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');

            try {
                $selectedStart = Carbon::parse(
                    $this->input('scheduled_date').' '.$this->input('scheduled_time'),
                    $bookingTimezone
                );
            } catch (\Throwable) {
                return;
            }

            if ($selectedStart->lessThanOrEqualTo(Carbon::now($bookingTimezone))) {
                $validator->errors()->add('scheduled_time', 'Please select a time later than the current time for today.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after_or_equal' => 'Please select today or a future date.',
            'scheduled_time.in' => 'Please select one of the available booking times.',
            'service_type.required' => 'Please select a service type.',
            'barangay.required' => 'Please select your barangay.',
            'barangay.in' => 'The selected barangay is not within our service area.',
            'property_type.required' => 'Please select your property type.',
            'floor_area.required' => 'Please enter the floor area in square meters.',
            'payment_method.required' => 'Please choose how you want to pay for this booking.',
            'service_plan.required' => 'Please choose whether this is a one-time booking or a subscription.',
            'subscription_frequency.required' => 'Please choose a recurring schedule for the subscription plan.',
            'subscription_occurrences.required' => 'Please choose how many visits should be scheduled for the subscription plan.',
        ];
    }
}
