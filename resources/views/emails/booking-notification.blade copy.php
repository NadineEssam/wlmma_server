<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationType === 'confirmation' ? 'Booking Confirmation' : 'Booking Update' }}</title>
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
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .content {
            background: white;
            padding: 40px 30px;
        }
        .greeting {
            margin-bottom: 30px;
        }
        .greeting h2 {
            color: #1a1a2e;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .greeting p {
            color: #666;
            font-size: 15px;
        }
        .booking-card {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .booking-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8ebf0;
        }
        .booking-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
        }
        .booking-status {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .activity-title {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 25px;
        }
        .info-box p {
            font-size: 14px;
            color: #1a1a2e;
            margin: 0;
        }
        .footer {
            text-align: center;
            padding: 30px;
            background: #f8f9fc;
        }
        .footer p {
            font-size: 13px;
            color: #888;
            margin: 5px 0;
        }
        .footer .company {
            font-weight: 600;
            color: #667eea;
        }
        @media (max-width: 480px) {
            .header h1 {
                font-size: 24px;
            }
            .content {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <div class="header-icon">
                    @if($notificationType === 'confirmation' || $notificationType === 'payment_completed')
                        &#10003;
                    @elseif($notificationType === 'cancellation')
                        &#10007;
                    @elseif($notificationType === 'waiting_list')
                        &#8987;
                    @else
                        &#9993;
                    @endif
                </div>
                <h1>
                    @if($notificationType === 'confirmation')
                        Booking Confirmed
                    @elseif($notificationType === 'payment_completed')
                        Payment Completed
                    @elseif($notificationType === 'cancellation')
                        Booking Cancelled
                    @elseif($notificationType === 'waiting_list')
                        Waiting List
                    @elseif($notificationType === 'no_seats')
                        No Available Seats
                    @elseif($notificationType === 'trip_completed')
                        Trip Completed
                    @else
                        Booking Update
                    @endif
                </h1>
            </div>

            <div class="content">
                <div class="greeting">
                    <h2>Hello {{ $customerName ?? 'Valued Customer' }},</h2>
                    @if($notificationType === 'confirmation')
                        <p>Your booking has been confirmed! Here are your booking details.</p>
                    @elseif($notificationType === 'payment_completed')
                        <p>Your payment has been successfully processed.</p>
                    @elseif($notificationType === 'cancellation')
                        <p>Your booking has been cancelled.</p>
                    @elseif($notificationType === 'waiting_list')
                        <p>You have been added to the waiting list for this trip.</p>
                    @elseif($notificationType === 'no_seats')
                        <p>Unfortunately, there are no available seats for this trip.</p>
                    @elseif($notificationType === 'trip_completed')
                        <p>Thank you for joining us! We hope you enjoyed your trip.</p>
                    @else
                        <p>Here is an update about your booking.</p>
                    @endif
                </div>

                <div class="booking-card">
                    <div class="booking-card-header">
                        <span class="booking-card-title">Booking Details</span>
                        <span class="booking-status
                            @if($bookingStatus === 'confirmed' || $bookingStatus === 'completed')
                                status-success
                            @elseif($bookingStatus === 'cancelled')
                                status-cancelled
                            @else
                                status-pending
                            @endif
                        ">{{ ucfirst($bookingStatus) }}</span>
                    </div>
                    <div class="activity-title">{{ $activityTitle }}</div>
                </div>

                <div class="info-box">
                    <p><strong>Note:</strong> If you have any questions about your booking, please contact our support team.</p>
                </div>
            </div>

            <div class="footer">
                <p class="company">WLMMA Tourism Company</p>
                <p>For questions, contact our support team</p>
                <p>&copy; {{ date('Y') }} WLMMA. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
