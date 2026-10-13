<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated guest is redirected to login from hub.
     */
    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Authenticated user receives a successful response on the post-login hub.
     */
    public function test_authenticated_user_accesses_hub_successfully(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }

    /**
     * Stats API returns infrastructure details.
     */
    public function test_stats_api_returns_infrastructure_details(): void
    {
        $response = $this->get('/api/v1/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'infrastructure' => [
                'octane',
                'redis',
                'pulse',
                'sentry',
                'reverb',
            ],
        ]);
    }
}
