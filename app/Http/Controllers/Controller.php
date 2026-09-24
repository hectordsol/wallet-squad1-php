<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Wallet API',
    description: 'Documentación de la API de Wallet. Las rutas privadas requieren un JWT: obtenelo en POST /api/v1/auth/login y cargalo con el botón Authorize.'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Schema(
    schema: 'Account',
    required: ['cbu', 'saldo', 'tipo', 'moneda'],
    properties: [
        new OA\Property(property: 'cbu', type: 'string', example: '0000000000000000000001'),
        new OA\Property(property: 'saldo', type: 'string', example: '1550.50'),
        new OA\Property(property: 'tipo', type: 'string', example: 'ahorro'),
        new OA\Property(property: 'moneda', type: 'string', example: 'ARS'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ApiError',
    description: 'Formato comun de error de la API (401, 403, 404 y 422). El campo status repite el codigo HTTP de la respuesta.',
    required: ['message', 'status', 'error'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'No autenticado'),
        new OA\Property(property: 'status', type: 'integer', example: 401),
        new OA\Property(property: 'error', type: 'object'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AccountNotFoundError',
    description: 'Respuesta 404 propia de los endpoints de cuenta y deposito. No incluye status ni error.',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Cuenta no encontrada'),
    ],
    type: 'object'
)]
abstract class Controller
{
    //
}
