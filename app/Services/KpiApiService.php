<?php

namespace App\Services;

use App\Models\CaseProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class KpiApiService
{
    protected string $baseUrl;
    protected string $loginPath;
    protected string $caseProjectsPath;
    protected int $timeout;
    protected ?string $lastError = null;
    protected string $usernameField;

    public function __construct()
    {
        $cfg = config('services.kpi');
        $this->baseUrl = rtrim($cfg['base_url'] ?? '', '/');
        $this->loginPath = $cfg['login_path'] ?? '/login';
        $this->caseProjectsPath = $cfg['case_projects_path'] ?? '/case-projects';
        $this->timeout = (int)($cfg['timeout'] ?? 15);
        $this->usernameField = $cfg['username_field'] ?? 'username';
    }

    /**
     * Authenticate and return bearer token.
     */
    public function authenticate(string $username, string $password): ?string
    {
        $url = $this->baseUrl . $this->loginPath;
        try {
            $payload = [
                $this->usernameField => $username,
                'password' => $password,
            ];
            $resp = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($url, $payload);

            if ($resp->successful()) {
                $body = $resp->body();

                // Check if response is HTML instead of JSON
                if (str_starts_with(trim($body), '<!DOCTYPE') || str_starts_with(trim($body), '<html')) {
                    $this->lastError = 'API mengembalikan HTML, bukan JSON. Endpoint mungkin salah atau tidak tersedia.';
                    Log::warning('KPI auth: HTML response instead of JSON', [
                        'url' => $url,
                        'status' => $resp->status(),
                        'content_type' => $resp->header('Content-Type'),
                        'body_preview' => $this->truncate($body, 200),
                    ]);
                    return null;
                }

                $json = $resp->json();
                if (!is_array($json)) {
                    $this->lastError = 'Response bukan JSON valid.';
                    Log::warning('KPI auth: invalid JSON', [
                        'url' => $url,
                        'body' => $this->truncate($body),
                    ]);
                    return null;
                }

                $token = $this->extractToken($json);
                if (!$token) {
                    $this->lastError = 'Login sukses (HTTP 200) tetapi token tidak ditemukan di response. Response: ' . $this->truncate(json_encode($json), 300);
                    Log::warning('KPI auth: token missing', [
                        'url' => $url,
                        'status' => $resp->status(),
                        'response_keys' => array_keys($json),
                        'body' => $this->truncate(json_encode($json)),
                    ]);
                }
                return $token;
            }
            $this->lastError = 'HTTP ' . $resp->status() . ' saat login. Body: ' . $this->truncate($resp->body());
            Log::warning('KPI auth failed', [
                'url' => $url,
                'status' => $resp->status(),
                // never log password; include only masked username for context
                'username' => $this->mask($username),
                'body' => $this->truncate($resp->body()),
            ]);
        } catch (\Throwable $e) {
            $this->lastError = 'Exception saat login: ' . $e->getMessage();
            Log::error('KPI auth exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
        }
        return null;
    }

    /**
     * Fetch case projects from KPI API.
     * @return array<int,array<string,mixed>>
     */
    public function fetchCaseProjects(string $token): array
    {
        $url = $this->baseUrl . $this->caseProjectsPath;
        try {
            $resp = Http::timeout($this->timeout)->withToken($token)->get($url);
            if ($resp->successful()) {
                $data = $resp->json();
                // Normalize: if API returns { data: [...] }
                if (isset($data['data']) && is_array($data['data'])) {
                    $data = $data['data'];
                }
                return is_array($data) ? $data : [];
            }
            $this->lastError = 'HTTP ' . $resp->status() . ' saat fetch case-projects. Body: ' . $this->truncate($resp->body());
            Log::warning('KPI fetch case projects failed', [
                'url' => $url,
                'status' => $resp->status(),
                'body' => $this->truncate($resp->body()),
            ]);
        } catch (\Throwable $e) {
            $this->lastError = 'Exception saat fetch: ' . $e->getMessage();
            Log::error('KPI fetch exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
        }
        return [];
    }

    /**
     * Sync fetched case projects into local database.
     * Returns summary metrics.
     * Expected remote item keys (adjust mapping as needed):
     *  - id / external_id
     *  - description
     *  - case_date (Y-m-d)
     *  - status
     *  - staff (object with id)
     *  - client (object with id)
     *  - link_dokumen
     */
    public function sync(array $remoteItems): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($remoteItems as $item) {
            if (!is_array($item)) {
                $skipped++;
                continue;
            }

            $externalId = $item['id'] ?? $item['external_id'] ?? null;
            if (!$externalId) {
                $skipped++;
                $errors[] = 'Item tanpa ID: ' . json_encode($item);
                continue;
            }

            // Extract IDs from nested objects
            $staffId = null;
            if (isset($item['staff']) && is_array($item['staff'])) {
                $staffId = $item['staff']['id'] ?? null;
            } elseif (isset($item['staff_id'])) {
                $staffId = $item['staff_id'];
            }

            $clientId = null;
            if (isset($item['client']) && is_array($item['client'])) {
                $clientId = $item['client']['id'] ?? null;
            } elseif (isset($item['client_id'])) {
                $clientId = $item['client_id'];
            }

            if (!$staffId || !$clientId) {
                $skipped++;
                $errors[] = "Item ID {$externalId} ('{$item['description']}'): staff_id atau client_id kosong";
                Log::warning('KPI sync: skipping item with null staff/client', [
                    'external_id' => $externalId,
                    'staff_id' => $staffId,
                    'client_id' => $clientId,
                    'description' => $item['description'] ?? 'N/A',
                    'staff_data' => $item['staff'] ?? null,
                    'client_data' => $item['client'] ?? null,
                ]);
                continue;
            }

            // We store by some unique combination; if table has no external_id column yet, you may add one later.
            // For now attempt match by description+case_date as fallback.
            $model = CaseProject::query()->where('description', $item['description'] ?? '')
                ->where('case_date', $item['case_date'] ?? null)
                ->first();

            $payload = [
                'description' => $item['description'] ?? '',
                'case_date' => $item['case_date'] ?? null,
                'status' => $item['status'] ?? 'pending',
                'staff_id' => $staffId,
                'client_id' => $clientId,
                'link_dokumen' => $item['link_dokumen'] ?? null,
            ];

            try {
                if ($model) {
                    $model->fill($payload);
                    if ($model->isDirty()) {
                        $model->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    CaseProject::create($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Item ID {$externalId}: " . $e->getMessage();
                Log::error('KPI sync: database error', [
                    'external_id' => $externalId,
                    'error' => $e->getMessage(),
                    'payload' => $payload,
                ]);
            }
        }

        if (!empty($errors)) {
            Log::warning('KPI sync completed with errors', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 10), // max 10 errors in log
            ]);
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * Get last error message (for UI display/logging).
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function truncate(?string $text, int $limit = 400): string
    {
        $text = (string) $text;
        return mb_strlen($text) > $limit ? (mb_substr($text, 0, $limit) . '…') : $text;
    }

    protected function mask(string $value): string
    {
        if ($value === '') return '';
        $len = mb_strlen($value);
        return mb_substr($value, 0, 1) . str_repeat('*', max(0, $len - 2)) . mb_substr($value, -1);
    }

    /**
     * Try multiple possible token keys.
     */
    protected function extractToken($json): ?string
    {
        if (!is_array($json)) return null;
        $candidates = [
            'token',
            'access_token',
            'data.token',
            'data.access_token',
            'data.authorization.token',
        ];
        foreach ($candidates as $key) {
            $value = data_get($json, $key);
            if (is_string($value) && $value !== '') return $value;
        }
        // Some APIs respond with { success:true, message:"...", data:{...token...}} etc.
        // Attempt to locate any string that looks like JWT (three dot separated segments) or long random.
        $flatten = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($json));
        foreach ($flatten as $v) {
            if (!is_string($v)) continue;
            if (preg_match('/^[A-Za-z0-9-_]{8,}\.[A-Za-z0-9-_]{8,}\.[A-Za-z0-9-_]{8,}$/', $v)) {
                return $v; // looks like JWT
            }
        }
        return null;
    }
}
