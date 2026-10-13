<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class EnterpriseModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_roles_and_permissions_are_seeded(): void
    {
        $admin = User::where('email', 'admin@hybrid.local')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertTrue($admin->hasPermissionTo('manage products'));

        $staff = User::where('email', 'staff@hybrid.local')->first();
        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('Staff'));
        $this->assertFalse($staff->hasPermissionTo('manage users'));
    }

    public function test_product_mutations_are_logged_to_activity_log(): void
    {
        $admin = User::where('email', 'admin@hybrid.local')->first();

        $product = Product::create([
            'name' => 'Audiophile Headphones',
            'category' => 'Audio',
            'price' => 199.99,
            'stock' => 25,
            'status' => 'in_stock',
            'created_by_channel' => 'Inertia Bridge',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'product',
            'subject_id' => $product->id,
            'event' => 'created',
        ]);

        $product->update(['price' => 179.99]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'product',
            'subject_id' => $product->id,
            'event' => 'updated',
        ]);
    }

    public function test_user_can_view_profile_and_update_name(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->patch('/profile', [
            'name' => 'Demo Super Admin Updated',
            'email' => 'admin@hybrid.local',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Demo Super Admin Updated',
        ]);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        $user = User::where('email', 'admin@hybrid.local')->first();

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_export_endpoints_return_successful_downloads(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        Product::create([
            'name' => 'Keyboard Mechanical',
            'category' => 'Hardware',
            'price' => 89.00,
            'stock' => 10,
            'status' => 'in_stock',
            'created_by_channel' => 'System',
        ]);

        // Excel
        $excelResponse = $this->actingAs($user)->get('/exports/products/excel');
        $excelResponse->assertStatus(200);
        $this->assertStringContainsString('products-', (string) $excelResponse->headers->get('content-disposition'));

        // CSV
        $csvResponse = $this->actingAs($user)->get('/exports/products/csv');
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('products-', (string) $csvResponse->headers->get('content-disposition'));

        // PDF
        $pdfResponse = $this->actingAs($user)->get('/exports/products/pdf');
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_notification_flow_and_demo_dispatch(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        $demoResponse = $this->actingAs($user)->postJson('/api/v1/notifications/demo');
        $demoResponse->assertStatus(200);

        $this->assertEquals(1, $user->unreadNotifications()->count());

        $readResponse = $this->actingAs($user)->postJson('/api/v1/notifications/read-all');
        $readResponse->assertStatus(200);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_authenticated_user_can_access_spa_without_blade_parse_error(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        $response = $this->actingAs($user)->get('/spa');
        $response->assertStatus(200);
        $response->assertSee('window.authUser = {', false);
        $response->assertSee('admin@hybrid.local', false);
    }

    public function test_scramble_api_docs_endpoint_is_accessible(): void
    {
        $response = $this->get('/docs/api');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_inertia_dashboard_with_chart_data(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        $response = $this->actingAs($user)->get('/inertia/dashboard');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_map_demo_endpoint(): void
    {
        $user = User::where('email', 'admin@hybrid.local')->first();

        $response = $this->actingAs($user)->get('/inertia/map-demo');
        $response->assertStatus(200);
    }
}
