<?php

test('la api rechaza un login sin credenciales', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response
        ->assertUnprocessable()
        ->assertJson([
            'status' => 422,
            'error' => [],
        ]);
});
