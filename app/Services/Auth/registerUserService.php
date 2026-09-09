<?php

namespace App\Services\Auth;

use App\DTO\Auth\RegisterUserDTO;
use App\Models\Usuario;

class registerUserService
{

    public function create(RegisterUserDTO $data)
    {
        return Usuario::create($data->toArray());
    }
}
