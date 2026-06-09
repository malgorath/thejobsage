<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

/**
 * Returns a JSON health snapshot for monitoring and ops dashboards.
 *
 * Checks: database connectivity, Ollama API reachability, and queue backend.
 * Returns HTTP 200 when all checks pass, 503 when any check reports an error.
 */
class HealthController extends Controller
{
    /**
     * Run all health checks and return the aggregate result.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'ollama'   => $this->checkOllama(),
            'queue'    => $this->checkQueue(),
        ];

        $allOk = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json(
            array_merge(['status' => $allOk ? 'ok' : 'degraded'], $checks),
            $allOk ? 200 : 503
        );
    }

    /**
     * Verify the primary database connection is reachable.
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Ping the configured AI backend using a lightweight endpoint.
     *
     * For Ollama native format: GET /api/tags (lists loaded models, cheap).
     * For OpenAI-compatible format: GET /v1/models.
     * A 5-second timeout is used to avoid blocking the health check.
     */
    private function checkOllama(): array
    {
        $start = microtime(true);

        try {
            $apiUrl  = config('ollama.api_url', '');
            $apiType = config('ollama.api_type', 'ollama');

            $parsed = parse_url($apiUrl);
            $base   = ($parsed['scheme'] ?? 'http').'://'.($parsed['host'] ?? 'localhost');
            if (isset($parsed['port'])) {
                $base .= ':'.$parsed['port'];
            }

            $pingUrl  = $apiType === 'openai' ? $base.'/v1/models' : $base.'/api/tags';
            $response = Http::timeout(5)->get($pingUrl);
            $ms       = (int) ((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                return ['status' => 'ok', 'latency_ms' => $ms];
            }

            return ['status' => 'error', 'latency_ms' => $ms, 'message' => "HTTP {$response->status()}"];
        } catch (\Throwable $e) {
            $ms = (int) ((microtime(true) - $start) * 1000);

            return ['status' => 'error', 'latency_ms' => $ms, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify the queue backend connection is reachable.
     *
     * Pings Redis when QUEUE_CONNECTION=redis; queries the jobs table for
     * database-backed queues; reports ok immediately for the sync driver.
     */
    private function checkQueue(): array
    {
        $driver = config('queue.default', 'sync');

        try {
            if ($driver === 'redis') {
                Redis::ping();
            } elseif ($driver === 'database') {
                DB::table('jobs')->count();
            }

            return ['status' => 'ok', 'driver' => $driver];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'driver' => $driver, 'message' => $e->getMessage()];
        }
    }
}
