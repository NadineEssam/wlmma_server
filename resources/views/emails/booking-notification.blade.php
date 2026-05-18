<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $notificationType === 'confirmation' ? 'Booking Confirmation' : 'Booking Update' }}
    </title>
</head>

<body style="margin:0; padding:0; background-color:#FFFCFC; font-family:Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFCFC;">
        <tr>
            <td align="center">

                <!-- Logo -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:650px;">
                    <tr>
                        <td align="center" style="padding:60px 20px 40px;">
                            <img src="{{ env('APP_URL') }}public/EmailTemplate-Wlmma/Image/logo.png" alt="WLMMA"
                                width="150" style="display:block; max-width:150px; height:auto;">
                        </td>
                    </tr>
                </table>

                <!-- Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:650px;">
                    <tr>
                        <td style="padding:0 20px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background-color:#FFFFFF; border-radius:24px;">

                                <!-- Header -->
                                <tr>
                                    <td style="padding:32px 24px 16px; text-align:center;">
                                        <h1 style="margin:0; font-size:26px; font-weight:700; color:#0D0D0D;">
                                            @if ($notificationType === 'confirmation')
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
                                    </td>
                                </tr>

                                <!-- Greeting -->
                                <tr>
                                    <td style="padding:0 24px 24px;">
                                        <p style="margin:0 0 8px; font-size:18px; font-weight:600; color:#0D0D0D;">
                                            Hello {{ $customerName ?? 'Valued Customer' }},
                                        </p>

                                        <p style="margin:0; font-size:15px; color:#0D0D0D70;">
                                            @if ($notificationType === 'confirmation')
                                                Your booking has been confirmed. Below are your details.
                                            @elseif($notificationType === 'payment_completed')
                                                Your payment was processed successfully.
                                            @elseif($notificationType === 'cancellation')
                                                Your booking has been cancelled.
                                            @elseif($notificationType === 'waiting_list')
                                                You have been added to the waiting list.
                                            @elseif($notificationType === 'no_seats')
                                                Unfortunately, there are no available seats.
                                            @elseif($notificationType === 'trip_completed')
                                                Thank you for joining us! We hope you enjoyed your trip.
                                            @else
                                                Here is an update regarding your booking.
                                            @endif
                                        </p>
                                    </td>
                                </tr>

                                <!-- Booking Details -->
                                <tr>
                                    <td style="padding:16px 24px; border-top:1px dashed #0D0D0D15;">
                                        <h2 style="margin:0 0 12px; font-size:20px; font-weight:700; color:#0D0D0D;">
                                            Booking Details
                                        </h2>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="background-color:#00909004; border:1px dashed #00909015; border-radius:16px;">
                                            <tr>
                                                <td style="padding:16px;">

                                                    <!-- Activity -->
                                                    <table width="100%">
                                                        <tr>
                                                            <td style="font-size:16px; color:#0D0D0D70;">
                                                                Activity
                                                            </td>
                                                            <td align="right"
                                                                style="font-size:18px; font-weight:600; color:#0D0D0D;">
                                                                {{ $activityTitle }}
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <!-- Status -->
                                                    <table width="100%" style="margin-top:12px;">
                                                        <tr>
                                                            <td style="font-size:16px; color:#0D0D0D70;">
                                                                Status
                                                            </td>
                                                            <td align="right"
                                                                style="font-size:16px; font-weight:700;
                                                            color:
                                                            @if ($bookingStatus === 'confirmed' || $bookingStatus === 'completed') #1E7E34
                                                            @elseif($bookingStatus === 'cancelled')
                                                                #B02A37
                                                            @else
                                                                #856404 @endif;">
                                                                {{ ucfirst($bookingStatus) }}
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Note -->
                                <tr>
                                    <td style="padding:16px 24px;">
                                        <p style="margin:0; font-size:14px; color:#0D0D0D70;">
                                            If you have any questions, please contact our support team.
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:650px;">
                    <tr>
                        <td align="center" style="padding:30px 20px;">
                            <p style="margin:0; font-size:14px; font-weight:600; color:#FF5B4B;">
                                WLMMA Tourism Company
                            </p>
                            <p style="margin:6px 0 0; font-size:13px; color:#0D0D0D70;">
                                © {{ date('Y') }} WLMMA. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>
