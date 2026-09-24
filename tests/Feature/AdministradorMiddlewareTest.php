<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministradorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrador_puede_acceder_a_una_ruta_administrativa(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);

        $token = auth('api')->login($administrador);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/ping');

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Acceso administrativo autorizado',
            ]);
    }

    public function test_un_usuario_que_no_es_administrador_recibe_una_respuesta_403(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'usuario',
        ]);

        $token = auth('api')->login($usuario);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/ping');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No autorizado. Se requiere rol de administrador.',
                'status' => 403,
            ]);
    }

    public function test_un_usuario_no_autenticado_no_puede_acceder_a_una_ruta_administrativa(): void
    {
        $response = $this->getJson('/api/v1/admin/ping');

        $response->assertStatus(401);
    }
}
