<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Favorito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreFavoriteTest extends TestCase
{
    use RefreshDatabase;

    // Test 1: happy path, un usuario guarda el CBU de otro y queda registrado con titular
    public function test_usuario_autenticado_puede_guardar_cbu_de_tercero(): void
    {
        // Arrange: dos usuarios con cuenta (Ana quiere guardar el CBU de Beto)
        $ana = User::factory()->create(['nombre' => 'Ana']);
        $ana->cuenta()->create([
            'cbu' => '1111111111111111111111',
            'saldo' => 0,
        ]);

        $beto = User::factory()->create(['nombre' => 'Beto']);
        $beto->cuenta()->create([
            'cbu' => '2222222222222222222222',
            'saldo' => 0,
        ]);

        $token = auth('api')->login($ana);

        // Act: Ana guarda el CBU de Beto en su lista
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/cbu/2222222222222222222222/users/{$ana->id}");

        // Assert: 201, la respuesta trae el CBU y el titular resuelto, y la fila existe en favoritos
        $response->assertStatus(201)
            ->assertJson([
                'cbu' => '2222222222222222222222',
                'titular' => 'Beto',
            ]);

        $this->assertDatabaseHas('favoritos', [
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);
    }

    // Test 2: no se puede duplicar el mismo CBU dos veces
    public function test_usuario_autenticado_no_puede_guardar_el_mismo_cbu_dos_veces(): void
    {
        // Arrange: Ana ya tiene el CBU de Beto guardado
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        Favorito::create([
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);

        $token = auth('api')->login($ana);

        // Act: intentamos guardar el mismo CBU otra vez
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/cbu/2222222222222222222222/users/{$ana->id}");

        // Assert: 422 y sigue habiendo una sola fila en favoritos
        $response->assertStatus(422);

        $this->assertDatabaseCount('favoritos', 1);
    }

    // Test 3: no se puede guardar el CBU propio como favorito
    public function test_usuario_autenticado_no_puede_guardar_el_cbu_propio(): void
    {
        // Arrange: Ana con su cuenta y su CBU
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $token = auth('api')->login($ana);

        // Act: Ana intenta guardarse a si misma
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/cbu/1111111111111111111111/users/{$ana->id}");

        // Assert: 422 y no se creo ningun favorito
        $response->assertStatus(422);
        $this->assertDatabaseCount('favoritos', 0);
    }

    // Test 4: CBU inexistente devuelve 404 (se busca por numero, no por id)
    public function test_usuario_autenticado_no_puede_guardar_cbu_inexistente_devuelve_404(): void
    {
        // Arrange: Ana con cuenta, nadie mas registrado
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $token = auth('api')->login($ana);

        // Act: intentamos guardar un CBU que no existe en la base
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/cbu/9999999999999999999999/users/{$ana->id}");

        // Assert: 404
        $response->assertStatus(404);
    }

    // Test 5: intentar modificar la lista de otro usuario devuelve 403
    public function test_no_puede_modificar_la_lista_de_otro_usuario(): void
    {
        // Arrange: Ana logueada, pero intenta operar contra el idUser de Beto
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        $token = auth('api')->login($ana);

        // Act: Ana intenta guardar el CBU de Beto en la lista DE BETO
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/cbu/2222222222222222222222/users/{$beto->id}");

        // Assert: 403 y no se creo ningun favorito
        $response->assertStatus(403);
        $this->assertDatabaseCount('favoritos', 0);
    }

    // Test 6: sin token, ni siquiera se llega a la logica del endpoint
    public function test_sin_token_devuelve_401(): void
    {
        // Arrange: nada, apuntamos sin autenticacion

        // Act: llamada sin header Authorization
        $response = $this->postJson('/api/v1/cbu/2222222222222222222222/users/1');

        // Assert: 401 JSON
        $response->assertStatus(401);
    }
}
