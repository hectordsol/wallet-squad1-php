<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\Auth\AccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    // Metodo para mostrar la cuenta del usuario autenticado
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $account = $user->cuenta;

        if (! $account) {
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 404);
        }

        return response()->json(
            new AccountResource($account),
            200
        );
    }

    public function store(StoreAccountRequest $accountRequest): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $account = $user->cuenta;

        if (! $account) {
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 404);
        }

        $depositAmount = $accountRequest->toDTO()->amount;

        DB::transaction(function () use ($account, $depositAmount): void {
        // Generar cambio en el saldo de la cuenta
            $account->saldo += $depositAmount;
            $account->save();
        // Generar registro de movimiento en movimientos
            $account->movimientos()->create([
                'tipo' => 'deposito',
                'monto' => $depositAmount,
            ]);
        });

        return response()->json(
            new AccountResource($account),
            200
        );
    }
}
