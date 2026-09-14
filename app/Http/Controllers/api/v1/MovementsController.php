<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Movements\getTransactionsResource;
use App\Services\Movements\GetTransactionsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MovementsController extends Controller
{
    //
    public function __construct(private GetTransactionsService $get_transactios_service) {}

    public function index(): AnonymousResourceCollection
    {
        $movements = $this->get_transactios_service->GetTransactios();
        return getTransactionsResource::collection($movements);
    }
}
