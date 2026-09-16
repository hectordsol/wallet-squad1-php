<?php

namespace App\Services\Investments;

use App\DTO\Investments\fixedTermDTO;

class fixedTermServices
{

    private const TNA = 0.30;

    public function create(FixedTermDTO $data): array
    {
        $interes = $data->monto * self::TNA * ($data->plazo / 365);
        $total   = $data->monto + $interes;

        return [
            'monto'   => $data->monto,
            'plazo'   => $data->plazo,
            'tna'     => self::TNA,
            'interes' => round($interes, 2),
            'total'   => round($total, 2),
        ];
    }
}
