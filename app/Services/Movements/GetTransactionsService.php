<?php

namespace App\Services\Movements;

use App\Models\Movimiento;

class GetTransactionsService
{
    public function GetTransactios(int $perPage = 15, string $sort = 'desc')
    {
        // crea la cuneta del usario(el password se hashea en el modelo)
        $cuentaId = auth('api')->user()->cuenta->id;
        $perPage = min(max($perPage, 1), 100);
        $sort = strtolower($sort) === 'asc' ? 'asc' : 'desc';

        $query = Movimiento::query()
            ->where('cuenta_id', $cuentaId)
            ->orderBy('created_at', $sort);

        return $query->paginate($perPage);
    }
}
