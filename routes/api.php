<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiProfileController;
use App\Http\Controllers\ApiVisitController;

Route::middleware('auth:sanctum')->group(function(){
    Route::post('profile', [ApiProfileController::class, 'index']);

    Route::post('select', [ApiVisitController::class, 'select']);
    Route::post('visits', [ApivisitController::class, 'visits']);
});

Route::apiResource('apis', ApiController::class);
Route::post('register', [ApiController::class, 'register']);
Route::post('sms_verification', [ApiController::class, 'sms_verification']);
Route::post('create_password', [ApiController::class, 'create_password']);

Route::post('login', [ApiController::class, 'login']);

Route::get('doctors', [ApiController::class, 'doctors']);
Route::get('doctors/{doctorId}/show', [ApiController::class, 'doctor_show']);

Route::get('services', [ApiController::class, 'services']);
Route::get('visit/{visit}/{userId}', [ApiVisitController::class, 'show']);

