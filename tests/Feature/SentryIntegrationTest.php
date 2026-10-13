<?php

namespace Tests\Feature;

use Sentry\Laravel\Integration;
use Sentry\Laravel\ServiceProvider as SentryServiceProvider;
use Tests\TestCase;

class SentryIntegrationTest extends TestCase
{
    public function test_sentry_service_provider_is_registered(): void
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(SentryServiceProvider::class, $providers);
    }

    public function test_sentry_config_is_loaded(): void
    {
        $this->assertNotNull(config('sentry'));
        $this->assertArrayHasKey('dsn', config('sentry'));
        $this->assertArrayHasKey('traces_sample_rate', config('sentry'));
    }

    public function test_sentry_log_channel_is_configured(): void
    {
        $channels = config('logging.channels');
        $this->assertArrayHasKey('sentry', $channels);
        $this->assertEquals('sentry', $channels['sentry']['driver']);
    }

    public function test_sentry_integration_handles_method_exists(): void
    {
        $this->assertTrue(method_exists(Integration::class, 'handles'));
    }
}
