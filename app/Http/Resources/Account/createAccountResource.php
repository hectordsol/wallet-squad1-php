<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class createAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "cbu" => $this->cbu,
            "saldo" => $this->saldo,
            "tipo" => $this->tipo,
            "moneda" => $this->moneda,
        ];
    }
}
