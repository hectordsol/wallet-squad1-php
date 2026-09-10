<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    //Metodo para mostrar la cuenta del usuario autenticado
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $account = $user->cuenta;

        if (!$account) {
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 404);
        }

        return response()->json(
            new AccountResource($account),
            200
        );
    }
}