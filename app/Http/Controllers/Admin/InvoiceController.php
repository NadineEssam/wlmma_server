<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CreditNoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Corecave\Zatca\Facades\Zatca;
use Corecave\Zatca\Models\ZatcaInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for managing ZATCA invoices in the admin dashboard.
 */
class InvoiceController extends Controller
{
    public function __construct(
        protected CreditNoteService $creditNoteService
    ) {}

    /**
     * List all ZATCA invoices with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ZatcaInvoice::query()
            ->with('invoiceable')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by subtype (invoice, credit_note, debit_note)
        if ($request->has('subtype')) {
            $query->where('subtype', $request->subtype);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by invoice number
        if ($request->has('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 15);
        $invoices = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get a specific invoice details.
     */
    public function show(int $id): JsonResponse
    {
        $invoice = ZatcaInvoice::with('invoiceable')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Download invoice XML.
     */
    public function downloadXml(int $id)
    {
        $invoice = ZatcaInvoice::findOrFail($id);

        if (empty($invoice->signed_xml)) {
            return response()->json([
                'success' => false,
                'message' => 'No XML available for this invoice',
            ], 404);
        }

        $filename = $invoice->invoice_number . '.xml';

        return Response::make($invoice->signed_xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get invoice QR code.
     */
    public function getQrCode(int $id): JsonResponse
    {
        $invoice = ZatcaInvoice::findOrFail($id);

        if (empty($invoice->qr_code)) {
            return response()->json([
                'success' => false,
                'message' => 'No QR code available for this invoice',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code' => $invoice->qr_code,
                'qr_code_image' => 'data:image/png;base64,' . $invoice->qr_code,
            ],
        ]);
    }

    /**
     * Retry a failed invoice submission.
     */
    public function retry(int $id): JsonResponse
    {
        $invoice = ZatcaInvoice::with('invoiceable')->findOrFail($id);

        if ($invoice->status !== 'rejected' && $invoice->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only rejected or pending invoices can be retried',
            ], 400);
        }

        if (!$invoice->invoiceable) {
            return response()->json([
                'success' => false,
                'message' => 'Original entity not found',
            ], 404);
        }

        try {
            // Re-submit using the invoice service
            $invoiceService = app(\App\Services\InvoiceService::class);
            $result = $invoiceService->processInvoice($invoice->invoiceable);

            return response()->json([
                'success' => $result !== null,
                'message' => $result ? 'Invoice resubmitted successfully' : 'Failed to resubmit invoice',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get invoice statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => ZatcaInvoice::count(),
            'by_status' => [
                'reported' => ZatcaInvoice::where('status', 'reported')->count(),
                'cleared' => ZatcaInvoice::where('status', 'cleared')->count(),
                'rejected' => ZatcaInvoice::where('status', 'rejected')->count(),
                'pending' => ZatcaInvoice::where('status', 'pending')->count(),
            ],
            'by_type' => [
                'simplified' => ZatcaInvoice::where('type', 'simplified')->count(),
                'standard' => ZatcaInvoice::where('type', 'standard')->count(),
            ],
            'by_subtype' => [
                'invoice' => ZatcaInvoice::where('subtype', 'invoice')->count(),
                'credit_note' => ZatcaInvoice::where('subtype', 'credit_note')->count(),
                'debit_note' => ZatcaInvoice::where('subtype', 'debit_note')->count(),
            ],
            'total_amount' => ZatcaInvoice::sum('total_amount'),
            'total_vat' => ZatcaInvoice::sum('vat_amount'),
            'today' => ZatcaInvoice::whereDate('created_at', today())->count(),
            'this_month' => ZatcaInvoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get ZATCA integration status.
     */
    public function zatcaStatus(): JsonResponse
    {
        try {
            $hasProductionCsid = !empty(config('zatca.production.csid'));
            $hasComplianceCsid = !empty(config('zatca.compliance.csid'));

            return response()->json([
                'success' => true,
                'data' => [
                    'environment' => config('zatca.environment'),
                    'has_production_csid' => $hasProductionCsid,
                    'has_compliance_csid' => $hasComplianceCsid,
                    'is_configured' => $hasProductionCsid || $hasComplianceCsid,
                    'seller_info' => [
                        'name' => config('zatca.seller.name'),
                        'vat_number' => config('zatca.seller.vat_number'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ZATCA status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(int $id)
    {
        $invoice = ZatcaInvoice::with('invoiceable')->findOrFail($id);

        // Get customer info from the invoiceable entity
        $customerName = 'Valued Customer';
        $customerEmail = '';
        $entityType = 'booking';

        if ($invoice->invoiceable) {
            $entity = $invoice->invoiceable;

            if (method_exists($entity, 'customer') && $entity->customer) {
                $customerName = $entity->customer->name ?? 'Valued Customer';
                $customerEmail = $entity->customer->email ?? '';
            } elseif (method_exists($entity, 'user') && $entity->user) {
                $customerName = $entity->user->name ?? 'Valued Customer';
                $customerEmail = $entity->user->email ?? '';
            }

            $entityType = class_basename($entity) === 'Order' ? 'order' : 'booking';
        }

        $isCreditNote = str_contains($invoice->subtype, 'credit');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'entityType' => $entityType,
            'isCreditNote' => $isCreditNote,
        ]);

        $filename = $invoice->invoice_number . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Stream invoice PDF (for viewing in browser).
     */
    public function viewPdf(int $id)
    {
        $invoice = ZatcaInvoice::with('invoiceable')->findOrFail($id);

        // Get customer info from the invoiceable entity
        $customerName = 'Valued Customer';
        $customerEmail = '';
        $entityType = 'booking';

        if ($invoice->invoiceable) {
            $entity = $invoice->invoiceable;

            if (method_exists($entity, 'customer') && $entity->customer) {
                $customerName = $entity->customer->name ?? 'Valued Customer';
                $customerEmail = $entity->customer->email ?? '';
            } elseif (method_exists($entity, 'user') && $entity->user) {
                $customerName = $entity->user->name ?? 'Valued Customer';
                $customerEmail = $entity->user->email ?? '';
            }

            $entityType = class_basename($entity) === 'Order' ? 'order' : 'booking';
        }

        $isCreditNote = str_contains($invoice->subtype, 'credit');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'entityType' => $entityType,
            'isCreditNote' => $isCreditNote,
        ]);

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }
}
