<?php

use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;

Route::get('/projects', [KioskController::class, 'projects']);
Route::post('/stt', [KioskController::class, 'stt']);
Route::post('/match', [KioskController::class, 'match']);
