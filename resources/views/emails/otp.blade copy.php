<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a2e;
            background-color: #f0f2f5;
            padding: 20px;
        }
        .email-wrapper {
            max-width: 500px;
            margin: 0 auto;
        }
        .email-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .header {
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .header-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        .content {
            background: white;
            padding: 40px 30px;
            text-align: center;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #667eea;
            background: #f8f9fc;
            padding: 20px 30px;
            border-radius: 12px;
            margin: 20px 0;
            display: inline-block;
        }
        .message {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
            margin-top: 20px;
            text-align: left;
            font-size: 13px;
            color: #856404;
        }
        .footer {
            text-align: center;
            padding: 25px;
            background: #f8f9fc;
        }
        .footer p {
            font-size: 12px;
            color: #888;
        }
        .footer .company {
            font-weight: 600;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <div class="header-icon">&#128274;</div>
                <h1>OTP Verification</h1>
            </div>

            <div class="content">
                <p>Your One-Time Password (OTP) is:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p class="message">Use this code to verify your account. This code will expire in 10 minutes.</p>
                <div class="warning">
                    <strong>Security Notice:</strong> Never share this code with anyone. WLMMA will never ask for your OTP.
                </div>
            </div>

            <div class="footer">
                <p class="company">WLMMA Tourism Company</p>
                <p>&copy; {{ date('Y') }} WLMMA. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
