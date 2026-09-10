<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    //Método para mostrar el perfil del usuario autenticado
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json(new ProfileResource($user), 200);
    }
}