<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
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
                                    <td style="padding: 40px 30px; text-align: center;">
                                        <div
                                            style="
                                        width:64px;
                                        height:64px;
                                        margin:0 auto 16px;
                                        border-radius:50%;
                                        background-color:#FF5B4B20;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        font-size:28px;
                                        color:#FF5B4B;">
                                            &#128274;
                                        </div>

                                        <h1 style="margin:0; font-size:26px; font-weight:700; color:#0D0D0D;">
                                            OTP Verification
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Message -->
                                <tr>
                                    <td style="padding:0 24px 24px; text-align:center;">
                                        <p style="margin:0; font-size:15px; color:#0D0D0D70;">
                                            Use the following One-Time Password to verify your account:
                                        </p>
                                    </td>
                                </tr>

                                <!-- OTP Box -->
                                <tr>
                                    <td align="center" style="padding:0 24px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0"
                                            style="background-color:#00909004;
                                                  border:1px dashed #00909030;
                                                  border-radius:16px;">
                                            <tr>
                                                <td
                                                    style="
                                                padding:20px 36px;
                                                font-size:32px;
                                                font-weight:700;
                                                letter-spacing:6px;
                                                color:#FF5B4B;
                                                text-align:center;">
                                                    {{ $otp }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Expiry -->
                                <tr>
                                    <td style="padding:0 24px 16px; text-align:center;">
                                        <p style="margin:0; font-size:14px; color:#0D0D0D70;">
                                            This code will expire in <strong>5 minutes</strong>.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Warning -->
                                <tr>
                                    <td style="padding:0 24px 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="background-color:#FFF3CD;
                                                  border-left:4px solid #FFC107;
                                                  border-radius:12px;">
                                            <tr>
                                                <td style="padding:14px 16px; font-size:13px; color:#856404;">
                                                    <strong>Security Notice:</strong><br>
                                                    Never share this OTP with anyone.
                                                    WLMMA will never ask for your verification code.
                                                </td>
                                            </tr>
                                        </table>
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
