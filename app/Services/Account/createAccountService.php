<?php

namespace App\Services\Account;


use App\Models\Cuenta;


class createAccountService
{

    public function create(createAccountDTO $data)
    {
        return Cuenta::create($data->toArray());
    }
}
