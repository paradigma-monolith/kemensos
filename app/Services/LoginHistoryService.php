<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;

class LoginHistoryService
{
    /**
     * Record a successful authentication attempt.
     */
    public static function recordSuccess(User $user, Request $request): LoginHistory
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $ua = $request->userAgent() ?? 'Unknown User-Agent';
        $parsed = self::parseUserAgent($ua);
        $location = self::resolveLocation($ip);

        $history = LoginHistory::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'device' => $parsed['device'],
            'location' => $location,
            'status' => 'success',
            'login_at' => now(),
        ]);

        if (function_exists('activity')) {
            activity('auth')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $ip,
                    'browser' => $parsed['browser'],
                    'platform' => $parsed['platform'],
                    'device' => $parsed['device'],
                    'location' => $location,
                ])
                ->log("Login berhasil via {$parsed['device']} ({$parsed['browser']} di {$parsed['platform']}) [IP: {$ip}]");
        }

        return $history;
    }

    /**
     * Record a failed authentication attempt.
     */
    public static function recordFailed(string $email, Request $request): LoginHistory
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $ua = $request->userAgent() ?? 'Unknown User-Agent';
        $parsed = self::parseUserAgent($ua);
        $location = self::resolveLocation($ip);
        $matchedUser = User::where('email', $email)->first();

        $history = LoginHistory::create([
            'user_id' => $matchedUser?->id,
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'device' => $parsed['device'],
            'location' => $location,
            'status' => 'failed',
            'login_at' => now(),
        ]);

        if (function_exists('activity')) {
            $act = activity('auth')
                ->withProperties([
                    'email' => $email,
                    'ip' => $ip,
                    'browser' => $parsed['browser'],
                    'platform' => $parsed['platform'],
                ]);

            if ($matchedUser) {
                $act->causedBy($matchedUser);
            }

            $act->log("Percobaan login gagal untuk akun [{$email}] [IP: {$ip}]");
        }

        return $history;
    }

    /**
     * Parse User-Agent string to detect browser, platform, and device type.
     *
     * @return array{browser: string, platform: string, device: string}
     */
    public static function parseUserAgent(string $ua): array
    {
        // Device Type
        $device = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $device = 'Tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $ua)) {
            $device = 'Mobile';
        }

        // Operating System / Platform
        $platform = 'Unknown OS';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $platform = 'iOS';
        } elseif (preg_match('/android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/windows nt 10/i', $ua)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/windows/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $platform = 'Linux';
        }

        // Browser Detection
        $browser = 'Web Browser';
        if (preg_match('/edg\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Edge ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/opr\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Opera ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Chrome ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Firefox ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/safari\/([0-9\.]+)/i', $ua, $matches) && !preg_match('/chrome/i', $ua)) {
            $browser = 'Safari ' . explode('.', $matches[1])[0];
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
        ];
    }

    /**
     * Resolve location label based on IP address.
     */
    public static function resolveLocation(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.') || str_starts_with($ip, '172.16.')) {
            return 'Jaringan Lokal / Private Network';
        }

        return 'Indonesia (Public IP)';
    }
}
