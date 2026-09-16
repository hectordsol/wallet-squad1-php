<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investments\fixedTerRequest;
use App\Http\Resources\investments\fixedTermResource;
use App\Services\Investments\fixedTermServices;
use Illuminate\Http\JsonResponse;

class investmentsController extends Controller
{
    public  function __construct(private fixedTermServices $fixed_term_services) {}

    public function store(fixedTerRequest $request): JsonResponse
    {
        $fixedTerm = $this->fixed_term_services->create($request->toDTO());
        return response()->json(new fixedTermResource($fixedTerm), 200);
    }
}
