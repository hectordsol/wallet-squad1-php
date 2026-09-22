<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminAccountRequest;
use App\Http\Requests\Admin\StoreAdminAccountRequest;
use App\Http\Requests\Admin\UpdateAdminAccountRequest;
use App\Http\Resources\Admin\AdminAccountResource;
use App\Models\Cuenta;
use App\Services\Account\createAccountService;
use App\Services\Admin\ListAdminAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminAccountController extends Controller
{
    public function __construct(
        private createAccountService $createAccountService,
        private ListAdminAccountService $listAdminAccountService,
        // private deleteFavoriteService $delete_favorite_service,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(ListAdminAccountRequest $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->filled('cbu')) {
            $cuenta = Cuenta::with('usuario')
                ->where('cbu', $request->string('cbu'))
                ->first();

            if ($cuenta === null) {
                return response()->json([
                    'message' => 'No se encontró una cuenta con el CBU indicado.',
                ], 404);
            }

            return response()->json(new AdminAccountResource($cuenta), 200);
        }

        $listAccount = $this->listAdminAccountService->list($request->toDTO());

        return AdminAccountResource::collection($listAccount);
    }

    // Almacena una nueva cuenta desde la administración de usuario sin cuenta
    public function store(StoreAdminAccountRequest $request): JsonResponse
    {
        $deletedAccount = Cuenta::withTrashed()
            ->where('usuario_id', $request->integer('usuario_id'))
            ->first();

        if ($deletedAccount?->trashed()) {
            $deletedAccount->restore();
            $deletedAccount->update($request->validated());
            $deletedAccount->load('usuario');

            return response()->json(new AdminAccountResource($deletedAccount), 200);
        }

        $account = $this->createAccountService->create($request->toDTO());

        return response()->json(
            new AdminAccountResource($account->load('usuario')),
            201
        );
    }

    // Muestra información de una cuenta
    public function show(Cuenta $cuenta): JsonResponse
    {
        $cuenta->load('usuario');

        return response()->json(new AdminAccountResource($cuenta), 200);
    }

    // Actualizar cuenta desde la administración
    public function update(UpdateAdminAccountRequest $request, Cuenta $cuenta): JsonResponse
    {
        $cuenta->update($request->validated());
        $cuenta->load('usuario');

        return response()->json(new AdminAccountResource($cuenta), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cuenta $cuenta): JsonResponse
    {
        if (! $cuenta->usuario()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la cuenta porque el usuario asociado no existe.',
            ], 422);
        }

        if ((float) $cuenta->saldo !== 0.0) {
            return response()->json([
                'message' => 'No se puede eliminar la cuenta porque su saldo debe ser 0.',
            ], 422);
        }

        $cuenta->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente',
        ], 204);
    }
}
