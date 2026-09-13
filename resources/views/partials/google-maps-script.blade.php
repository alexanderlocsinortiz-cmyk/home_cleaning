@php
    $googleMapsApiKey = config('services.google.maps_api_key');
    $googleMapsCallback = $callback ?? null;
@endphp
<script>
    window.cleanflowGoogleMapsEnabled = @json(!empty($googleMapsApiKey));
</script>
@if($googleMapsApiKey && $googleMapsCallback)
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&callback={{ urlencode($googleMapsCallback) }}"></script>
@endif
