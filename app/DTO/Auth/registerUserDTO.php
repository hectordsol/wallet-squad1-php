<?php

namespace App\DTO\Auth;

class RegisterUserDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $edad,
    ) {}

    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'email' => $this->email,
            'password'       => $this->password,
            'edad'       => $this->edad,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data["name"],
            email: $data["email"],
            password: $data["password"],
            edad: $data["edad"],
        );
    }
}
