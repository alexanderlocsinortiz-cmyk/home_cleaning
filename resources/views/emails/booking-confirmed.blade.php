<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #1D9E75; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .booking-box { background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .label { color: #64748b; font-size: 14px; }
        .value { font-weight: bold; color: #1e293b; font-size: 14px; }
        .badge { background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Booking Confirmed!</h1>
            <p style="margin:5px 0 0 0; opacity:0.9;">Your cleaning service is confirmed</p>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
            <p>Great news! Your booking has been <strong>confirmed</strong> by our admin. Please be ready on your scheduled date.</p>
            
            <div class="booking-box">
                <div class="row">
                    <span class="label">Booking #</span>
                    <span class="value">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="row">
                    <span class="label">Service Type</span>
                    <span class="value">{{ $booking->service_label }}</span>
                </div>
                <div class="row">
                    <span class="label">Address</span>
                    <span class="value">{{ $booking->street_address }}, {{ ucfirst($booking->barangay) }}</span>
                </div>
                <div class="row">
                    <span class="label">Scheduled Date</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y') }}</span>
                </div>
                <div class="row">
                    <span class="label">Scheduled Time</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</span>
                </div>
                @if($booking->staff)
                <div class="row">
                    <span class="label">Assigned Staff</span>
                    <span class="value">{{ $booking->staff->first_name }} {{ $booking->staff->last_name }}</span>
                </div>
                @endif
                <div class="row">
                    <span class="label">Price</span>
                    <span class="value">₱{{ number_format($booking->price, 2) }}</span>
                </div>
                <div class="row" style="border:0;">
                    <span class="label">Status</span>
                    <span class="badge">Confirmed</span>
                </div>
            </div>

            <p style="color:#64748b; font-size:14px;">⚠️ Payment is collected on-site after the service is completed.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Home Cleaning Service — Valencia City, Bukidnon</p>
        </div>
    </div>
</body>
</html>

