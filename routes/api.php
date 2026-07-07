<?php

use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('contractors', ContractorController::class);
Route::apiResource('invoices', InvoiceController::class);