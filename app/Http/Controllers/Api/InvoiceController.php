<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(private OcrService $ocrService)
    {
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
            'status' => 'processed',
        ]);

        return response()->json($invoice, 201);
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