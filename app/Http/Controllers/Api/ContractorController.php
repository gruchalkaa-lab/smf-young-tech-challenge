<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractorRequest;
use App\Models\Contractor;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contractors', description: 'Zarządzanie kontrahentami')]
class ContractorController extends Controller
{
    #[OA\Get(
        path: '/contractors',
        tags: ['Contractors'],
        summary: 'Lista wszystkich kontrahentów',
        responses: [
            new OA\Response(response: 200, description: 'Lista kontrahentów wraz z ich fakturami'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Contractor::with('invoices')->get());
    }

    #[OA\Post(
        path: '/contractors',
        tags: ['Contractors'],
        summary: 'Utwórz nowego kontrahenta',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'ABC Sp. z o.o.'),
                    new OA\Property(property: 'address', type: 'string', example: 'ul. Przykładowa 5, Gdańsk'),
                    new OA\Property(property: 'nip', type: 'string', example: '1234567890'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kontrahent utworzony'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ]
    )]
    public function store(StoreContractorRequest $request): JsonResponse
    {
        $contractor = Contractor::create($request->validated());
        return response()->json($contractor, 201);
    }

    #[OA\Get(
        path: '/contractors/{id}',
        tags: ['Contractors'],
        summary: 'Pokaż jednego kontrahenta',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dane kontrahenta'),
            new OA\Response(response: 404, description: 'Nie znaleziono'),
        ]
    )]
    public function show(Contractor $contractor): JsonResponse
    {
        return response()->json($contractor->load('invoices'));
    }

    #[OA\Put(
        path: '/contractors/{id}',
        tags: ['Contractors'],
        summary: 'Zaktualizuj kontrahenta',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'ABC Sp. z o.o.'),
                    new OA\Property(property: 'address', type: 'string', example: 'ul. Przykładowa 5'),
                    new OA\Property(property: 'nip', type: 'string', example: '1234567890'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Kontrahent zaktualizowany'),
        ]
    )]
    public function update(StoreContractorRequest $request, Contractor $contractor): JsonResponse
    {
        $contractor->update($request->validated());
        return response()->json($contractor);
    }

    #[OA\Delete(
        path: '/contractors/{id}',
        tags: ['Contractors'],
        summary: 'Usuń kontrahenta',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kontrahent usunięty'),
        ]
    )]
    public function destroy(Contractor $contractor): JsonResponse
    {
        $contractor->delete();
        return response()->json(null, 204);
    }
}