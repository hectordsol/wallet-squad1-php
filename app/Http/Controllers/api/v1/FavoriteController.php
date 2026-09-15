<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Favorites\deleteFavoriteFormRequest;
use App\Http\Requests\Favorites\listFavoritesFormRequest;
use App\Http\Requests\Favorites\storeFavoriteFormRequest;
use App\Http\Resources\Favorites\FavoriteResource;
use App\Services\Favorites\deleteFavoriteService;
use App\Services\Favorites\listFavoritesService;
use App\Services\Favorites\storeFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function __construct(
        private storeFavoriteService $store_favorite_service,
        private listFavoritesService $list_favorites_service,
        private deleteFavoriteService $delete_favorite_service,
    ) {}

    public function store(storeFavoriteFormRequest $request): JsonResponse
    {
        $favorite = $this->store_favorite_service->store($request->toDTO());

        // Cargamos las relaciones para que el Resource pueda mostrar el titular.
        $favorite->load('cuentaFavorita.usuario');

        return response()->json(new FavoriteResource($favorite), 201);
    }

    public function index(listFavoritesFormRequest $request): AnonymousResourceCollection
    {
        $favorites = $this->list_favorites_service->list($request->toDTO());

        return FavoriteResource::collection($favorites);
    }

    public function destroy(deleteFavoriteFormRequest $request): JsonResponse
    {
        $this->delete_favorite_service->delete($request->toDTO());

        return response()->json([
            'message' => 'CBU removido de tu lista de favoritos.',
        ], 200);
    }
}
