<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1D9E75, #0F6E56); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0 0 5px 0; font-size: 28px; }
        .header p { margin: 0; opacity: 0.9; font-size: 15px; }
        .body { padding: 40px 30px; }
        .body p { color: #4a5568; line-height: 1.6; font-size: 15px; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 30px 0; }
        .footer { background: #1e293b; padding: 25px; text-align: center; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; }
        .footer .brand { color: white; font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .info-box { background: #E1F5EE; border-left: 4px solid #1D9E75; border-radius: 4px; padding: 15px; margin: 20px 0; }
        .info-box p { margin: 0; color: #0F6E56; font-size: 14px; }
        .code-box { background: #f8fafc; border: 1px solid #dbe3ed; border-radius: 12px; padding: 18px; text-align: center; margin: 24px 0; }
        .code-label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.18em; font-weight: bold; }
        .code-value { color: #0f172a; font-size: 32px; letter-spacing: 0.42em; font-weight: bold; margin-top: 10px; padding-left: 0.42em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Home Cleaning Service</h1>
            <p>Professional Home Cleaning Services</p>
            <p style="font-size:13px; margin-top:5px; opacity:0.8;">Valencia City, Bukidnon</p>
        </div>

        <div class="body">
            <p>Hi <strong>{{ $user->first_name }}</strong>,</p>
            <p>Welcome to <strong>Home Cleaning Service</strong>. We are excited to have you on board.</p>
            <p>Use this verification code to confirm your email address and activate your account.</p>

            <div class="code-box">
                <div class="code-label">Verification Code</div>
                <div class="code-value">{{ $code }}</div>
            </div>

            <div class="info-box">
                <p>This verification code will expire in <strong>{{ $expiresInMinutes }}</strong> minutes. If it expires, request a new code from the verification page.</p>
            </div>

            <hr class="divider">

            <p style="font-size:14px; color:#94a3b8;">Enter the code on the email verification screen after signing in. If you did not create a Home Cleaning Service account, no further action is required.</p>
        </div>

        <div class="footer">
            <p class="brand">Home Cleaning Service</p>
            <p>Professional Home Cleaning Services</p>
            <p>Valencia City, Bukidnon, Philippines</p>
            <p style="margin-top:10px;">&copy; {{ date('Y') }} Home Cleaning Service. All rights reserved.</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
