<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Invoice::with(['contractor', 'items', 'payment'])->get());
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $file = $request->file('invoice_file');
        $path = $file->store('invoices', 'local');

        $invoice = Invoice::create([
            'file_path' => $path,
            'status' => 'uploaded',
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