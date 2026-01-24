<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_import(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/plan/import');
    }

    public function test_the_import_page_returns_successful_response(): void
    {
        $response = $this->get('/plan/import');

        $response->assertStatus(200);
    }
}
