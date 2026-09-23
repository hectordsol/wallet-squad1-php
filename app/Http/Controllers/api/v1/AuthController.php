<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\loginUserFormRequest;
use App\Http\Requests\Auth\registerUserFormRequest;
use App\Http\Requests\Auth\updateUserFormRequest;
use App\Http\Resources\Auth\registerUserResource;
use App\Http\Resources\Auth\updateUserResource;
use App\Models\User;
use App\Services\Auth\deleteUserService;
use App\Services\Auth\loginUserService;
use App\Services\Auth\registerUserService;
use App\Services\Auth\updateUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private registerUserService $register_user_service,
        private loginUserService $login_user_service,
        private updateUserService $update_user_service,
        private deleteUserService $delete_user_service,
    ) {}

    public function register(registerUserFormRequest $request): JsonResponse
    {
        $user = $this->register_user_service->create($request->toDTO());

        return response()->json(new registerUserResource($user), 201);
    }

    //nuevo login para el RateLimit de intentos de sesión
    public function login(loginUserFormRequest $request): JsonResponse
    {
        $email = Str::lower(trim((string) $request->input('email')));
        $key = 'login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Demasiados intentos de inicio de sesión. Intente nuevamente más tarde.',
                'status' => 429,
                'retry_after' => $seconds,
                'error' => (object) [],
            ], 429)->header('Retry-After', (string) $seconds);
        }

        $token = $this->login_user_service->login($request->toDTO());

        if ($token === null) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Credenciales invalidas',
                'status' => 401,
                'error' => (object) [],
            ], 401);
        }

        RateLimiter::clear($key);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 200);
    }

    // Método para actualizar el perfil del usuario autenticado
    public function update(updateUserFormRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $user = $this->update_user_service->update($user, $request->toDTO());

        return response()->json(new updateUserResource($user), 200);
    }

    // Método para dar de baja al usuario autenticado
    public function delete(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $this->delete_user_service->delete($user);
        Auth::guard('api')->logout();

        return response()->json(null, 204);
    }
}
