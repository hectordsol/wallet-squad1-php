<?php

use App\Http\Controllers\api\v1\AccountController;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rutas públicas de autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Ruta protegida de prueba: verifica 401 JSON sin token o con token inválido.
    // Se reemplaza por los endpoints reales en WAL-005 / WAL-006.
    Route::middleware('auth:api')->group(function () {
        Route::get('/ping', fn () => response()->json(['message' => 'pong']));
        // Agregamos ruta para el perfil del usuario autenticado
        Route::get('/profile', [ProfileController::class, 'show']);
        // Agregamos ruta para la cuenta del usuario autenticado
        Route::get('/account', [AccountController::class, 'show']);
        // Agregamos ruta para depostiar en cuenta del usuario autenticado
        Route::post('/deposits', [AccountController::class, 'store']);
    });
});