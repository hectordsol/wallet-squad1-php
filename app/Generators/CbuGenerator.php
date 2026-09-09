<?php

namespace App\Generators;

use App\Models\Cuenta;

class CbuGenerator
{
    //genera un cbu de 22 digitos
    public static function generate(): string
    {
        do {
            $cbu = self::generateRandomCbu();
        } while (self::cbuExists($cbu));

        return $cbu;
    }

    // genera el cbu de forma aleatoria
    //primeros digitos 0000009
    private static function generateRandomCbu(): string
    {
        $cbu = '0000009';
        for ($i = 0; $i < 15; $i++) {
            $cbu .= random_int(0, 9);
        }
        return $cbu;
    }

    // verifica si el cbu generado existe en la bd
    private static function cbuExists(string $cbu): bool
    {
        return Cuenta::where('cbu', $cbu)->exists();
    }
}
