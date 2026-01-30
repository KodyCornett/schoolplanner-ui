<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_to_import(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/plan/import');
    }

    public function test_the_import_page_requires_authentication(): void
    {
        $response = $this->get('/plan/import');

        $response->assertRedirect('/login');
    }

    public function test_the_import_page_returns_successful_response_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/plan/import');

        $response->assertStatus(200);
    }
}
