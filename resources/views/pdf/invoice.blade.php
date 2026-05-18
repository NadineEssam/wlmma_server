<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            background: #fff;
        }
        .invoice-container {
            padding: 30px 40px;
        }
        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .company-name-ar {
            font-size: 18px;
            color: #764ba2;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #667eea;
            font-weight: bold;
        }
        .zatca-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 10px;
        }
        /* Info Section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box-title {
            font-size: 11px;
            font-weight: bold;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            color: #888;
            font-size: 10px;
        }
        .info-value {
            color: #333;
            font-weight: bold;
            font-size: 11px;
        }
        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .items-table th:last-child {
            text-align: right;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .items-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        /* Totals */
        .totals-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }
        .totals-box {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 15px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row:last-child {
            border-bottom: none;
        }
        .totals-label {
            display: table-cell;
            width: 60%;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-weight: bold;
        }
        .totals-row.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 10px -15px -15px;
            padding: 12px 15px;
            border-radius: 0 0 8px 8px;
        }
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            color: white;
            font-size: 14px;
        }
        /* QR Code */
        .qr-section {
            text-align: center;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 8px;
        }
        .qr-section img {
            width: 120px;
            height: 120px;
        }
        .qr-section p {
            font-size: 9px;
            color: #666;
            margin-top: 8px;
        }
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
        }
        .footer p {
            font-size: 9px;
            color: #888;
            margin: 3px 0;
        }
        .footer .zatca-info {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .footer .zatca-info p {
            color: #333;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">WLMMA</div>
                <div class="company-name-ar">شركة ولمة للسياحة</div>
                <div class="company-details">
                    VAT: {{ config('zatca.seller.vat_number', '312132926200003') }}<br>
                    CR: {{ config('zatca.seller.registration_number', '1213292620') }}<br>
                    {{ config('zatca.seller.address.street', 'Anas Bin Malik') }}, {{ config('zatca.seller.address.city', 'Riyadh') }}<br>
                    Saudi Arabia
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">{{ $isCreditNote ? 'CREDIT NOTE' : 'TAX INVOICE' }}</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                @if($invoice->status === 'reported' || $invoice->status === 'cleared')
                    <div class="zatca-badge">ZATCA VERIFIED</div>
                @endif
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-box-title">Invoice Details</div>
                <div class="info-row">
                    <span class="info-label">Invoice Number:</span>
                    <span class="info-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Issue Date:</span>
                    <span class="info-value">{{ $invoice->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Issue Time:</span>
                    <span class="info-value">{{ $invoice->created_at->format('H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice Type:</span>
                    <span class="info-value">{{ ucfirst($invoice->type) }} ({{ $invoice->isSimplified() ? 'B2C' : 'B2B' }})</span>
                </div>
                <div class="info-row">
                    <span class="info-label">UUID:</span>
                    <span class="info-value" style="font-size: 9px;">{{ $invoice->uuid }}</span>
                </div>
            </div>
            <div class="info-box" style="padding-left: 30px;">
                <div class="info-box-title">Bill To</div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $customerName }}</span>
                </div>
                @if(!empty($customerEmail))
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $customerEmail }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $entityType === 'booking' ? 'Activity Booking' : 'Order' }} - {{ $invoice->invoice_number }}</td>
                    <td>1</td>
                    <td>SAR {{ number_format($invoice->total_amount - $invoice->vat_amount, 2) }}</td>
                    <td>SAR {{ number_format($invoice->total_amount - $invoice->vat_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals & QR -->
        <div class="totals-section">
            <div class="totals-left">
                @if($invoice->qr_code && $invoice->qr_code_image)
                <div class="qr-section">
                    <img src="data:image/png;base64,{{ $invoice->qr_code_image }}" alt="QR Code">
                    <p>Scan to verify invoice authenticity<br>via ZATCA portal</p>
                </div>
                @endif
            </div>
            <div class="totals-right">
                <div class="totals-box">
                    <div class="totals-row">
                        <span class="totals-label">Subtotal (excl. VAT)</span>
                        <span class="totals-value">SAR {{ number_format($invoice->total_amount - $invoice->vat_amount, 2) }}</span>
                    </div>
                    <div class="totals-row">
                        <span class="totals-label">VAT (15%)</span>
                        <span class="totals-value">SAR {{ number_format($invoice->vat_amount, 2) }}</span>
                    </div>
                    <div class="totals-row total">
                        <span class="totals-label">Total Amount</span>
                        <span class="totals-value">SAR {{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="zatca-info">
                <p><strong>ZATCA Compliant E-Invoice</strong> - This invoice has been generated in compliance with ZATCA Phase 2 e-invoicing regulations.</p>
            </div>
            <p>Thank you for choosing WLMMA Tourism Company</p>
            <p>For inquiries, please contact our support team</p>
            <p>&copy; {{ date('Y') }} WLMMA. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
