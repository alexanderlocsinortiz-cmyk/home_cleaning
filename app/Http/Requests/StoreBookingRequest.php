<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('notes')) {
            $this->merge(['notes' => strip_tags($this->notes)]);
        }
    }

    public function rules(): array
    {
        $validSlugs = Cache::remember('active_service_slugs', 300, fn () => Service::where('is_active', true)->pluck('slug')->toArray());
        $validAddOns = array_keys(\App\Models\Booking::addOnCatalog());
        $paymentMethods = array_keys(\App\Models\Booking::paymentMethods());
        $servicePlans = array_keys(\App\Models\Booking::servicePlans());
        $subscriptionFrequencies = array_keys(\App\Models\Booking::subscriptionFrequencyLabels());
        $validBarangays = array_keys(config('cleanflow.barangays', []));

        return [
            'service_type' => ['required', Rule::in($validSlugs)],
            'property_type' => 'required|in:house,apartment,boarding_house',
            'rooms' => 'required|integer|min:1|max:20',
            'bathrooms' => 'required|integer|min:1|max:10',
            'floor_area' => 'required|integer|min:10|max:1000',
            'add_ons' => 'nullable|array',
            'add_ons.*' => ['string', Rule::in($validAddOns)],
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
            'preferred_staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Please select a future date.',
            'service_type.required' => 'Please select a service type.',
            'barangay.required' => 'Please select your barangay.',
            'barangay.in' => 'The selected barangay is not within our service area.',
            'property_type.required' => 'Please select your property type.',
            'rooms.required' => 'Please enter number of rooms.',
            'bathrooms.required' => 'Please enter number of bathrooms.',
            'floor_area.required' => 'Please enter the floor area in square meters.',
            'payment_method.required' => 'Please choose how you want to pay for this booking.',
            'service_plan.required' => 'Please choose whether this is a one-time booking or a subscription.',
            'subscription_frequency.required' => 'Please choose a recurring schedule for the subscription plan.',
            'subscription_occurrences.required' => 'Please choose how many visits should be scheduled for the subscription plan.',
        ];
    }
}
