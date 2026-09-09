<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\registerUserFormRequest;
use App\Http\Resources\Auth\registerUserResource;
use App\Services\Auth\registerUserService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    //
    public function __construct(private registerUserService $register_user_service) {}

    public function register(registerUserFormRequest $request): JsonResponse
    {
        $user = $this->register_user_service->create($request->toDTO());

        return response()->json(new registerUserResource($user), 201);
    }
}
