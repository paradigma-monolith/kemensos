<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@hybrid.local',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@hybrid.local',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@hybrid.local',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@hybrid.local',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_unauthenticated_user_redirected_from_protected_hub(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_from_inertia_dashboard(): void
    {
        $response = $this->get('/inertia/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_from_spa(): void
    {
        $response = $this->get('/spa');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_protected_hub(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_logout_via_post_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_logout_via_get_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_login_creates_login_history_with_ip_and_agent_details(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@hybrid.local',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@hybrid.local',
            'password' => 'password',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0',
            'REMOTE_ADDR' => '192.168.1.50',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'email' => 'admin@hybrid.local',
            'status' => 'success',
            'platform' => 'Windows 10/11',
            'device' => 'Desktop',
        ]);
    }

    public function test_failed_login_creates_failed_login_history_record(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'hacker@target.local',
            'password' => 'wrongpass',
        ], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
            'REMOTE_ADDR' => '103.20.10.5',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('login_histories', [
            'email' => 'hacker@target.local',
            'status' => 'failed',
            'platform' => 'iOS',
            'device' => 'Mobile',
        ]);
    }

    public function test_authenticated_user_can_fetch_login_histories_via_api(): void
    {
        $user = User::factory()->create();
        \App\Models\LoginHistory::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => '127.0.0.1',
            'browser' => 'Chrome 122',
            'platform' => 'Windows',
            'device' => 'Desktop',
            'status' => 'success',
            'login_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile/login-histories');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'current_ip',
            'histories' => [
                'data',
            ],
        ]);
    }

    public function test_super_admin_can_view_all_users_login_histories(): void
    {
        $superAdmin = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->assignRole($role);

        $otherUser = User::factory()->create();

        \App\Models\LoginHistory::create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
            'ip_address' => '192.168.1.50',
            'browser' => 'Firefox 120',
            'platform' => 'Linux',
            'device' => 'Desktop',
            'status' => 'success',
            'login_at' => now(),
        ]);

        // Super Admin gets all histories on Profile Inertia page
        $inertiaRes = $this->actingAs($superAdmin)->get('/profile?scope=all');
        $inertiaRes->assertStatus(200);
        $loginHistories = $inertiaRes->viewData('page')['props']['login_histories'];
        $emails = collect($loginHistories)->pluck('email')->all();
        $this->assertContains($otherUser->email, $emails);

        // Super Admin gets all histories via API
        $apiRes = $this->actingAs($superAdmin, 'sanctum')->getJson('/api/v1/profile/login-histories?scope=all');
        $apiRes->assertStatus(200);
        $apiEmails = collect($apiRes->json('histories.data'))->pluck('email')->all();
        $this->assertContains($otherUser->email, $apiEmails);
    }

    public function test_regular_user_cannot_view_other_users_login_histories(): void
    {
        $regularUser = User::factory()->create();
        $otherUser = User::factory()->create();

        \App\Models\LoginHistory::create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
            'ip_address' => '10.0.0.99',
            'browser' => 'Safari 17',
            'platform' => 'macOS',
            'device' => 'Desktop',
            'status' => 'success',
            'login_at' => now(),
        ]);

        // Attempting scope=all as regular user should still only return regular user's histories
        $inertiaRes = $this->actingAs($regularUser)->get('/profile?scope=all');
        $inertiaRes->assertStatus(200);
        $loginHistories = $inertiaRes->viewData('page')['props']['login_histories'];
        $emails = collect($loginHistories)->pluck('email')->all();
        $this->assertNotContains($otherUser->email, $emails);

        // API call with scope=all as regular user should also only return their own
        $apiRes = $this->actingAs($regularUser, 'sanctum')->getJson('/api/v1/profile/login-histories?scope=all');
        $apiRes->assertStatus(200);
        $apiEmails = collect($apiRes->json('histories.data'))->pluck('email')->all();
        $this->assertNotContains($otherUser->email, $apiEmails);
    }
}

