<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validSlugs = Service::where('is_active', true)->pluck('slug')->toArray();
        $validAddOns = array_keys(\App\Models\Booking::addOnCatalog());

        return [
            'service_type' => ['required', Rule::in($validSlugs)],
            'property_type' => 'required|in:house,apartment,boarding_house',
            'rooms' => 'required|integer|min:1|max:20',
            'bathrooms' => 'required|integer|min:1|max:10',
            'floor_area' => 'required|integer|min:10|max:1000',
            'add_ons' => 'nullable|array',
            'add_ons.*' => ['string', Rule::in($validAddOns)],
        ];
    }
}
