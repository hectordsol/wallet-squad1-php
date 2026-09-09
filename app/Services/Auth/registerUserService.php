<?php

namespace App\Services\Auth;

use App\DTO\Account\createAccountDTO;
use App\DTO\Auth\RegisterUserDTO;
use App\Models\Usuario;
use App\Services\Account\createAccountService;


class registerUserService
{
    //inyecta el servcio en el constructor
    public function __construct(private createAccountService $createAccountService) {}

    public function create(RegisterUserDTO $data)
    {
        //crea la cuneta del usario(el password se hashea en el modelo)
        $usuario = Usuario::create($data->toArray());
        $usuario->refresh(); //se usa para traer el rol por default
        //agrega el id del usuario al dto
        $cuenta = new createAccountDTO(
            usuario_id: $usuario->id
        );

        $this->createAccountService->create($cuenta);
        return $usuario;
    }
}
