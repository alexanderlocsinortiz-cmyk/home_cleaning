@php($serviceImage = $serviceImage ?? null)

<div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="h-28 w-full shrink-0 overflow-hidden rounded-2xl border border-white bg-white shadow-sm sm:w-44">
            <img src="{{ $serviceImage?->image_url ?? asset('images/services/optimized/basic.jpg') }}" alt="{{ $serviceImage?->image_alt ?? 'Service image preview' }}" class="h-full w-full object-cover" data-service-image-preview>
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-extrabold text-slate-900">Service image</div>
            <p class="mt-1 text-xs leading-5 text-slate-600">Use a clear image that matches this service. It appears on the public catalog, booking selector, and mobile service feed.</p>
            <label class="mt-3 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500" for="service-image">Upload or replace image</label>
            <input id="service-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-100 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700" data-service-image-input>
            <p class="mt-1 text-[11px] text-slate-500">JPG, PNG, or WebP. Maximum 5 MB. Leave empty to keep the current image.</p>
            @error('image')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            @if($serviceImage?->image_path)
                <label class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    Remove custom image and restore the matching default illustration
                </label>
            @else
                <div class="mt-3 text-xs font-semibold text-emerald-700"><i class="fas fa-check-circle mr-1"></i>Using the bundled matching illustration until you upload a custom image.</div>
            @endif
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-service-image-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    const preview = input.closest('.rounded-2xl')?.querySelector('[data-service-image-preview]');

                    if (file && preview) {
                        preview.src = URL.createObjectURL(file);
                    }
                });
            });
        </script>
    @endpush
@endonce
