<?php

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un administrador puede listar cuentas ordenadas por nombre de usuario', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $primero = User::factory()->create(['nombre' => 'Ana Cuenta']);
    $segundo = User::factory()->create(['nombre' => 'Zoe Cuenta']);

    $cuentaPrimero = Cuenta::factory()->create([
        'usuario_id' => $primero->id,
        'saldo' => 1250.50,
    ]);
    $cuentaSegundo = Cuenta::factory()->create([
        'usuario_id' => $segundo->id,
        'saldo' => 850.25,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->getJson('/api/v1/admin/accounts?orden=asc&per_page=2');

    $response
        ->assertOk()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('data.0.id', $cuentaPrimero->id)
        ->assertJsonPath('data.0.usuario_id', $primero->id)
        ->assertJsonPath('data.0.cbu', $cuentaPrimero->cbu)
        ->assertJsonPath('data.0.monto', '1250.50')
        ->assertJsonPath('data.0.tipo', $cuentaPrimero->tipo)
        ->assertJsonPath('data.0.moneda', $cuentaPrimero->moneda)
        ->assertJsonPath('data.0.nombre_usuario', 'Ana Cuenta');

    expect($response->json('data.0'))->toHaveKeys([
        'id',
        'usuario_id',
        'cbu',
        'monto',
        'tipo',
        'moneda',
        'nombre_usuario',
    ]);
});

test('el listado de cuentas usa 15 por pagina y rechaza mas de 100', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    Cuenta::factory()->count(16)->create();
    $token = auth('api')->login($administrador);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/accounts')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonCount(15, 'data');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/accounts?per_page=101')
        ->assertUnprocessable();
});

test('un administrador puede obtener una cuenta por su cbu mediante query', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $cuenta = Cuenta::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->getJson('/api/v1/admin/accounts?cbu='.$cuenta->cbu)
        ->assertOk()
        ->assertJsonPath('id', $cuenta->id)
        ->assertJsonPath('cbu', $cuenta->cbu)
        ->assertJsonPath('usuario_id', $cuenta->usuario_id);
});

test('buscar una cuenta por cbu inexistente devuelve un error claro', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->getJson('/api/v1/admin/accounts?cbu=0000000000000000000000')
        ->assertNotFound()
        ->assertJsonPath('message', 'No se encontró una cuenta con el CBU indicado.');
});

test('un administrador puede crear una cuenta para un usuario sin cuenta', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();

    $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->postJson('/api/v1/admin/accounts', [
            'usuario_id' => $usuario->id,
            'tipo' => 'corriente',
            'moneda' => 'USD',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('usuario_id', $usuario->id)
        ->assertJsonPath('tipo', 'corriente')
        ->assertJsonPath('moneda', 'USD')
        ->assertJsonPath('nombre_usuario', $usuario->nombre);

    $this->assertDatabaseHas('cuentas', [
        'usuario_id' => $usuario->id,
        'tipo' => 'corriente',
        'moneda' => 'USD',
    ]);
});

test('un administrador puede restaurar una cuenta dada de baja', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();
    $cuenta = Cuenta::factory()->create([
        'usuario_id' => $usuario->id,
        'tipo' => 'ahorro',
        'moneda' => 'ARS',
    ]);
    $cuenta->delete();

    $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->postJson('/api/v1/admin/accounts', [
            'usuario_id' => $usuario->id,
            'tipo' => 'corriente',
            'moneda' => 'USD',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('id', $cuenta->id)
        ->assertJsonPath('usuario_id', $usuario->id)
        ->assertJsonPath('tipo', 'corriente')
        ->assertJsonPath('moneda', 'USD');

    $this->assertDatabaseHas('cuentas', [
        'id' => $cuenta->id,
        'usuario_id' => $usuario->id,
        'deleted_at' => null,
        'tipo' => 'corriente',
        'moneda' => 'USD',
    ]);
});

test('un usuario no administrador no puede crear cuentas', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
    ]);
    $destinatario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($usuario))
        ->postJson('/api/v1/admin/accounts', [
            'usuario_id' => $destinatario->id,
        ])
        ->assertForbidden();
});

test('un administrador no puede crear una segunda cuenta para el mismo usuario', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();
    Cuenta::factory()->create(['usuario_id' => $usuario->id]);

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->postJson('/api/v1/admin/accounts', [
            'usuario_id' => $usuario->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'El usuario ya tiene una cuenta.');
});

test('actualizar una cuenta no permite reasignarla a otro usuario', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();
    $cuenta = Cuenta::factory()->create([
        'usuario_id' => $usuario->id,
        'tipo' => 'ahorro',
    ]);

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->patchJson('/api/v1/admin/accounts/'.$cuenta->id, [
            'usuario_id' => $otroUsuario->id,
            'tipo' => 'corriente',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'No se puede reasignar la cuenta a otro usuario.');

    expect($cuenta->refresh()->usuario_id)->toBe($usuario->id)
        ->and($cuenta->tipo)->toBe('ahorro');
});

test('actualizar una cuenta conserva su usuario y modifica sus datos permitidos', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();
    $cuenta = Cuenta::factory()->create(['usuario_id' => $usuario->id]);

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->patchJson('/api/v1/admin/accounts/'.$cuenta->id, [
            'tipo' => 'corriente',
            'moneda' => 'USD',
        ])
        ->assertOk()
        ->assertJsonPath('usuario_id', $usuario->id)
        ->assertJsonPath('tipo', 'corriente')
        ->assertJsonPath('moneda', 'USD');
});

test('eliminar una cuenta con saldo cero aplica baja logica aunque su usuario tenga baja logica', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $usuario = User::factory()->create();
    $cuenta = Cuenta::factory()->create(['usuario_id' => $usuario->id]);
    $usuario->delete();

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->deleteJson('/api/v1/admin/accounts/'.$cuenta->id)
        ->assertOk()
        ->assertJsonPath('message', 'Cuenta eliminada correctamente');

    $this->assertSoftDeleted('users', ['id' => $usuario->id]);
    $this->assertSoftDeleted('cuentas', ['id' => $cuenta->id]);
});

test('no se puede eliminar una cuenta con saldo', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);
    $cuenta = Cuenta::factory()->create(['saldo' => 100.00]);

    $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
        ->deleteJson('/api/v1/admin/accounts/'.$cuenta->id)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'No se puede eliminar la cuenta porque su saldo debe ser 0.');

    $this->assertDatabaseHas('cuentas', [
        'id' => $cuenta->id,
        'deleted_at' => null,
    ]);
});
