<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class StatsApiController extends Controller
{
    /**
     * Return architectural and system stats for Traditional SPA.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'architecture' => [
                'type' => 'Hybrid Architecture',
                'active_channel' => 'Traditional SPA (Vue 3 Client-Side + REST API)',
                'bridge' => 'Inertia.js Bridge co-existing in same codebase',
                'database' => [
                    'driver' => 'MySQL',
                    'database' => config('database.connections.mysql.database'),
                    'connection' => 'Active',
                ],
            ],
            'metrics' => [
                'total_products' => Product::count(),
                'total_stock' => Product::sum('stock'),
                'by_channel' => [
                    'Inertia Bridge' => Product::where('created_by_channel', 'Inertia Bridge')->count(),
                    'SPA API' => Product::where('created_by_channel', 'SPA API')->count(),
                    'System' => Product::where('created_by_channel', 'System')->count(),
                ],
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'api_endpoint' => url('/api/v1'),
            ],
            'infrastructure' => [
                'octane' => [
                    'installed' => class_exists(\Laravel\Octane\Octane::class),
                    'server' => config('octane.server', 'roadrunner'),
                    'in_octane' => isset($_SERVER['LARAVEL_OCTANE']),
                    'description' => 'In-Memory Framework Execution via RoadRunner (rr.exe)',
                ],
                'redis' => \App\Services\RedisService::getTelemetry(),
                'sentry' => [
                    'installed' => class_exists(\Sentry\Laravel\Integration::class),
                    'configured' => !empty(config('sentry.dsn')),
                    'environment' => config('sentry.environment', config('app.env')),
                    'description' => 'Full-Stack Error Monitoring, Browser Tracing & Session Replay',
                ],
                'pulse' => [
                    'installed' => class_exists(\Laravel\Pulse\Pulse::class),
                    'enabled' => config('pulse.enabled', true),
                    'path' => config('pulse.path', 'pulse'),
                    'description' => 'Real-Time Health Monitoring, Slow Queries & Server Metrics',
                ],
                'reverb' => [
                    'installed' => class_exists(\Laravel\Reverb\ReverbServiceProvider::class),
                    'broadcaster' => config('broadcasting.default'),
                    'host' => config('reverb.servers.reverb.hostname') ?: config('reverb.servers.reverb.host', 'localhost'),
                    'port' => config('reverb.servers.reverb.port', 8080),
                    'description' => 'First-Party High-Speed WebSocket Server & Echo',
                ],
            ],
        ]);
    }
}
