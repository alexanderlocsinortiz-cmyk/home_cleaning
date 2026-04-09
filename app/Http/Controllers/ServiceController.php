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
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:1',
            'is_active'   => 'nullable|boolean',
        ]);

        $slug = Service::canonicalSlugForName($request->name);

        if (Service::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A service with this name already exists.',
            ]);
        }

        Service::create([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'price'       => $request->price,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service added successfully.');
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:1',
            'is_active'   => 'nullable|boolean',
        ]);

        $slug = Service::canonicalSlugForName($request->name);

        if (Service::where('slug', $slug)->where('id', '!=', $service->id)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A service with this name already exists.',
            ]);
        }

        $service->update([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'price'       => $request->price,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return redirect()->route('admin.services.index')
            ->with('success', 'Service removed successfully.');
    }
}
