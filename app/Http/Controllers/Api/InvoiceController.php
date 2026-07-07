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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Invoices', description: 'Zarządzanie fakturami: upload, OCR, ekstrakcja danych')]
class InvoiceController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private InvoiceParsingAgent $parsingAgent,
    ) {
    }

    #[OA\Get(
        path: '/invoices',
        tags: ['Invoices'],
        summary: 'Lista wszystkich faktur',
        responses: [
            new OA\Response(response: 200, description: 'Lista faktur wraz z kontrahentem, pozycjami i płatnością'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Invoice::with(['contractor', 'items', 'payment'])->get());
    }

    #[OA\Post(
        path: '/invoices',
        tags: ['Invoices'],
        summary: 'Wgraj nową fakturę (PDF/JPG/PNG) - automatycznie uruchamia OCR i ekstrakcję danych',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['invoice_file'],
                    properties: [
                        new OA\Property(
                            property: 'invoice_file',
                            type: 'string',
                            format: 'binary',
                            description: 'Plik faktury (PDF, JPG, JPEG lub PNG, max 10MB)'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Faktura przetworzona: OCR i agent wyciągnęły dostępne dane'),
            new OA\Response(response: 422, description: 'Błąd walidacji pliku'),
        ]
    )]
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

    #[OA\Get(
        path: '/invoices/{id}',
        tags: ['Invoices'],
        summary: 'Pokaż jedną fakturę',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dane faktury'),
            new OA\Response(response: 404, description: 'Nie znaleziono'),
        ]
    )]
    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load(['contractor', 'items', 'payment']));
    }

    #[OA\Put(
        path: '/invoices/{id}',
        tags: ['Invoices'],
        summary: 'Zaktualizuj fakturę (np. ręczna korekta po OCR)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Faktura zaktualizowana'),
        ]
    )]
    public function update(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice);
    }

    #[OA\Delete(
        path: '/invoices/{id}',
        tags: ['Invoices'],
        summary: 'Usuń fakturę wraz z plikiem',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Faktura usunięta'),
        ]
    )]
    public function destroy(Invoice $invoice): JsonResponse
    {
        Storage::disk('local')->delete($invoice->file_path);
        $invoice->delete();
        return response()->json(null, 204);
    }
}