<?php

namespace Database\Seeders;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoginHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@hybrid.local')->first();
        if (! $admin) {
            return;
        }

        // Only seed if empty
        if ($admin->loginHistories()->count() > 0) {
            return;
        }

        $records = [
            [
                'user_id' => $admin->id,
                'email' => $admin->email,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'browser' => 'Chrome 122',
                'platform' => 'Windows 10/11',
                'device' => 'Desktop',
                'location' => 'Jaringan Lokal / Private Network',
                'status' => 'success',
                'login_at' => now()->subMinutes(15),
            ],
            [
                'user_id' => $admin->id,
                'email' => $admin->email,
                'ip_address' => '192.168.1.105',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                'browser' => 'Safari 17',
                'platform' => 'iOS',
                'device' => 'Mobile',
                'location' => 'Jaringan Lokal / Private Network',
                'status' => 'success',
                'login_at' => now()->subHours(6),
            ],
            [
                'user_id' => $admin->id,
                'email' => $admin->email,
                'ip_address' => '182.253.140.22',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/121.0.0.0 Safari/537.36',
                'browser' => 'Edge 121',
                'platform' => 'Windows 10/11',
                'device' => 'Desktop',
                'location' => 'Jakarta, Indonesia (Public IP)',
                'status' => 'success',
                'login_at' => now()->subDays(1),
            ],
            [
                'user_id' => $admin->id,
                'email' => $admin->email,
                'ip_address' => '103.247.112.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
                'browser' => 'Firefox 122',
                'platform' => 'Windows 10/11',
                'device' => 'Desktop',
                'location' => 'Surabaya, Indonesia (Public IP)',
                'status' => 'failed',
                'login_at' => now()->subDays(2),
            ],
        ];

        foreach ($records as $record) {
            LoginHistory::create($record);
        }
    }
}
