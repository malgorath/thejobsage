<?php

use App\Models\Prompt;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('returns raw text when ollama is unreachable during pii strip', function () {
    Http::fake(function () {
        throw new Exception('connection refused');
    });

    $service = app(OllamaService::class);
    $raw = 'John Doe, john@example.com, Software Engineer';
    $result = $service->stripPii($raw);

    // Falls back to the original text on connection failure
    expect($result)->toBe($raw);
});

it('uses database prompt body when pii_strip prompt exists', function () {
    Prompt::create([
        'key'    => 'pii_strip',
        'title'  => 'PII Strip',
        'body'   => 'Strip PII: {{resume_text}}',
        'config' => ['temperature' => 0.1],
    ]);

    $captured = null;
    Http::fake(function ($request) use (&$captured) {
        $captured = $request->data();

        return Http::response(['response' => 'anonymized text'], 200);
    });

    $service = app(OllamaService::class);
    $service->stripPii('hello world');

    expect($captured['prompt'])->toContain('Strip PII: hello world');
    expect($captured['temperature'])->toBe(0.1);
});

it('returns extracted skills as array', function () {
    Http::fake([
        config('ollama.api_url') => Http::response(['response' => 'PHP, Laravel, JavaScript'], 200),
    ]);

    $service = app(OllamaService::class);
    $skills = $service->extractSkills('Experienced in PHP, Laravel, and JavaScript.');

    expect($skills)->toBeArray();
    expect($skills)->toContain('PHP');
    expect($skills)->toContain('Laravel');
});
