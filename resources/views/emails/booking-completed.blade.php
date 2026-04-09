<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #f59e0b; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .booking-box { background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .label { color: #64748b; font-size: 14px; }
        .value { font-weight: bold; color: #1e293b; font-size: 14px; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
        .rate-btn { display: inline-block; background: #f59e0b; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌟 Service Completed!</h1>
            <p style="margin:5px 0 0 0; opacity:0.9;">Thank you for choosing Home Cleaning Service</p>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
            <p>Your cleaning service has been <strong>completed</strong>! We hope you are satisfied with our service.</p>
            
            <div class="booking-box">
                <div class="row">
                    <span class="label">Booking #</span>
                    <span class="value">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="row">
                    <span class="label">Service</span>
                    <span class="value">{{ $booking->service_label }}</span>
                </div>
                <div class="row">
                    <span class="label">Staff</span>
                    <span class="value">{{ $booking->staff->first_name ?? 'N/A' }}</span>
                </div>
                <div class="row" style="border:0;">
                    <span class="label">Amount Paid</span>
                    <span class="value">₱{{ number_format($booking->price, 2) }}</span>
                </div>
            </div>

            <p><strong>Please rate your experience!</strong> Your feedback helps us improve.</p>
            <a href="{{ url('/bookings/'.$booking->id) }}" class="rate-btn">
                ⭐ Rate Your Service
            </a>

            <p style="color:#64748b; font-size:14px; margin-top:20px;">Thank you for trusting Home Cleaning Service! We look forward to serving you again.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Home Cleaning Service — Valencia City, Bukidnon</p>
        </div>
    </div>
</body>
</html>

