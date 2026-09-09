<?php

use App\Http\Controllers\api\v1\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Ruta de registro
    Route::post('/auth/register', [AuthController::class, 'register']);
});
