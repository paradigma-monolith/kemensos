<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisService
{
    /**
     * Determine if Redis client package or extension is installed.
     */
    public static function isInstalled(): bool
    {
        return class_exists(\Predis\Client::class) || extension_loaded('redis');
    }

    /**
     * Check if Redis server connection is actively responding.
     */
    public static function isConnected(): bool
    {
        if (!self::isInstalled()) {
            return false;
        }

        try {
            $clientType = Config::get('database.redis.client', 'predis');
            $host = Config::get('database.redis.default.host', '127.0.0.1');
            $port = (int) Config::get('database.redis.default.port', 6379);

            if ($clientType === 'predis' && class_exists(\Predis\Client::class)) {
                $client = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $host,
                    'port' => $port,
                    'timeout' => 0.1,
                ]);
                $client->connect();
                $connected = $client->isConnected();
                $client->disconnect();
                return $connected;
            }

            // Fallback for phpredis or facade
            $redis = Redis::connection();
            return (bool) $redis->ping();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get detailed Redis telemetry data for dashboard & APIs.
     *
     * @return array<string, mixed>
     */
    public static function getTelemetry(): array
    {
        $installed = self::isInstalled();
        $connected = self::isConnected();
        $client = Config::get('database.redis.client', 'predis');
        $host = Config::get('database.redis.default.host', '127.0.0.1');
        $port = (int) Config::get('database.redis.default.port', 6379);
        $prefix = Config::get('database.redis.options.prefix', 'hybrid-laravel-database-');

        return [
            'installed' => $installed,
            'connected' => $connected,
            'client' => $client,
            'driver' => class_exists(\Predis\Client::class) ? 'Predis (v3.7)' : (extension_loaded('redis') ? 'phpredis' : 'none'),
            'host' => $host,
            'port' => $port,
            'prefix' => $prefix,
            'status' => $connected ? 'Connected' : 'Ready (Configured)',
            'description' => 'In-Memory Data Store, Blazing-Fast Key-Value Cache & Pub/Sub',
            'features' => [
                'In-Memory Key/Value Store',
                'High-Throughput Cache Store',
                'Distributed Session Driver',
                'Queue Worker Backend',
                'Pub/Sub Broadcasting Engine',
            ],
        ];
    }
}
