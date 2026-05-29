<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceAddOn;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('created_at', 'desc')->get();
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
            'price' => 'required|numeric|min:1',
            'duration_minutes' => 'required|integer|min:30|max:720',
            'is_active' => 'nullable|boolean',
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
            'price' => $request->price,
            'duration_minutes' => $request->duration_minutes,
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
            'price' => 'required|numeric|min:1',
            'duration_minutes' => 'required|integer|min:30|max:720',
            'is_active' => 'nullable|boolean',
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
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
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
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $addOn->update([
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
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
