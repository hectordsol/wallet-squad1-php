<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\loginUserFormRequest;
use App\Http\Requests\Auth\registerUserFormRequest;
use App\Http\Resources\Auth\registerUserResource;
use App\Services\Auth\loginUserService;
use App\Services\Auth\registerUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private registerUserService $register_user_service,
        private loginUserService $login_user_service,
    ) {}

    public function register(registerUserFormRequest $request): JsonResponse
    {
        $user = $this->register_user_service->create($request->toDTO());

        return response()->json(new registerUserResource($user), 201);
    }

    public function login(loginUserFormRequest $request): JsonResponse
    {
        $token = $this->login_user_service->login($request->toDTO());

        if ($token === null) {
            return response()->json([
                'message' => 'Credenciales invalidas',
                'status'  => 401,
                'error'   => (object) [],
            ], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ], 200);
    }
}
