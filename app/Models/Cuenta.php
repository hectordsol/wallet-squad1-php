<?php

namespace App\Models;

use Database\Factories\CuentaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    /** @use HasFactory<CuentaFactory> */
    use HasFactory;
}
