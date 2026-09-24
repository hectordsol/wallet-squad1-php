<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_usuario_con_token_consulta_sus_movimientos_paginado_y_con_metadato(): void
    {
        // arrange
        $user = User::factory()->create();
        $cuenta = Cuenta::factory()->create(['usuario_id' => $user->id]);
        Movimiento::factory()->count(101)->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
        ]);
        $token = auth('api')->login($user);
        // act realizar la peticion
        $response = $this->withToken($token)->getJson('/api/v1/movements');
        // assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'tipo',
                    'monto',
                    'cbu_contraparte',
                ],
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'links' => [
                    '*' => [
                        'url',
                        'label',
                        'page',
                        'active',
                    ],
                ],
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
        $response->assertJsonPath('meta.per_page', 15);

        $maxPerPageResponse = $this->withToken($token)
            ->getJson('/api/v1/movements?per_page=200');

        $maxPerPageResponse->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_orden_fecha_por_defecto_descendente(): void
    {
        $user = User::factory()->create();
        $cuenta = Cuenta::factory()->create(['usuario_id' => $user->id]);
        $fechaAntigua = now()->subDays(2);
        $fechaIntermedia = now()->subDay();
        $fechaReciente = now();

        $movimientoAntiguo = Movimiento::factory()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'created_at' => $fechaAntigua,
            'updated_at' => $fechaAntigua,
        ]);
        $movimientoIntermedio = Movimiento::factory()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'created_at' => $fechaIntermedia,
            'updated_at' => $fechaIntermedia,
        ]);
        $movimientoReciente = Movimiento::factory()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'created_at' => $fechaReciente,
            'updated_at' => $fechaReciente,
        ]);
        $token = auth('api')->login($user);

        $defaultResponse = $this->withToken($token)->getJson('/api/v1/movements');
        $defaultResponse->assertOk()
            ->assertJsonPath('data.0.id', $movimientoReciente->id)
            ->assertJsonPath('data.1.id', $movimientoIntermedio->id)
            ->assertJsonPath('data.2.id', $movimientoAntiguo->id);

        $ascendingResponse = $this->withToken($token)
            ->getJson('/api/v1/movements?sort=asc');
        $ascendingResponse->assertOk()
            ->assertJsonPath('data.0.id', $movimientoAntiguo->id)
            ->assertJsonPath('data.1.id', $movimientoIntermedio->id)
            ->assertJsonPath('data.2.id', $movimientoReciente->id);

        $descendingResponse = $this->withToken($token)
            ->getJson('/api/v1/movements?sort=desc');
        $descendingResponse->assertOk()
            ->assertJsonPath('data.0.id', $movimientoReciente->id)
            ->assertJsonPath('data.1.id', $movimientoIntermedio->id)
            ->assertJsonPath('data.2.id', $movimientoAntiguo->id);
    }

    public function test_usuario_sin_token_no_puede_consulta_movimientos(): void
    {
        // arrange
        $user = User::factory()->create();
        Cuenta::factory()->create(['usuario_id' => $user->id]);
        // act realizar la peticion
        $response = $this->getJson('/api/v1/movements');
        // assert
        $response->assertUnauthorized();
    }

    public function test_usuario_con_token_intenta_consulta_movimientos_de_otra_cuenta(): void
    {
        // usuario que intenta acceder a otra cuenta
        $attackerUser = User::factory()->create();
        $attackerAccount = Cuenta::factory()->create(['usuario_id' => $attackerUser->id]);
        $attackerToken = auth('api')->login($attackerUser);

        // usuario victima
        $victimUser = User::factory()->create();
        $victimAccount = Cuenta::factory()->create(['usuario_id' => $victimUser->id]);

        // movimiento de cuenta del usuario victima
        Movimiento::factory()->sinContraparte()->create([
            'cuenta_id' => $victimAccount->id,
            'tipo' => 'deposito',
            'monto' => 99999.00,
        ]);

        Movimiento::factory()->sinContraparte()->create([
            'cuenta_id' => $attackerAccount->id,
            'tipo' => 'deposito',
            'monto' => 1.00,
        ]);

        // usuario atacante intenta entrar a la cuenta de la victima
        $response = $this->withToken($attackerToken)
            ->getJson("/api/v1/movements?cuenta_id={$victimAccount->id}");

        // devuleve ok, porq el codigo ignora el id que le pasa como parametro
        $response->assertOk();

        // verificamos que la respuesta no contenga
        $response->assertJsonMissing([
            'monto' => 99999.00,
        ]);
        // lo que debe mostrar, son los datos de su propia cuenta
        $response->assertJsonFragment([
            'id' => 2,
            'tipo' => 'deposito',
            'monto' => '1.00',
        ]);
    }
}
