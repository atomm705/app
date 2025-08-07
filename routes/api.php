<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Proxy\PatientController;

Route::middleware('auth:sanctum')->get('/patients/{id}', [PatientController::class, 'show']);
