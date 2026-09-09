<?php

namespace App\Models;

use Database\Factories\CuentaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    /** @use HasFactory<CuentaFactory> */
    use HasFactory;

    protected $table = 'cuentas';

    protected $fillable = [
        'usuario_id',
        'cbu',
        'saldo',
        'tipo',
        'moneda',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'cuenta_id');
    }

    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class, 'cuenta_id');
    }

    public function cuentasQueLaTienenComoFavorita(): HasMany
    {
        return $this->hasMany(Favorito::class, 'cbu_favorito', 'cbu');
    }
}
