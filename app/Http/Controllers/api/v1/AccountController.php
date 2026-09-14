<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\Auth\AccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Cuenta;
use App\Models\Movimiento;
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
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'destination_cbu' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
        ]);

        $user_origen = Auth::guard('api')->user();
        $account_origen = $user_origen->cuenta;
        $account_destino = Cuenta::where('cbu', $request->destination_cbu)->first();

        if ($account_origen->cbu === $request->destination_cbu) {
            return response()->json([
                'message' => 'No se puede transferir a la misma cuenta',
            ], 422);
        }
        
        if (!$account_origen || !$account_destino) {
            return response()->json([
                'message' => 'la cuenta de origen o destino no existe',
            ], 404);
        }

        if($account_origen->saldo < $request->amount) {
            return response()->json([
                'message' => 'Saldo insuficiente para realizar la transferencia',
            ], 422);
        }

        DB::transaction(function () use ($account_origen, $account_destino, $request): void {
            // Restar el monto de la cuenta de origen
            $account_origen->saldo -= $request->amount;
            $account_origen->save();

            // Agregar el monto a la cuenta de destino
            $account_destino->saldo += $request->amount;
            $account_destino->save();

            // Registrar el movimiento en la cuenta de origen
            $account_origen->movimientos()->create([
                'tipo' => 'transferencia_salida',
                'monto' => $request->amount,
                'cbu_contraparte' => $account_destino->cbu,
            ]);

            // Registrar el movimiento en la cuenta de destino
            $account_destino->movimientos()->create([
                'tipo' => 'transferencia_entrada',
                'monto' => $request->amount,
                'cbu_contraparte' => $account_origen->cbu,
            ]);
        });

        return response()->json([
            'message' => 'Transferencia realizada con éxito',
        ], 200);
    }
}
