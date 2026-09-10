<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        //Retornamos el CBU y el saldo formateado a dos decimales como un string
        return [
            'cbu' => $this->cbu,
            'balance' => number_format((float) $this->saldo, 2, '.', ''),
        ];
    }
}
