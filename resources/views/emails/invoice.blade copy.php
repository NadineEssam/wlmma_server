<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isCreditNote ? 'Credit Note' : 'Invoice' }} - {{ $invoice->invoice_number }}</title>
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .header {
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
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

        .header .invoice-number {
            font-size: 14px;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        .zatca-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.25);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 15px;
            font-weight: 500;
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

        .invoice-card {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .invoice-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8ebf0;
        }

        .invoice-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .invoice-status {
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

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            padding: 12px;
            background: white;
            border-radius: 8px;
        }

        .detail-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .amount-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            color: white;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .amount-row.total {
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: 10px;
            padding-top: 15px;
            font-size: 20px;
            font-weight: 700;
        }

        .qr-section {
            text-align: center;
            padding: 25px;
            background: #f8f9fc;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .qr-section img {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .qr-section p {
            margin-top: 12px;
            font-size: 13px;
            color: #666;
        }

        .download-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 14px 35px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
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
            .detail-grid {
                grid-template-columns: 1fr;
            }

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
                    @if ($isCreditNote)
                        &#8634;
                    @else
                        &#10003;
                    @endif
                </div>
                <h1>{{ $isCreditNote ? 'Credit Note' : 'Invoice' }}</h1>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                @if ($invoice->status === 'reported' || $invoice->status === 'cleared')
                    <span class="zatca-badge">&#9989; ZATCA Verified</span>
                @endif
            </div>

            <div class="content">
                <div class="greeting">
                    <h2>Hello {{ $customerName }},</h2>
                    @if ($isCreditNote)
                        <p>Your refund has been processed. Please find your credit note details below.</p>
                    @else
                        <p>Thank you for your {{ $entityType }}! Here's your official e-invoice.</p>
                    @endif
                </div>

                <div class="invoice-card">
                    <div class="invoice-card-header">
                        <span class="invoice-card-title">Invoice Details</span>
                        <span class="invoice-status status-success">{{ ucfirst($invoice->status) }}</span>
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Invoice Number</div>
                            <div class="detail-value">{{ $invoice->invoice_number }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Date</div>
                            {{-- <div class="detail-value">{{ $invoice->created_at->format('d M Y') }}</div> --}}
                            <div class="detail-value">
                                {{ $invoice->created_at?->format('d M Y') ?? '-' }}
                            </div>

                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Type</div>
                            <div class="detail-value">{{ ucfirst($invoice->type) }} Invoice</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Time</div>
                            {{-- <div class="detail-value">{{ $invoice->created_at->format('H:i') }}</div> --}}
                            <div class="detail-value">
                                {{ $invoice->created_at?->format('H:i') ?? '-' }}
                            </div>

                        </div>
                    </div>
                </div>

                <div class="amount-section">
                    <div class="amount-row">
                        <span>Subtotal</span>
                        <span>SAR {{ number_format($invoice->total_amount - $invoice->vat_amount, 2) }}</span>
                    </div>
                    <div class="amount-row">
                        <span>VAT (15%)</span>
                        <span>SAR {{ number_format($invoice->vat_amount, 2) }}</span>
                    </div>
                    <div class="amount-row total">
                        <span>Total</span>
                        <span>SAR {{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>

                @if ($invoice->qr_code && $invoice->qr_code_image)
                    <div class="qr-section">
                        <img src="data:image/png;base64,{{ $invoice->qr_code_image }}" alt="ZATCA QR Code">
                        <p>Scan to verify invoice authenticity</p>
                    </div>
                @endif

                <div class="download-section">
                    <a href="{{ config('app.url') }}/invoices/{{ $invoice->id }}/pdf" class="download-btn">
                        &#8681; Download PDF Invoice
                    </a>
                </div>

                <div class="info-box">
                    <p><strong>Note:</strong> This is an officially generated e-invoice compliant with ZATCA Phase 2
                        regulations. The XML invoice is attached to this email for your records.</p>
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
