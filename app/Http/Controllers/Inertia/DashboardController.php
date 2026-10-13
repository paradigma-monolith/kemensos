<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the main hybrid hub / landing page.
     */
    public function index(): Response
    {
        $stats = [
            'total_products' => Product::count(),
            'total_stock' => Product::sum('stock'),
            'total_valuation' => Product::selectRaw('SUM(price * stock) as total')->value('total') ?? 0,
            'channel_breakdown' => [
                'inertia' => Product::where('created_by_channel', 'Inertia Bridge')->count(),
                'spa_api' => Product::where('created_by_channel', 'SPA API')->count(),
                'system' => Product::where('created_by_channel', 'System')->count(),
            ],
            'recent_products' => Product::latest()->take(5)->get(),
            'database' => [
                'driver' => config('database.default'),
                'database' => config('database.connections.mysql.database'),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'architecture' => 'Hybrid (Inertia.js Monolith Bridge + Decoupled SPA via REST API)',
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
        ];

        return Inertia::render('HomeHub', [
            'stats' => $stats,
        ]);
    }

    /**
     * Inertia-specific dedicated dashboard.
     */
    public function dashboard(): Response
    {
        $products = Product::latest()->paginate(10);

        $categories = Product::selectRaw('category, COUNT(*) as count, SUM(stock) as total_stock, ROUND(AVG(price), 2) as avg_price')
            ->groupBy('category')
            ->orderByDesc('total_stock')
            ->get();

        $channels = [
            'inertia' => Product::where('created_by_channel', 'Inertia Bridge')->count(),
            'spa_api' => Product::where('created_by_channel', 'SPA API')->count(),
            'system' => Product::where('created_by_channel', 'System')->count(),
        ];

        return Inertia::render('Inertia/Dashboard', [
            'products' => $products,
            'summary' => [
                'count' => Product::count(),
                'inertia_count' => $channels['inertia'],
                'api_count' => $channels['spa_api'],
                'system_count' => $channels['system'],
                'total_stock' => (int) Product::sum('stock'),
                'total_valuation' => (float) (Product::selectRaw('SUM(price * stock) as total')->value('total') ?? 0),
            ],
            'analytics' => [
                'categories' => $categories,
                'channels' => $channels,
            ],
        ]);
    }

    /**
     * MapLibre GL JS interactive geospatial demo page.
     */
    public function mapDemo(): Response
    {
        $serverNodes = [
            [
                'id' => 'jkt-01',
                'name' => 'Jakarta Hybrid Gateway (Primary HQ)',
                'location' => 'Cyber 2 Tower, Kuningan, Jakarta',
                'coordinates' => [106.8272, -6.1751],
                'status' => 'online',
                'ping' => '4ms',
                'connections' => 1420,
                'type' => 'Core Engine (Inertia & Octane)',
                'flag' => '🇮🇩',
            ],
            [
                'id' => 'sin-01',
                'name' => 'Singapore Equinix SG1 Node',
                'location' => 'Ayer Rajah, Singapore',
                'coordinates' => [103.8519, 1.2902],
                'status' => 'online',
                'ping' => '12ms',
                'connections' => 840,
                'type' => 'Edge CDN & Redis Cache',
                'flag' => '🇸🇬',
            ],
            [
                'id' => 'tko-01',
                'name' => 'Tokyo Otemachi Cloud Cluster',
                'location' => 'Chiyoda, Tokyo, Japan',
                'coordinates' => [139.6917, 35.6895],
                'status' => 'online',
                'ping' => '48ms',
                'connections' => 520,
                'type' => 'Regional Edge Gateway',
                'flag' => '🇯🇵',
            ],
            [
                'id' => 'fra-01',
                'name' => 'Frankfurt Main Datahub',
                'location' => 'Frankfurt am Main, Germany',
                'coordinates' => [8.6821, 50.1109],
                'status' => 'online',
                'ping' => '142ms',
                'connections' => 310,
                'type' => 'Backup & Audit Log Vault',
                'flag' => '🇩🇪',
            ],
            [
                'id' => 'sfo-01',
                'name' => 'Silicon Valley Cloud Fabric',
                'location' => 'Santa Clara, California, USA',
                'coordinates' => [-122.4194, 37.7749],
                'status' => 'online',
                'ping' => '168ms',
                'connections' => 640,
                'type' => 'AI & Microservices Hub',
                'flag' => '🇺🇸',
            ],
            [
                'id' => 'syd-01',
                'name' => 'Sydney Oceanic Relay',
                'location' => 'Sydney, NSW, Australia',
                'coordinates' => [151.2093, -33.8688],
                'status' => 'online',
                'ping' => '96ms',
                'connections' => 290,
                'type' => 'Oceanic Gateway',
                'flag' => '🇦🇺',
            ],
        ];

        return Inertia::render('Inertia/MapDemo', [
            'nodes' => $serverNodes,
            'summary' => [
                'total_nodes' => count($serverNodes),
                'active_connections' => array_sum(array_column($serverNodes, 'connections')),
                'avg_latency' => '78ms',
                'global_uptime' => '99.99%',
            ],
        ]);
    }
}
