<?php

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function administradorConToken(): array
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);

        return [$administrador, auth('api')->login($administrador)];
    }

    private function usuarioComunConToken(): array
    {
        $usuario = User::factory()->create([
            'rol' => 'usuario',
        ]);

        return [$usuario, auth('api')->login($usuario)];
    }

    // --- Listado ---

    public function test_un_administrador_puede_listar_usuarios(): void
    {
        [, $token] = $this->administradorConToken();
        User::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(4, 'data');
    }

    public function test_el_listado_de_usuarios_no_expone_la_contrasena(): void
    {
        [, $token] = $this->administradorConToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200);
        expect($response->json('data.0'))->not->toHaveKey('password');
    }

    public function test_el_listado_de_usuarios_orden_por_created_at_segun_el_parametro_orden(): void
    {
        [$administrador, $token] = $this->administradorConToken();

        $ultimo = User::factory()->create([
            'created_at' => now()->addDay(),
        ]);

        $ascendente = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users?orden=asc');
        $descendente = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users?orden=desc');

        expect($ascendente->json('data.0.id'))->toBe($administrador->id);
        expect($descendente->json('data.0.id'))->toBe($ultimo->id);
    }

    public function test_el_listado_de_usuarios_respeta_per_page(): void
    {
        [, $token] = $this->administradorConToken();
        User::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_el_listado_de_usuarios_rechaza_un_per_page_mayor_a_100(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users?per_page=101')
            ->assertStatus(422);
    }

    public function test_el_listado_de_usuarios_rechaza_un_orden_invalido(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users?orden=cualquiera')
            ->assertStatus(422);
    }

    public function test_el_listado_de_usuarios_no_incluye_a_los_usuarios_dados_de_baja(): void
    {
        [, $token] = $this->administradorConToken();
        $eliminado = User::factory()->create();
        $eliminado->delete();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // --- Consulta ---

    public function test_un_administrador_puede_consultar_un_usuario(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/admin/users/{$usuario->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
            ]);
        expect($response->json())->not->toHaveKey('password');
    }

    public function test_consultar_un_usuario_inexistente_devuelve_404(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users/99999')
            ->assertStatus(404);
    }

    public function test_consultar_un_usuario_dado_de_baja_devuelve_404(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();
        $usuario->delete();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/admin/users/{$usuario->id}")
            ->assertStatus(404);
    }

    // --- Creacion ---

    public function test_un_administrador_puede_crear_un_usuario(): void
    {
        [, $token] = $this->administradorConToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 30,
            ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'rol' => 'usuario',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.local',
            'rol' => 'usuario',
            'eliminado' => false,
        ]);
    }

    public function test_crear_un_usuario_tambien_le_crea_su_cuenta(): void
    {
        [, $token] = $this->administradorConToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 30,
            ]);

        $cuenta = Cuenta::where('usuario_id', $response->json('id'))->first();

        expect($cuenta)->not->toBeNull();
        expect($cuenta->cbu)->not->toBeEmpty();
    }

    public function test_la_contrasena_del_usuario_creado_se_guarda_hasheada(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 30,
            ])
            ->assertStatus(201);

        $creado = User::where('email', 'nuevo@test.local')->first();

        expect($creado->getAuthPassword())->not->toBe('secreto123');
        expect(Hash::check('secreto123', $creado->getAuthPassword()))->toBeTrue();
    }

    public function test_crear_un_usuario_ignora_el_rol_enviado_por_el_cliente(): void
    {
        [, $token] = $this->administradorConToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 30,
                'rol' => 'administrador',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.local',
            'rol' => 'usuario',
        ]);
    }

    public function test_la_respuesta_de_creacion_no_expone_la_contrasena(): void
    {
        [, $token] = $this->administradorConToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 30,
            ]);

        $response->assertStatus(201);
        expect($response->json())->not->toHaveKey('password');
    }

    public function test_crear_un_usuario_valida_los_campos_obligatorios(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [])
            ->assertStatus(422);
    }

    public function test_crear_un_usuario_rechaza_un_email_ya_registrado(): void
    {
        [, $token] = $this->administradorConToken();
        $existente = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => $existente->email,
                'password' => 'secreto123',
                'edad' => 30,
            ])
            ->assertStatus(422);
    }

    public function test_crear_un_usuario_rechaza_una_contrasena_corta(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'corta',
                'edad' => 30,
            ])
            ->assertStatus(422);
    }

    public function test_crear_un_usuario_rechaza_a_un_menor_de_edad(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'nombre' => 'Usuario Nuevo',
                'email' => 'nuevo@test.local',
                'password' => 'secreto123',
                'edad' => 15,
            ])
            ->assertStatus(422);
    }

    // --- Actualizacion ---

    public function test_un_administrador_puede_actualizar_un_usuario(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'nombre' => 'Nombre Editado',
                'edad' => 45,
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $usuario->id,
                'nombre' => 'Nombre Editado',
                'edad' => 45,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'nombre' => 'Nombre Editado',
            'edad' => 45,
        ]);
    }

    public function test_actualizar_un_usuario_con_su_propio_email_actual_no_da_error_de_unicidad(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'email' => $usuario->email,
                'nombre' => 'Nombre Editado',
            ])
            ->assertStatus(200);
    }

    public function test_actualizar_un_usuario_con_el_email_de_otro_usuario_devuelve_422(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();
        $otro = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'email' => $otro->email,
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'El correo electrónico ya está en uso.',
                'status' => 422,
            ]);
    }

    public function test_actualizar_la_contrasena_de_un_usuario_la_guarda_hasheada_y_no_la_expone(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'password' => 'nuevaclave123',
            ]);

        $response->assertStatus(200);
        expect($response->json())->not->toHaveKey('password');
        expect(Hash::check('nuevaclave123', $usuario->fresh()->getAuthPassword()))->toBeTrue();
    }

    public function test_actualizar_un_usuario_no_permite_cambiarle_el_rol(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'rol' => 'administrador',
            ])
            ->assertStatus(200);

        expect($usuario->fresh()->rol)->toBe('usuario');
    }

    public function test_actualizar_un_usuario_con_una_edad_invalida_devuelve_422(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/admin/users/{$usuario->id}", [
                'edad' => 5,
            ])
            ->assertStatus(422);
    }

    public function test_actualizar_un_usuario_inexistente_devuelve_404(): void
    {
        [, $token] = $this->administradorConToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/admin/users/99999', ['nombre' => 'Fantasma'])
            ->assertStatus(404);
    }

    // --- Baja logica ---

    public function test_un_administrador_puede_dar_de_baja_un_usuario(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/users/{$usuario->id}")
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Usuario eliminado correctamente',
            ]);

        $this->assertSoftDeleted('users', ['id' => $usuario->id]);
        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'eliminado' => true,
        ]);
    }

    public function test_dar_de_baja_un_usuario_no_borra_su_cuenta_ni_sus_movimientos(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();
        $cuenta = Cuenta::factory()->create(['usuario_id' => $usuario->id]);
        $movimiento = Movimiento::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => 500,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/users/{$usuario->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('cuentas', ['id' => $cuenta->id]);
        $this->assertDatabaseHas('movimientos', ['id' => $movimiento->id]);
    }

    public function test_dar_de_baja_un_usuario_ya_dado_de_baja_devuelve_404(): void
    {
        [, $token] = $this->administradorConToken();
        $usuario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/users/{$usuario->id}")
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/users/{$usuario->id}")
            ->assertStatus(404);
    }

    // --- Proteccion por rol y por JWT ---

    #[DataProvider('rutasAdministrativas')]
    public function test_un_usuario_comun_no_puede_administrar_usuarios(string $metodo, string $ruta): void
    {
        [, $token] = $this->usuarioComunConToken();
        $objetivo = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->json($metodo, str_replace('{id}', (string) $objetivo->id, $ruta))
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No autorizado. Se requiere rol de administrador.',
                'status' => 403,
            ]);
    }

    #[DataProvider('rutasAdministrativas')]
    public function test_una_peticion_sin_token_no_puede_administrar_usuarios(string $metodo, string $ruta): void
    {
        $objetivo = User::factory()->create();

        $this->json($metodo, str_replace('{id}', (string) $objetivo->id, $ruta))
            ->assertStatus(401);
    }

    public static function rutasAdministrativas(): array
    {
        return [
            ['GET', '/api/v1/admin/users'],
            ['POST', '/api/v1/admin/users'],
            ['GET', '/api/v1/admin/users/{id}'],
            ['PUT', '/api/v1/admin/users/{id}'],
            ['PATCH', '/api/v1/admin/users/{id}'],
            ['DELETE', '/api/v1/admin/users/{id}'],
        ];
    }
}
