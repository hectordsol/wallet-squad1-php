<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Movements\getTransactionsResource;
use App\Services\Movements\GetTransactionsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class MovementsController extends Controller
{
    //
    public function __construct(private GetTransactionsService $get_transactios_service) {}

    public function index(): AnonymousResourceCollection
    {
        $movements = $this->get_transactios_service->GetTransactios();
        return getTransactionsResource::collection($movements);
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

        if ($account_origen->saldo < $request->amount) {
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
