<?php

use App\Http\Controllers\api\v1\AccountController;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\MovementsController;
use App\Http\Controllers\api\v1\ProfileController;
use App\Http\Controllers\api\v1\FavoriteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\AdminMovementController;

Route::prefix('v1')->group(function () {
    // Rutas públicas de autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {

        // Agregamos ruta para el perfil del usuario autenticado
        Route::get('/profile', [ProfileController::class, 'show']);
        // Agregamos ruta para la cuenta del usuario autenticado
        Route::get('/account', [AccountController::class, 'show']);
        // Agregamos ruta para depostiar en cuenta del usuario autenticado
        Route::post('/deposits', [AccountController::class, 'store']);
        //agrega ruta para consultrar movimientos
        Route::get("/movements", [MovementsController::class, "index"]);
        // TRANSFERIR DINERO
        Route::post('/transfers', [MovementsController::class, 'transfer']);
        // GUARDAR CBU DE TERCEROS (favoritos)
        Route::post('/cbu/{cbu}/users/{idUser}', [FavoriteController::class, 'store']);
        // LISTAR CBU DE TERCEROS
        Route::get('/cbu/users/{idUser}', [FavoriteController::class, 'index']);
        // REMOVER CBU DE TERCEROS
        Route::delete('/cbu/{cbu}/users/{idUser}', [FavoriteController::class, 'destroy']);

        //Agregamos rutas para el administrador
        Route::prefix('admin')->middleware('administrador')->group(function () {

            Route::get('/ping', fn() => response()->json([
                'message' => 'Acceso administrativo autorizado',
            ]));

            Route::get('/movements', [AdminMovementController::class, 'index']);
            Route::post('/movements', [AdminMovementController::class, 'store']);
            Route::get('/movements/{movimiento}', [AdminMovementController::class, 'show']);
            Route::put('/movements/{movimiento}', [AdminMovementController::class, 'update']);
            Route::patch('/movements/{movimiento}', [AdminMovementController::class, 'update']);
            Route::delete('/movements/{movimiento}', [AdminMovementController::class, 'destroy']);
        });
    });
});
