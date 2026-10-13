<?php

namespace Tests\Feature;

use App\Services\RedisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedisIntegrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_predis_package_is_installed(): void
    {
        $this->assertTrue(class_exists(\Predis\Client::class));
    }

    public function test_redis_config_is_loaded(): void
    {
        $redisConfig = config('database.redis');
        $this->assertIsArray($redisConfig);
        $this->assertArrayHasKey('client', $redisConfig);
        $this->assertEquals('predis', $redisConfig['client']);
        $this->assertArrayHasKey('default', $redisConfig);
        $this->assertEquals('127.0.0.1', $redisConfig['default']['host']);
        $this->assertEquals('6379', (string) $redisConfig['default']['port']);
    }

    public function test_redis_service_telemetry_structure(): void
    {
        $telemetry = RedisService::getTelemetry();

        $this->assertArrayHasKey('installed', $telemetry);
        $this->assertTrue($telemetry['installed']);
        $this->assertArrayHasKey('client', $telemetry);
        $this->assertEquals('predis', $telemetry['client']);
        $this->assertArrayHasKey('host', $telemetry);
        $this->assertArrayHasKey('port', $telemetry);
        $this->assertArrayHasKey('features', $telemetry);
        $this->assertIsArray($telemetry['features']);
    }

    public function test_stats_api_contains_redis_telemetry(): void
    {
        $response = $this->get('/api/v1/stats');

        $response->assertStatus(200);
        $response->assertJsonPath('infrastructure.redis.installed', true);
        $response->assertJsonPath('infrastructure.redis.client', 'predis');
        $response->assertJsonPath('infrastructure.redis.port', 6379);
    }
}
