<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('created_at', 'desc')->get();
        $packageCatalog = Service::packageCatalog();
        $missingPackages = collect($packageCatalog)
            ->reject(fn (array $metadata, string $slug) => $services->contains('slug', $slug))
            ->map(fn (array $metadata, string $slug) => Service::packageMetadataFor($slug))
            ->values();

        return view('admin.services.index', compact('services', 'packageCatalog', 'missingPackages'));
    }

    public function create(Request $request)
    {
        $packageCatalog = Service::packageCatalog();
        $selectedTemplate = $request->query('template');
        $selectedTemplate = is_string($selectedTemplate) && array_key_exists($selectedTemplate, $packageCatalog)
            ? $selectedTemplate
            : null;
        $selectedPackage = $selectedTemplate ? Service::packageMetadataFor($selectedTemplate) : null;

        return view('admin.services.create', compact('packageCatalog', 'selectedPackage', 'selectedTemplate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:1',
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
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $service->update(['is_active' => false]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service archived successfully. Existing booking history remains intact.');
    }
}
