<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Status Update</title>
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

                <!-- Main Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:650px;">
                    <tr>
                        <td style="padding:0 20px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background-color:#FFFFFF; border-radius:24px;">

                                <!-- Header -->
                                <tr>
                                    <td style="padding:32px 24px 16px; text-align:center;">
                                        <h1 style="margin:0; font-size:26px; font-weight:700; color:#0D0D0D;">
                                            Activity Status Update
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Activity Info -->
                                <tr>
                                    <td style="padding:0 24px 24px;">
                                        <h2 style="margin:0 0 8px; font-size:22px; font-weight:700; color:#0D0D0D;">
                                            {{ $activityTitle }}
                                        </h2>

                                        <p style="margin:0; font-size:15px; color:#0D0D0D70;">
                                            Here is the latest update regarding your activity.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Status Box -->
                                <tr>
                                    <td style="padding:16px 24px; border-top:1px dashed #0D0D0D15;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="background-color:#00909004; border:1px dashed #00909015; border-radius:16px;">
                                            <tr>
                                                <td style="padding:16px;">

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="font-size:16px; color:#0D0D0D70;">
                                                                Status
                                                            </td>
                                                            <td align="right"
                                                                style="font-size:18px; font-weight:700;
                                                            color:
                                                            @if (strtolower($status) === 'confirmed' || strtolower($status) === 'approved') #1E7E34
                                                            @elseif(strtolower($status) === 'cancelled' || strtolower($status) === 'rejected')
                                                                #B02A37
                                                            @elseif(strtolower($status) === 'refunded')
                                                                #004085
                                                            @else
                                                                #856404 @endif;">
                                                                {{ strtoupper($status) }}
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Wallet Balance -->
                                @if ($refundAmount)
                                    <tr>
                                        <td style="padding:16px 24px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                style="background-color:#E8F4FD; border-radius:16px;">
                                                <tr>
                                                    <td style="padding:16px;">
                                                        <p style="margin:0 0 6px; font-size:14px; color:#0D0D0D70;">
                                                            The refunded amount
                                                        </p>
                                                        <p
                                                            style="margin:0; font-size:24px; font-weight:700; color:#FF5B4B;">
                                                            {{ $refundAmount }} SAR
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                <!-- Additional Message -->
                                {{-- @if ($additionalMessage)
                                    <tr>
                                        <td style="padding:0 24px 24px;">
                                            <p style="margin:0; font-size:15px; color:#0D0D0D70;">
                                                {{ $additionalMessage }}
                                            </p>
                                        </td>
                                    </tr>
                                @endif --}}

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
