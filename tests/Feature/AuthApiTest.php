<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_un_cliente_se_registra_obtiene_cbu_unico_saldo_0_y_201(): void
    {
        // arrange
        $user = [
            'nombre' => ' name test',
            'email' => 'em213213ail@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'edad' => 18,
        ];
        // act realizar la peticion
        $response = $this->postJson('/api/v1/auth/register', $user);
        // assert respuesta 201 assertCreated()
        $response->assertCreated()->assertJsonStructure(
            [
                'id',
                'nombre',
                'email',
                'rol',
            ]
        );
        // Verifica que se cree una cuenta asociada al usuario.
        $cuenta = Cuenta::where('usuario_id', $response->json('id'))->first();
        // Comprueba que el CBU tenga 22 dígitos.
        // Comprueba que el saldo inicial sea 0.0
        expect($cuenta)->not->toBeNull() 
            ->and($cuenta->cbu)->toMatch('/^\d{22}$/')
            ->and((float) $cuenta->saldo)->toBe(0.0);
    }

    public function test_usuario_no_se_registra_con_email_duplicado(): void
    {
        // arrange

        User::factory()->create([
            'email' => 'email@gmail.com',
        ]);
        $user = [
            'nombre' => ' name test',
            'email' => 'email@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'edad' => 18,
        ];
        // act realizar la peticion
        $response = $this->postJson('/api/v1/auth/register', $user);
        // assert
        $response->assertUnprocessable();
    }

    public function test_usuario_no_se_registra_con_password_diferentes(): void
    {
        // arrange
        $user = [
            'nombre' => ' name test',
            'email' => 'email@test.com',
            'password' => 'password',
            'password_confirmation' => 'password1',
            'edad' => 18,
        ];
        // act realizar la peticion
        $response = $this->postJson('/api/v1/auth/register', $user);
        // assert
        $response->assertUnprocessable();
    }

    public function test_usuario_no_se_registra_menor_de_edad(): void
    {
        // arrange
        $user = [
            'nombre' => ' name test',
            'email' => 'em213213ail@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'edad' => 17,
        ];
        // act realizar la peticion
        $response = $this->postJson('/api/v1/auth/register', $user);
        // assert
        $response->assertUnprocessable();
    }

    public function test_usuario_no_se_registra_con_datos_incompletos(): void
    {
        // arrange
        $user = [
            'nombre' => ' name test',
            'email' => 'em213213ail@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',

        ];
        // act realizar la peticion
        $response = $this->postJson('/api/v1/auth/register', $user);
        // assert
        $response->assertUnprocessable();
    }
}
