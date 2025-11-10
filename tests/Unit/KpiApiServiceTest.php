<?php

namespace Tests\Unit;

use App\Services\KpiApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class KpiApiServiceTest extends TestCase
{
    /** @test */
    public function authenticate_success_returns_token()
    {
        $base = config('services.kpi.base_url');
        $login = rtrim($base, '/') . (config('services.kpi.login_path') ?? '/login');

        Http::fake([
            $login => Http::response(['token' => 'abc123'], 200),
        ]);

        $service = new KpiApiService();
        $token = $service->authenticate('user', 'pass');

        $this->assertSame('abc123', $token);
        $this->assertNull($service->getLastError());
    }

    /** @test */
    public function authenticate_failure_sets_last_error_and_logs_warning()
    {
        $base = config('services.kpi.base_url');
        $login = rtrim($base, '/') . (config('services.kpi.login_path') ?? '/login');

        Http::fake([
            $login => Http::response(['message' => 'Invalid credentials'], 401),
        ]);

        Log::spy();

        $service = new KpiApiService();
        $token = $service->authenticate('user', 'wrong');

        $this->assertNull($token);
        $this->assertNotNull($service->getLastError());
        $this->assertStringContainsString('HTTP 401', $service->getLastError());

        Log::shouldHaveReceived('warning')->once();
    }

    /** @test */
    public function fetch_case_projects_handles_wrapped_data_key()
    {
        $base = config('services.kpi.base_url');
        $path = rtrim($base, '/') . (config('services.kpi.case_projects_path') ?? '/case-projects');

        Http::fake([
            $path => Http::response(['data' => [
                ['description' => 'Contoh', 'case_date' => '2025-01-01']
            ]], 200),
        ]);

        $service = new KpiApiService();
        $items = $service->fetchCaseProjects('dummy-token');

        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Contoh', $items[0]['description']);
    }
    /** @test */
    public function authenticate_can_extract_access_token_in_data_structure()
    {
        $base = config('services.kpi.base_url');
        $login = rtrim($base, '/') . (config('services.kpi.login_path') ?? '/login');

        Http::fake([
            $login => Http::response([
                'data' => [
                    'access_token' => 'xyz789'
                ],
            ], 200),
        ]);

        $service = new KpiApiService();
        $token = $service->authenticate('user', 'pass');
        $this->assertSame('xyz789', $token);
    }

    /** @test */
    public function authenticate_can_detect_jwt_like_token_anywhere()
    {
        $base = config('services.kpi.base_url');
        $login = rtrim($base, '/') . (config('services.kpi.login_path') ?? '/login');
        $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTYiLCJleHAiOjE2MzAwMDB9.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        Http::fake([
            $login => Http::response([
                'meta' => [
                    'session' => [
                        'value' => $jwt,
                    ],
                ],
            ], 200),
        ]);

        $service = new KpiApiService();
        $token = $service->authenticate('user', 'pass');
        $this->assertSame($jwt, $token);
    }
}
