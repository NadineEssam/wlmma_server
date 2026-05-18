<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZatcaInvoiceService
{
    public function sendTestInvoice()
    {
        $seller = config('zatca.seller');
        $zatca = config('zatca.zatca');

        $invoice = [
            'InvoiceNumber' => 'INV-0001',
            'IssueDate' => now()->format('Y-m-d'),
            'CustomerName' => 'عميل تجريبي',
            'CustomerVAT' => '123456789000003',
            'TotalAmount' => 115,
            'TaxAmount' => 15,
        ];

        // هنا هنرسل طلب تحقق امتثال تجريبي
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($zatca['base_url'] . $zatca['compliance_url'], [
            'invoice' => $invoice,
        ]);

        return $response->json();
    }
}
