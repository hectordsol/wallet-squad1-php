<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Services\Admin\CreateAdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(private CreateAdminUserService $createAdminUserService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'orden' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orden = $request->input('orden', 'desc');
        $perPage = $request->integer('per_page', 15);

        $usuarios = User::query()
            ->orderBy('created_at', $orden)
            ->paginate($perPage);

        return AdminUserResource::collection($usuarios);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $usuario = $this->createAdminUserService->create($request->toDTO());

        return response()->json(
            new AdminUserResource($usuario),
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(
            new AdminUserResource($user),
            200
        );
    }
}
