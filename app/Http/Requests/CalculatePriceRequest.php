<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validSlugs = Service::where('is_active', true)->pluck('slug')->toArray();
        $validAddOns = array_keys(Booking::addOnCatalog());
        $propertyTypes = array_keys(Booking::propertyTypeLabels());

        return [
            'service_type' => ['required', Rule::in($validSlugs)],
            'property_type' => ['required', Rule::in($propertyTypes)],
            // Public quote previews may omit these because they do not affect
            // the current per-square-meter price.
            'rooms' => 'nullable|integer|min:1|max:20',
            'bathrooms' => 'nullable|integer|min:1|max:10',
            'floor_area' => 'required|integer|min:10|max:1000',
            'add_ons' => 'nullable|array',
            'add_ons.*' => ['string', Rule::in($validAddOns)],
            'add_on_quantities' => 'nullable|array',
            'add_on_quantities.*' => 'nullable|integer|min:1|max:50',
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

            if (
                $service?->scopeIsApproved()
                && $service->scope_max_floor_area
                && (int) $this->input('floor_area', 0) > (int) $service->scope_max_floor_area
            ) {
                $validator->errors()->add(
                    'floor_area',
                    "This service is approved for up to {$service->scope_max_floor_area} sqm."
                );
            }
        });
    }
}
