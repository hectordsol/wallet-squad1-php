<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // Test 1: login con credenciales válidas devuelve un token utilizable
    public function test_login_con_credenciales_validas_devuelve_bearer_token(): void
    {
        // Arrange: creamos un usuario con una contraseña conocida
        $user = User::factory()->create([
            'email' => 'ana@test.com',
            'password' => Hash::make('secret123'),
        ]);

        // Act: enviamos las credenciales al endpoint de login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@test.com',
            'password' => 'secret123',
        ]);

        // Assert: 200 y la respuesta trae la estructura del JWT
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);
    }

    public function test_devuelve_id_nombre_email_edad_imagen_sin_contrasena_200(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'imagen' => null,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk();

        $response = $this->withHeader('Authorization', 'Bearer '.$loginResponse->json('access_token'))
            ->getJson('/api/v1/profile');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'nombre',
                'email',
                'edad',
                'imagen',
            ])
            ->assertJson([
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'edad' => $user->edad,
                'imagen' => null,
            ])
            ->assertJsonMissingPath('password');
    }

    // Test 2: contraseña incorrecta devuelve 401 y nunca expone el hash
    public function test_login_con_password_incorrecta_devuelve_401(): void
    {
        // Arrange: usuario con contraseña conocida
        User::factory()->create([
            'email' => 'ana@test.com',
            'password' => Hash::make('secret123'),
        ]);

        // Act: intentamos loguear con la contraseña equivocada
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@test.com',
            'password' => 'incorrecta',
        ]);

        // Assert: 401 y la respuesta no debe contener el password bajo ninguna forma
        $response->assertStatus(401);
        $response->assertJsonMissing(['password']);
    }

    // Test 3: email inexistente también responde 401 (no filtrar si el usuario existe o no)
    public function test_login_con_email_inexistente_devuelve_401(): void
    {
        // Arrange: base vacía, no hay usuarios registrados

        // Act: intentamos loguear con un email que no existe
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nadie@test.com',
            'password' => 'cualquiera',
        ]);

        // Assert: mismo 401 que con password mal, para no filtrar informacion
        $response->assertStatus(401);
    }

    // Test 4: campos faltantes disparan validación 422
    public function test_login_con_datos_faltantes_devuelve_422(): void
    {
        // Arrange: datos incompletos para el login
        $response = $this->postJson('/api/v1/auth/login', []);

        // Assert: la API devuelve su estructura de error personalizada
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'status',
                'error',
            ])
            ->assertJson([
                'status' => 422,
                'error' => [],
            ]);

        $this->assertNotEmpty($response->json('message'));
    }

    // Test 5: acceder a una ruta protegida sin token devuelve 401 JSON
    public function test_ruta_protegida_sin_token_devuelve_401_json(): void
    {
        // Arrange: nada, apuntamos directo a la ruta privada

        // Act: llamamos a /profile sin header de autorizacion
        $response = $this->getJson('/api/v1/profile');

        // Assert: 401 y el tipo de contenido debe ser JSON, no una redireccion
        $response->assertStatus(401);
        $response->assertHeader('content-type', 'application/json');
    }

    // Test 6: token inválido también da 401 JSON
    public function test_ruta_protegida_con_token_invalido_devuelve_401_json(): void
    {
        // Arrange: token que no fue emitido por la API
        $tokenFalso = 'Bearer token-invalido-cualquiera';

        // Act: intentamos acceder a /profile con ese token
        $response = $this->withHeader('Authorization', $tokenFalso)
            ->getJson('/api/v1/profile');

        // Assert: 401 y respuesta JSON
        $response->assertStatus(401);
        $response->assertHeader('content-type', 'application/json');
    }

    // Test 7: el ciclo completo, login -> usar token -> acceder a ruta privada
    public function test_token_valido_permite_acceder_a_ruta_protegida(): void
    {
        // Arrange: usuario registrado con contraseña conocida
        $user = User::factory()->create([
            'email' => 'ana@test.com',
            'password' => Hash::make('secret123'),
        ]);

        // Act 1: hacemos login para obtener el token real de la API
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@test.com',
            'password' => 'secret123',
        ]);
        $token = $loginResponse->json('access_token');

        // Act 2: usamos ese token para pegarle a /profile
        $profileResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile');

        // Assert: 200 y el perfil corresponde al usuario logueado
        $profileResponse->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => 'ana@test.com',
            ]);
    }
}
