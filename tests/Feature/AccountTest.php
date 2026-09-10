<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    //Agregamos test para verificar que un usuario autenticado puede consultar su cuenta
    public function test_usuario_autenticado_puede_consultar_su_cuenta(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 1500.50,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account->cbu,
                'saldo' => '1500.50',
            ]);
    }
    //Agregamos test para verificar que un usuario sin token no puede consultar su cuenta
    public function test_usuario_sin_token_no_puede_consultar_su_cuenta(): void
    {
        $response = $this->getJson('/api/v1/account');

        $response->assertStatus(401);
    }
    //Agregamos test para verificar que un usuario con token inválido no puede consultar su cuenta
    public function test_usuario_solo_puede_ver_su_propia_cuenta(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = $user1->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $user2->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 9999.99,
        ]);

        $token = auth('api')->login($user1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account1->cbu,
                'saldo' => '100.00',
            ])
            ->assertJsonMissing([
                'cbu' => '0000000000000000000002',
            ]);
    }
}