<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Proxy\PatientController;
use App\Http\Controllers\Api\AuthController;

Route::post('/precreate', [AuthController::class, 'precreate']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/create-password', [AuthController::class, 'createPassword']);
Route::get('/test-crm', function () {
    $response = Http::get(config('services.crm.url') . '/patients/check', [
        'full_name' => 'Донець Юрій Васильович',
        'phone' => '380663604626',
        'birthday' => '1983-02-05',
    ]);

    return [
        'status' => $response->status(),
        'body' => $response->json(),
    ];
});Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn(Request $request) => $request->user());
});
