<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    //Agregamos test para verificar que un usuario autenticado puede consultar su perfil
    public function test_usuario_autenticado_puede_consultar_su_perfil(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile');

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->nombre,
                'email' => $user->email,
            ])
            ->assertJsonMissing([
                'password' => $user->password,
            ]);
    }

    //Agregamos test para verificar que un usuario sin token no puede consultar su perfil
    public function test_usuario_sin_token_no_puede_consultar_su_perfil(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }
}
