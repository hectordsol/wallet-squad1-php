<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Movements\AdminMovementResource;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Admin\StoreAdminMovementRequest;
use App\Http\Requests\Admin\UpdateAdminMovementRequest;
use Illuminate\Http\JsonResponse;

class AdminMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'cuenta_id' => ['nullable', 'integer', 'exists:cuentas,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'orden' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        //Equivale a obtener cada movimiento junto con:
        //Movimiento -> Cuenta -> Usuario
        $query = Movimiento::query()->with('cuenta.usuario');

        //Filtra por cuenta_id si se proporciona en la solicitud
        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_id', $request->integer('cuenta_id'));
        }

        //devuelve los movimientos de todas las cuentas pertenecientes al usuario
        if ($request->filled('usuario_id')) {
            $query->whereHas('cuenta', function ($query) use ($request) {
                $query->where('usuario_id', $request->integer('usuario_id'));
            });
        }

        $orden = $request->input('orden', 'desc');
        $perPage = $request->integer('per_page', 15);

        $movimientos = $query
            ->orderBy('created_at', $orden)
            ->paginate($perPage);

        return AdminMovementResource::collection($movimientos);
    }

    public function store(StoreAdminMovementRequest $request): JsonResponse
    {
        $movimiento = Movimiento::create($request->validated());

        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            201
        );
    }

    public function show(Movimiento $movimiento): JsonResponse
    {
        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            200
        );
    }

    public function update(UpdateAdminMovementRequest $request, Movimiento $movimiento): JsonResponse
    {
        $movimiento->update($request->validated());

        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            200
        );
    }

    public function destroy(Movimiento $movimiento): JsonResponse
    {
        $movimiento->delete();

        return response()->json([
            'message' => 'Movimiento eliminado correctamente',
        ], 200);
    }

}
