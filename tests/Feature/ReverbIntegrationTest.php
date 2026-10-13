<?php

namespace Tests\Feature;

use Laravel\Reverb\ReverbServiceProvider;
use Tests\TestCase;

class ReverbIntegrationTest extends TestCase
{
    public function test_reverb_service_provider_is_registered(): void
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(ReverbServiceProvider::class, $providers);
    }

    public function test_reverb_config_is_loaded(): void
    {
        $this->assertNotNull(config('reverb'));
        $this->assertArrayHasKey('servers', config('reverb'));
        $this->assertArrayHasKey('apps', config('reverb'));
    }

    public function test_reverb_broadcasting_connection_is_configured(): void
    {
        $connections = config('broadcasting.connections');
        $this->assertArrayHasKey('reverb', $connections);
        $this->assertEquals('reverb', $connections['reverb']['driver']);
    }

    public function test_channels_file_exists(): void
    {
        $this->assertFileExists(base_path('routes/channels.php'));
    }
}
