<?php

use Illuminate\Support\Facades\Http;

test('health endpoint returns 200 and ok status when all systems are up', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $response = $this->get('/health');

    $response->assertOk();
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('database.status', 'ok');
    $response->assertJsonPath('ollama.status', 'ok');
    $response->assertJsonPath('queue.status', 'ok');
    $response->assertJsonStructure([
        'status',
        'database' => ['status'],
        'ollama'   => ['status', 'latency_ms'],
        'queue'    => ['status', 'driver'],
    ]);
});

test('health endpoint returns 503 and degraded status when Ollama is unreachable', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 7: connection refused');
    });

    $response = $this->get('/health');

    $response->assertStatus(503);
    $response->assertJsonPath('status', 'degraded');
    $response->assertJsonPath('ollama.status', 'error');
});

test('health endpoint is accessible without authentication', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $this->get('/health')->assertOk();
});
