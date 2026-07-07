<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Contractor;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceParsingAgent;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private InvoiceParsingAgent $parsingAgent,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(Invoice::with(['contractor', 'items', 'payment'])->get());
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $file = $request->file('invoice_file');
        $path = $file->store('invoices', 'local');
        $mimeType = $file->getMimeType();

        $invoice = Invoice::create([
            'file_path' => $path,
            'status' => 'uploaded',
        ]);

        $absolutePath = Storage::disk('local')->path($path);
        $text = $this->ocrService->extractText($absolutePath, $mimeType);

        $invoice->update([
            'raw_ocr_text' => $text,
            'status' => 'processing',
        ]);

        $parsedData = $this->parsingAgent->parse($text);

        $contractor = null;
        if (!empty($parsedData['contractor_name']) || !empty($parsedData['nip'])) {
            $contractor = Contractor::firstOrCreate(
                ['nip' => $parsedData['nip']],
                ['name' => $parsedData['contractor_name'] ?? 'Nieznany kontrahent']
            );
        }

        if (!empty($parsedData['total_amount'])) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $parsedData['total_amount'],
                'currency' => $parsedData['currency'],
                'method' => $parsedData['payment_method'],
            ]);
        }

        $invoice->update([
            'contractor_id' => $contractor?->id,
            'status' => 'processed',
        ]);

        return response()->json($invoice->load(['contractor', 'items', 'payment']), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load(['contractor', 'items', 'payment']));
    }

    public function update(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        Storage::disk('local')->delete($invoice->file_path);
        $invoice->delete();
        return response()->json(null, 204);
    }
}