<?php

use App\Models\Job;
use App\Models\JobListingSkill;
use App\Services\CandidatePipelineService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['ollama.api_type' => 'ollama']);
});

/**
 * Helper: call runPipeline with a mocked Ollama that returns specific extracted skills.
 * Returns the match_score from the pipeline result.
 */
function pipelineScore(Job $job, string $extractedSkills): ?int
{
    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'Anonymized candidate text.'], 200)   // stripPii
            ->push(['response' => $extractedSkills], 200)               // extractSkills
            ->push(['response' => 'Professional developer summary.'], 200), // generateSummary
    ]);

    $result = app(CandidatePipelineService::class)->runPipeline('raw resume text', $job);

    return $result['match_score'];
}

// ─── Case-insensitive matching ────────────────────────────────────────────────

test('skill matching is case-insensitive: uppercase job skill matches lowercase extracted', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'PHP'])->id);

    expect(pipelineScore($job, 'php'))->toBe(100);
});

test('skill matching is case-insensitive: lowercase job skill matches uppercase extracted', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'laravel'])->id);

    expect(pipelineScore($job, 'Laravel'))->toBe(100);
});

test('skill matching is case-insensitive: mixed-case both sides still match', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'TypeScript'])->id);

    expect(pipelineScore($job, 'typescript'))->toBe(100);
});

// ─── Parenthetical skill matching ────────────────────────────────────────────

test('parenthetical skill: PHP (Laravel) extracted skill matches job skill laravel', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'laravel'])->id);

    expect(pipelineScore($job, 'PHP (Laravel)'))->toBe(100);
});

test('parenthetical skill: PHP (Laravel) extracted skill matches job skill php', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'php'])->id);

    expect(pipelineScore($job, 'PHP (Laravel)'))->toBe(100);
});

test('parenthetical skill: both php and laravel job skills match PHP (Laravel) extracted', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'php'])->id);
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'laravel'])->id);

    // Both job skills satisfied by the single parenthetical extracted skill
    expect(pipelineScore($job, 'PHP (Laravel)'))->toBe(100);
});

test('parenthetical skill with multiple extracted skills covers all job requirements', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'php'])->id);
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'laravel'])->id);
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'mysql'])->id);

    // "PHP (Laravel)" covers php and laravel; "MySQL" covers mysql
    expect(pipelineScore($job, 'PHP (Laravel), MySQL'))->toBe(100);
});

// ─── Partial / contained term matching ───────────────────────────────────────

test('dotted skill: React.js extracted matches job skill react', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'react'])->id);

    expect(pipelineScore($job, 'React.js'))->toBe(100);
});

test('dotted skill: Node.js extracted matches job skill node', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'node'])->id);

    expect(pipelineScore($job, 'Node.js'))->toBe(100);
});

test('multi-word extracted skill: Node JS Development matches job skill node', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'node'])->id);

    expect(pipelineScore($job, 'Node JS Development'))->toBe(100);
});

// ─── Non-matching (word-boundary safety) ─────────────────────────────────────

test('java does not match javascript', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'java'])->id);

    expect(pipelineScore($job, 'JavaScript'))->toBe(0);
});

test('php does not match phpunit', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'phpunit'])->id);

    expect(pipelineScore($job, 'php'))->toBe(0);
});

test('completely unrelated skills score zero', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'python'])->id);

    expect(pipelineScore($job, 'PHP, Laravel, MySQL'))->toBe(0);
});

// ─── Partial match (some skills found, not all) ───────────────────────────────

test('partial match: one of two job skills found returns 50 percent', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'php'])->id);
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'vue'])->id);

    // Candidate has PHP (Laravel) — matches php but NOT vue
    expect(pipelineScore($job, 'PHP (Laravel)'))->toBe(50);
});

// ─── Special characters preserved for specific languages ─────────────────────

test('C++ job skill matches C++ extracted skill', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'C++'])->id);

    expect(pipelineScore($job, 'C++'))->toBe(100);
});

test('C# job skill matches C# extracted skill', function () {
    $job = Job::factory()->create();
    $job->listingSkills()->attach(JobListingSkill::firstOrCreate(['name' => 'C#'])->id);

    expect(pipelineScore($job, 'C#'))->toBe(100);
});
