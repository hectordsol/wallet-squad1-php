<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Favorites\storeFavoriteFormRequest;
use App\Http\Resources\Favorites\FavoriteResource;
use App\Services\Favorites\storeFavoriteService;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function __construct(private storeFavoriteService $store_favorite_service) {}

    public function store(storeFavoriteFormRequest $request): JsonResponse
    {
        $favorite = $this->store_favorite_service->store($request->toDTO());

        // Cargamos las relaciones para que el Resource pueda mostrar el titular.
        $favorite->load('cuentaFavorita.usuario');

        return response()->json(new FavoriteResource($favorite), 201);
    }
}
