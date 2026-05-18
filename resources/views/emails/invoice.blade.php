<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ @$isCreditNote ? 'Credit Note' : 'Invoice' }} - {{ $invoice->invoice_number }}</title>
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
                                            {{ @$isCreditNote ? 'Credit Note' : 'Invoice' }}
                                        </h1>
                                        <p style="margin:6px 0 0; font-size:14px; color:#0D0D0D70;">
                                            Invoice No: {{ $invoice->invoice_number }}
                                        </p>

                                        @if ($invoice->status === 'reported' || $invoice->status === 'cleared')
                                            <p style="margin-top:10px; font-size:13px; font-weight:600; color:#1E7E34;">
                                                &#10003; ZATCA Verified
                                            </p>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Greeting -->
                                <tr>
                                    <td style="padding:0 24px 24px;">
                                        <p style="margin:0 0 8px; font-size:18px; font-weight:600; color:#0D0D0D;">
                                            Hello {{ $customerName }},
                                        </p>
                                        <p style="margin:0; font-size:15px; color:#0D0D0D70;">
                                            @if (@$isCreditNote)
                                                Your refund has been processed. Please find your credit note details
                                                below.
                                            @else
                                                Thank you for your {{ $entityType }}. Below is your official
                                                e-invoice.
                                            @endif
                                        </p>
                                    </td>
                                </tr>

                                <!-- Invoice Details -->
                                <tr>
                                    <td style="padding:16px 24px; border-top:1px dashed #0D0D0D15;">
                                        <h2 style="margin:0 0 12px; font-size:20px; font-weight:700; color:#0D0D0D;">
                                            Invoice Details
                                        </h2>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="background-color:#00909004; border:1px dashed #00909015; border-radius:16px;">
                                            <tr>
                                                <td style="padding:16px;">

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                Invoice Number</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600; color:#0D0D0D;">
                                                                {{ $invoice->invoice_number }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                Date</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600; color:#0D0D0D;">
                                                                {{ $invoice->created_at->format('d M Y') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                Time</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600; color:#0D0D0D;">
                                                                {{ $invoice->created_at->format('H:i') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                Type</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600; color:#0D0D0D;">
                                                                {{ ucfirst($invoice->type) }} Invoice
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Amount -->
                                <tr>
                                    <td style="padding:16px 24px;">
                                        <h2 style="margin:0 0 12px; font-size:20px; font-weight:700; color:#0D0D0D;">
                                            Invoice Amount
                                        </h2>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="background-color:#00909004; border:1px dashed #00909015; border-radius:16px;">
                                            <tr>
                                                <td style="padding:16px;">

                                                    <table width="100%">
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                Subtotal</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600;">
                                                                SAR
                                                                {{ number_format($invoice->total_amount - $invoice->vat_amount, 2) }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:8px 0; font-size:15px; color:#0D0D0D70;">
                                                                VAT (15%)</td>
                                                            <td align="right"
                                                                style="padding:8px 0; font-size:16px; font-weight:600;">
                                                                SAR {{ number_format($invoice->vat_amount, 2) }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td
                                                                style="padding:12px 0; font-size:18px; font-weight:700;">
                                                                Total</td>
                                                            <td align="right"
                                                                style="padding:12px 0; font-size:22px; font-weight:700; color:#FF5B4B;">
                                                                SAR {{ number_format($invoice->total_amount, 2) }}
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- QR -->
                                @if ($invoice->qr_code && $invoice->qr_code_image)
                                    <tr>
                                        <td align="center" style="padding:16px 24px;">
                                            <img src="data:image/png;base64,{{ $invoice->qr_code_image }}"
                                                alt="ZATCA QR" width="150"
                                                style="display:block; border-radius:12px;">
                                            <p style="margin-top:8px; font-size:13px; color:#0D0D0D70;">
                                                Scan to verify invoice authenticity
                                            </p>
                                        </td>
                                    </tr>
                                @endif

                                <!-- Download -->
                                <!--<tr>-->
                                <!--    <td align="center" style="padding:24px;">-->
                                <!--        <a href="{{ config('app.url') }}/invoices/{{ $invoice->id }}/pdf"-->
                                <!--           style="display:inline-block; background-color:#FF5B4B; color:#FFFFFF;-->
                    <!--           text-decoration:none; padding:14px 32px; border-radius:10px;-->
                    <!--           font-size:15px; font-weight:600;">-->
                                <!--            ⬇ Download PDF Invoice-->
                                <!--        </a>-->
                                <!--    </td>-->
                                <!--</tr>-->

                                <!-- Note -->
                                <!--<tr>-->
                                <!--    <td style="padding:0 24px 24px;">-->
                                <!--        <p style="margin:0; font-size:14px; color:#0D0D0D70;">-->
                                <!--            <strong>Note:</strong> This is an official ZATCA Phase-2 compliant-->
                                <!--            e-invoice.-->
                                <!--            The XML invoice is attached for your records.-->
                                <!--        </p>-->
                                <!--    </td>-->
                                <!--</tr>-->

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
                                &#xA9; {{ date('Y') }} WLMMA. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>
