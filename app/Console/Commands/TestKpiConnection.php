<?php

namespace App\Console\Commands;

use App\Services\KpiApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestKpiConnection extends Command
{
    protected $signature = 'kpi:test {username?} {password?}';
    protected $description = 'Test koneksi dan login ke KPI API untuk debugging';

    public function handle()
    {
        $username = $this->argument('username') ?? config('services.kpi.username');
        $password = $this->argument('password') ?? config('services.kpi.password');

        if (!$username || !$password) {
            $this->error('Username atau password tidak ditemukan. Gunakan: php artisan kpi:test <username> <password>');
            return 1;
        }

        $this->info('=== Testing KPI API Connection ===');
        $this->newLine();

        $config = config('services.kpi');
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        $loginPath = $config['login_path'] ?? '/login';
        $url = $baseUrl . $loginPath;

        $this->info("Base URL: {$baseUrl}");
        $this->info("Login Path: {$loginPath}");
        $this->info("Full URL: {$url}");
        $this->info("Username Field: {$config['username_field']}");
        $this->info("Username: " . str_repeat('*', strlen($username) - 2) . substr($username, -2));
        $this->newLine();

        // Test 1: Basic connectivity
        $this->info('🔍 Test 1: Checking endpoint availability...');
        try {
            $resp = Http::timeout(10)->get($baseUrl);
            $this->line("  Status: {$resp->status()}");
            $this->line("  Content-Type: " . ($resp->header('Content-Type') ?? 'N/A'));
        } catch (\Throwable $e) {
            $this->error("  Error: {$e->getMessage()}");
        }
        $this->newLine();

        // Test 2: Login attempt
        $this->info('🔍 Test 2: Attempting login...');
        $payload = [
            $config['username_field'] ?? 'username' => $username,
            'password' => $password,
        ];

        $this->line("  Payload: " . json_encode(array_merge($payload, ['password' => '***'])));
        $this->newLine();

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($url, $payload);

            $this->line("  HTTP Status: {$resp->status()}");
            $this->line("  Content-Type: " . ($resp->header('Content-Type') ?? 'N/A'));

            $body = $resp->body();
            $isHtml = str_starts_with(trim($body), '<!DOCTYPE') || str_starts_with(trim($body), '<html');

            if ($isHtml) {
                $this->warn("  ⚠️  Response is HTML, not JSON!");
                $this->line("  Preview: " . substr($body, 0, 200) . '...');
            } else {
                $json = $resp->json();
                $this->info("  ✓ Response is JSON");
                $this->line("  Keys: " . implode(', ', array_keys($json ?? [])));
                $this->line("  Full Response:");
                $this->line(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        } catch (\Throwable $e) {
            $this->error("  Error: {$e->getMessage()}");
        }
        $this->newLine();

        // Test 3: Try with service
        $this->info('🔍 Test 3: Using KpiApiService...');
        $service = new KpiApiService();
        $token = $service->authenticate($username, $password);

        if ($token) {
            $this->info("  ✓ Login sukses!");
            $this->line("  Token: " . substr($token, 0, 20) . '...');
        } else {
            $this->error("  ✗ Login gagal!");
            if ($lastError = $service->getLastError()) {
                $this->line("  Error: {$lastError}");
            }
        }

        $this->newLine();
        $this->info('=== Testing Complete ===');

        return $token ? 0 : 1;
    }
}
