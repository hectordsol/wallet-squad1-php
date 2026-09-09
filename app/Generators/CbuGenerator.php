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
    private static function generateRandomCbu(): string
    {
        $digits = '';
        for ($i = 0; $i < 22; $i++) {
            $digits .= random_int(0, 9);
        }
        return $digits;
    }

    // verifica si el cbu generado existe en la bd
    private static function cbuExists(string $cbu): bool
    {
        return Cuenta::where('cbu', $cbu)->exists();
    }
}
