<?php

namespace Tests\Feature;

use App\Models\Favorito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesIndexAndDestroyTest extends TestCase
{
    use RefreshDatabase;

    // ---------- LISTAR (index) ----------

    // Test 1: el listado devuelve solo los favoritos del usuario con CBU y titular
    public function test_lista_solo_los_favoritos_del_usuario_autenticado(): void
    {
        // Arrange: Ana con un favorito (Beto), y Carlos con otro favorito para verificar aislamiento
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create(['nombre' => 'Beto']);
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        $carlos = User::factory()->create();
        $carlos->cuenta()->create(['cbu' => '3333333333333333333333', 'saldo' => 0]);

        // Ana guarda a Beto
        Favorito::create([
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);
        // Carlos guarda a Beto tambien, pero Ana no debe verlo
        Favorito::create([
            'cuenta_id' => $carlos->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);

        $token = auth('api')->login($ana);

        // Act: Ana consulta su lista
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/cbu/users/{$ana->id}");

        // Assert: 200, un solo item, con CBU y titular resueltos
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'cbu' => '2222222222222222222222',
                'titular' => 'Beto',
            ]);
    }

    // Test 2: intentar listar los favoritos de otro usuario devuelve 403
    public function test_no_puede_listar_favoritos_de_otro_usuario(): void
    {
        // Arrange: Ana logueada, pero pide la lista con el idUser de Beto
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        $token = auth('api')->login($ana);

        // Act: Ana pega a la lista de Beto
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/cbu/users/{$beto->id}");

        // Assert: 403
        $response->assertStatus(403);
    }

    // ---------- REMOVER (destroy) ----------

    // Test 3: remover un favorito quita solo esa fila y devuelve 200
    public function test_usuario_autenticado_puede_remover_un_favorito_de_su_lista(): void
    {
        // Arrange: Ana con el CBU de Beto guardado
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        Favorito::create([
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);

        $token = auth('api')->login($ana);

        // Act: Ana borra a Beto de sus favoritos
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/cbu/2222222222222222222222/users/{$ana->id}");

        // Assert: 200 y la fila desaparecio de favoritos
        $response->assertStatus(200);
        $this->assertDatabaseMissing('favoritos', [
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);
    }

    // Test 4 (CRITICO): remover un favorito NO borra la cuenta ni al usuario tercero
    public function test_remover_favorito_no_elimina_cuenta_ni_usuario_tercero(): void
    {
        // Arrange: Ana con el CBU de Beto guardado
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        Favorito::create([
            'cuenta_id' => $ana->cuenta->id,
            'cbu_favorito' => '2222222222222222222222',
        ]);

        $token = auth('api')->login($ana);

        // Act: Ana elimina el favorito
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/cbu/2222222222222222222222/users/{$ana->id}");

        // Assert: Beto y su cuenta siguen existiendo intactos en la base
        $this->assertDatabaseHas('users', ['id' => $beto->id]);
        $this->assertDatabaseHas('cuentas', ['cbu' => '2222222222222222222222']);
    }

    // Test 5: remover un CBU que no esta en la lista devuelve 404
    public function test_remover_cbu_que_no_esta_en_la_lista_devuelve_404(): void
    {
        // Arrange: Ana no tiene ningun favorito guardado, pero Beto existe
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        $token = auth('api')->login($ana);

        // Act: Ana intenta borrar un favorito que no tiene
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/cbu/2222222222222222222222/users/{$ana->id}");

        // Assert: 404
        $response->assertStatus(404);
    }

    // Test 6: intentar remover con un idUser ajeno devuelve 403
    public function test_usuario_autenticado_no_puede_remover_favoritos_de_otro_usuario(): void
    {
        // Arrange: Beto tiene guardado un CBU; Ana no deberia poder tocarlo
        $ana = User::factory()->create();
        $ana->cuenta()->create(['cbu' => '1111111111111111111111', 'saldo' => 0]);

        $beto = User::factory()->create();
        $beto->cuenta()->create(['cbu' => '2222222222222222222222', 'saldo' => 0]);

        $carlos = User::factory()->create();
        $carlos->cuenta()->create(['cbu' => '3333333333333333333333', 'saldo' => 0]);

        Favorito::create([
            'cuenta_id' => $beto->cuenta->id,
            'cbu_favorito' => '3333333333333333333333',
        ]);

        $token = auth('api')->login($ana);

        // Act: Ana intenta borrar el favorito DE BETO
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/cbu/3333333333333333333333/users/{$beto->id}");

        // Assert: 403 y el favorito de Beto sigue intacto
        $response->assertStatus(403);
        $this->assertDatabaseHas('favoritos', [
            'cuenta_id' => $beto->cuenta->id,
            'cbu_favorito' => '3333333333333333333333',
        ]);
    }

    // Test 7: sin token, ninguna operacion es posible
    public function test_sin_token_devuelve_401(): void
    {
        // Act: intentamos listar y remover sin autenticacion
        $listar = $this->getJson('/api/v1/cbu/users/1');
        $borrar = $this->deleteJson('/api/v1/cbu/2222222222222222222222/users/1');

        // Assert: ambos devuelven 401
        $listar->assertStatus(401);
        $borrar->assertStatus(401);
    }
}
