<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Status Update</title>
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
            max-width: 600px;
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
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        .content {
            background: white;
            padding: 40px 30px;
        }
        .status-card {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .status-card h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-refunded { background: #cce5ff; color: #004085; }
        .wallet-info {
            background: #e8f4fd;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin-top: 20px;
        }
        .wallet-info p {
            font-size: 14px;
            color: #1a1a2e;
        }
        .wallet-balance {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
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
                <h1>Activity Status Update</h1>
            </div>

            <div class="content">
                <div class="status-card">
                    <h2>{{ $activityTitle }}</h2>
                    <p>Status:
                        <span class="status-badge
                            @if(strtolower($status) === 'confirmed' || strtolower($status) === 'approved')
                                status-confirmed
                            @elseif(strtolower($status) === 'cancelled' || strtolower($status) === 'rejected')
                                status-cancelled
                            @elseif(strtolower($status) === 'refunded')
                                status-refunded
                            @else
                                status-pending
                            @endif
                        ">{{ $status }}</span>
                    </p>
                </div>

                @if($refundAmount)
                <div class="wallet-info">
                    <p>Your current wallet balance:</p>
                    <p class="wallet-balance">{{ $refundAmount }} SAR</p>
                </div>
                @endif

                @if($additionalMessage)
                <p style="margin-top: 20px; color: #666;">{{ $additionalMessage }}</p>
                @endif
            </div>

            <div class="footer">
                <p class="company">WLMMA Tourism Company</p>
                <p>&copy; {{ date('Y') }} WLMMA. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
