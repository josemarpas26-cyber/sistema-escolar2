<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessDeniedPageTest extends TestCase
{
    public function test_forbidden_route_renders_access_denied_page(): void
    {
        $response = $this->get('/forbidden');

        $response->assertOk();
        $response->assertSee('403');
        $response->assertSee('Acesso Restrito');
        $response->assertSee('Voltar para o Dashboard');
    }
}