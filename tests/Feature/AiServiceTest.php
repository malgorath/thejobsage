<?php

use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->aiService = app(OllamaService::class);
});

test('stripPii returns anonymized text on success', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([
            'response' => 'Software Engineer at Company A with strong PHP skills.',
        ], 200),
    ]);

    $raw = 'John Doe, john@acme.com, Software Engineer at Acme Corp.';
    $result = $this->aiService->stripPii($raw);

    expect($result)->toBeString();
    expect($result)->toContain('Company A');
});

test('stripPii falls back to raw text when ollama returns 500', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([], 500),
    ]);

    $raw = 'Jane Smith, jane@example.com';
    $result = $this->aiService->stripPii($raw);

    expect($result)->toBe($raw);
});

test('stripPii falls back to raw text on malformed response', function () {
    Http::fake([
        config('ollama.api_url') => Http::response(['error' => 'bad model'], 200),
    ]);

    $raw = 'Jane Smith, jane@example.com';
    $result = $this->aiService->stripPii($raw);

    expect($result)->toBe($raw);
});

test('skill extraction returns array of skills', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([
            'response' => 'PHP, Laravel, MySQL',
        ], 200),
    ]);

    $skills = $this->aiService->extractSkills('PHP, Laravel, MySQL developer.');

    expect($skills)->toBeArray();
    expect($skills)->toContain('PHP');
    expect($skills)->toContain('Laravel');
});

test('skill extraction returns empty array on connection failure', function () {
    Http::fake(function () {
        throw new Exception('connection refused');
    });

    $skills = $this->aiService->extractSkills('some resume text');

    expect($skills)->toBeArray();
    expect($skills)->toBeEmpty();
});

test('generateSkillGapSummary returns gap text on success', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([
            'response' => 'The candidate is missing Python and AWS experience required for the role.',
        ], 200),
    ]);

    $result = $this->aiService->generateSkillGapSummary(
        ['python', 'aws', 'php'],
        ['php', 'laravel']
    );

    expect($result)->toBeString();
    expect($result)->toContain('Python');
});

test('generateSkillGapSummary returns empty string when no job skills provided', function () {
    $result = $this->aiService->generateSkillGapSummary([], ['php', 'laravel']);

    expect($result)->toBe('');
});

test('generateSkillGapSummary returns empty string on connection failure', function () {
    Http::fake(function () {
        throw new Exception('connection refused');
    });

    $result = $this->aiService->generateSkillGapSummary(['python', 'aws'], ['php']);

    expect($result)->toBe('');
});
