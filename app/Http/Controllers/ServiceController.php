<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceAddOn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('sort_order')->orderBy('price')->orderBy('created_at', 'desc')->get();
        $addOns = ServiceAddOn::orderBy('sort_order')->orderBy('label')->get();
        $packageCatalog = Service::packageCatalog();

        return view('admin.services.index', compact('services', 'addOns', 'packageCatalog'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'price' => 'required|numeric|min:1',
            'duration_minutes' => 'required|integer|min:30|max:720',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'scope_status' => ['nullable', Rule::in(Service::SCOPE_STATUSES)],
            'scope_manual_review_above_limit' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            ...$this->scopeValidationRules($request),
        ]);

        $slug = Service::canonicalSlugForName($request->name);

        if (Service::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A service with this name already exists.',
            ]);
        }

        $packageMetadata = Service::packageMetadataFor($slug);

        Service::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => filled($request->description)
                ? $request->description
                : ($packageMetadata['default_description'] ?? null),
            'image_path' => $this->storeServiceImage($request),
            'price' => $request->price,
            'duration_minutes' => $request->duration_minutes,
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : 0,
            'scope_max_floor_area' => $request->input('scope_max_floor_area'),
            'scope_cleaner_count' => $request->input('scope_cleaner_count', 1),
            'scope_status' => $request->input('scope_status', 'provisional'),
            'scope_manual_review_above_limit' => $request->has('scope_manual_review_above_limit'),
            'scope_included_areas' => $request->input('scope_included_areas'),
            'scope_included_tasks' => $request->input('scope_included_tasks'),
            'scope_excluded_tasks' => $request->input('scope_excluded_tasks'),
            'scope_condition_limits' => $request->input('scope_condition_limits'),
            'scope_equipment_policy' => $request->input('scope_equipment_policy'),
            'scope_access_limits' => $request->input('scope_access_limits'),
            'scope_extra_work_policy' => $request->input('scope_extra_work_policy'),
            'scope_acceptance_criteria' => $request->input('scope_acceptance_criteria'),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service added successfully.');
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        $servicePackage = Service::packageMetadataFor($service->slug);

        return view('admin.services.edit', compact('service', 'servicePackage'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'price' => 'required|numeric|min:1',
            'duration_minutes' => 'required|integer|min:30|max:720',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'scope_status' => ['nullable', Rule::in(Service::SCOPE_STATUSES)],
            'scope_manual_review_above_limit' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            ...$this->scopeValidationRules($request),
        ]);

        $slug = Service::canonicalSlugForName($request->name);

        if (Service::where('slug', $slug)->where('id', '!=', $service->id)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A service with this name already exists.',
            ]);
        }

        $packageMetadata = Service::packageMetadataFor($slug);

        $service->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => filled($request->description)
                ? $request->description
                : ($packageMetadata['default_description'] ?? null),
            'price' => $request->price,
            'duration_minutes' => $request->duration_minutes,
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : ($service->sort_order ?? 0),
            'scope_max_floor_area' => $request->input('scope_max_floor_area'),
            'scope_cleaner_count' => $request->input('scope_cleaner_count', 1),
            'scope_status' => $request->input('scope_status', 'provisional'),
            'scope_manual_review_above_limit' => $request->has('scope_manual_review_above_limit'),
            'scope_included_areas' => $request->input('scope_included_areas'),
            'scope_included_tasks' => $request->input('scope_included_tasks'),
            'scope_excluded_tasks' => $request->input('scope_excluded_tasks'),
            'scope_condition_limits' => $request->input('scope_condition_limits'),
            'scope_equipment_policy' => $request->input('scope_equipment_policy'),
            'scope_access_limits' => $request->input('scope_access_limits'),
            'scope_extra_work_policy' => $request->input('scope_extra_work_policy'),
            'scope_acceptance_criteria' => $request->input('scope_acceptance_criteria'),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $this->updateServiceImage($request, $service);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    private function scopeValidationRules(Request $request): array
    {
        $approved = $request->input('scope_status') === 'approved';
        $textRules = $approved
            ? ['required', 'string', 'max:5000']
            : ['nullable', 'string', 'max:5000'];

        return [
            'scope_max_floor_area' => $approved
                ? ['required', 'integer', 'min:10', 'max:1000']
                : ['nullable', 'integer', 'min:10', 'max:1000'],
            'scope_cleaner_count' => $approved
                ? ['required', 'integer', 'min:1', 'max:20']
                : ['nullable', 'integer', 'min:1', 'max:20'],
            'scope_included_areas' => $textRules,
            'scope_included_tasks' => $textRules,
            'scope_excluded_tasks' => $textRules,
            'scope_condition_limits' => $textRules,
            'scope_equipment_policy' => $textRules,
            'scope_access_limits' => $textRules,
            'scope_extra_work_policy' => $textRules,
            'scope_acceptance_criteria' => $textRules,
        ];
    }

    private function storeServiceImage(Request $request): ?string
    {
        return $request->hasFile('image')
            ? $request->file('image')->store('services', config('filesystems.public_uploads_disk'))
            : null;
    }

    private function updateServiceImage(Request $request, Service $service): void
    {
        if ($request->boolean('remove_image') && filled($service->image_path)) {
            Storage::disk(config('filesystems.public_uploads_disk'))->delete($service->image_path);
            $service->update(['image_path' => null]);
        }

        if ($request->hasFile('image')) {
            if (filled($service->image_path)) {
                Storage::disk(config('filesystems.public_uploads_disk'))->delete($service->image_path);
            }

            $service->update(['image_path' => $this->storeServiceImage($request)]);
        }
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        if ($service->is_active) {
            $service->update(['is_active' => false]);

            return redirect()->route('admin.services.index')
                ->with('success', 'Service deactivated successfully. It is hidden from new bookings.');
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    public function reactivate(Service $service)
    {
        $service->update(['is_active' => true]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service reactivated successfully. Clients can book it again.');
    }

    public function storeAddOn(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0|max:999999.99',
            'pricing_unit' => 'nullable|string|max:60',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $key = ServiceAddOn::keyForLabel($validated['label']);

        if (ServiceAddOn::where('key', $key)->exists()) {
            throw ValidationException::withMessages([
                'label' => 'An add-on with this label already exists.',
            ]);
        }

        ServiceAddOn::create([
            'key' => $key,
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'pricing_unit' => $validated['pricing_unit'] ?? 'per booking',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Add-on added successfully.');
    }

    public function updateAddOn(Request $request, ServiceAddOn $addOn)
    {
        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:100',
                Rule::unique('service_add_ons', 'label')->ignore($addOn->id),
            ],
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0|max:999999.99',
            'pricing_unit' => 'nullable|string|max:60',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $addOn->update([
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'pricing_unit' => $validated['pricing_unit'] ?? 'per booking',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Add-on updated successfully.');
    }

    public function destroyAddOn(ServiceAddOn $addOn)
    {
        if ($addOn->is_active) {
            $addOn->update(['is_active' => false]);

            return redirect()->route('admin.services.index')
                ->with('success', 'Add-on deactivated successfully. It is hidden from new bookings.');
        }

        $addOn->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Add-on deleted successfully.');
    }
}
