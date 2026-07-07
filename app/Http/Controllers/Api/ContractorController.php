<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractorRequest;
use App\Models\Contractor;
use Illuminate\Http\JsonResponse;

class ContractorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Contractor::with('invoices')->get());
    }

    public function store(StoreContractorRequest $request): JsonResponse
    {
        $contractor = Contractor::create($request->validated());
        return response()->json($contractor, 201);
    }

    public function show(Contractor $contractor): JsonResponse
    {
        return response()->json($contractor->load('invoices'));
    }

    public function update(StoreContractorRequest $request, Contractor $contractor): JsonResponse
    {
        $contractor->update($request->validated());
        return response()->json($contractor);
    }

    public function destroy(Contractor $contractor): JsonResponse
    {
        $contractor->delete();
        return response()->json(null, 204);
    }
}