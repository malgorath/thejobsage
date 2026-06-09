<?php

use App\Models\Candidate;
use App\Models\Job;
use App\Models\JobListingSkill;
use App\Models\Resume;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
use Illuminate\Support\Facades\Http;

/**
 * These tests verify the full pipeline chain:
 *   resume text → PII strip (HTTP) → skill extraction (HTTP)
 *   → match score calculation → summary generation (HTTP) → DB persistence.
 *
 * HTTP is mocked at the transport layer; the ResumeTextExtractor is replaced
 * via the service container to return a known text string without needing a
 * real binary file. This covers the integration gap where individual unit tests
 * all passed but the full pipeline produced empty data when Ollama was unreachable.
 */
beforeEach(function () {
    // Ensure the service always uses ollama native format regardless of .env
    config(['ollama.api_type' => 'ollama']);
});

test('full pipeline: pii-strips, extracts skills, calculates match score, and writes summary', function () {
    $job = Job::factory()->create(['description' => 'PHP Laravel MySQL developer']);
    $phpSkill = JobListingSkill::firstOrCreate(['name' => 'php']);
    $laravelSkill = JobListingSkill::firstOrCreate(['name' => 'laravel']);
    $job->listingSkills()->syncWithoutDetaching([$phpSkill->id, $laravelSkill->id]);

    $resume = Resume::factory()->create([
        'mime_type' => 'application/pdf',
        'file_data'  => 'placeholder',
    ]);

    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    // Text extractor returns known text — no real PDF binary needed
    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->once()
        ->andReturn('Experienced backend developer. Proficient in PHP, Laravel, and MySQL. Built REST APIs for large-scale platforms.');

    // Sequence: (1) stripPii → (2) extractSkills → (3) generateSummary
    // Job already has listing skills, so JobSkillService skips its Ollama call.
    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'Experienced backend developer with strong PHP skills.'], 200)
            ->push(['response' => 'PHP, Laravel, MySQL'], 200)
            ->push(['response' => 'Candidate is a strong backend developer with PHP and database skills.'], 200),
    ]);

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    expect($candidate->status)->toBe('analyzed');
    expect($candidate->anonymized_summary)->toBe('Candidate is a strong backend developer with PHP and database skills.');
    expect($candidate->match_score)->not->toBeNull();
    expect($candidate->match_score)->toBeGreaterThan(0);

    // Skills extracted from step 2 should be persisted via resume_skill pivot
    $resume->load('skills');
    expect($resume->skills->pluck('name'))->toContain('php');
    expect($resume->skills->pluck('name'))->toContain('laravel');
});

test('full pipeline: match score is 100 when all job skills are found in resume', function () {
    $job = Job::factory()->create(['description' => 'PHP dev']);
    $phpSkill = JobListingSkill::firstOrCreate(['name' => 'php']);
    $job->listingSkills()->syncWithoutDetaching([$phpSkill->id]);

    $resume = Resume::factory()->create(['mime_type' => 'application/pdf', 'file_data' => 'placeholder']);
    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->andReturn('PHP expert.');

    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'PHP expert.'], 200)
            ->push(['response' => 'PHP'], 200)
            ->push(['response' => 'Senior PHP developer with extensive experience.'], 200),
    ]);

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    expect($candidate->match_score)->toBe(100);
});

test('full pipeline: match score is 0 when no job skills match resume skills', function () {
    $job = Job::factory()->create(['description' => 'Python dev']);
    $pythonSkill = JobListingSkill::firstOrCreate(['name' => 'python']);
    $job->listingSkills()->syncWithoutDetaching([$pythonSkill->id]);

    $resume = Resume::factory()->create(['mime_type' => 'application/pdf', 'file_data' => 'placeholder']);
    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->andReturn('PHP and JavaScript developer.');

    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'PHP and JavaScript developer.'], 200)
            ->push(['response' => 'PHP, JavaScript'], 200)
            ->push(['response' => 'Frontend-focused developer with no Python background.'], 200),
    ]);

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    expect($candidate->match_score)->toBe(0);
});

test('full pipeline: job skill extraction fires when job has no listing skills yet', function () {
    $job = Job::factory()->create(['description' => 'We need PHP and Vue skills']);
    // Deliberately no listing skills attached — pipeline should call Ollama to extract them

    $resume = Resume::factory()->create(['mime_type' => 'application/pdf', 'file_data' => 'placeholder']);
    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->andReturn('PHP and Vue developer.');

    // Sequence: (1) stripPii → (2) extractSkills (resume) → (3) extractAndAttach (job) → (4) generateSummary
    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'Backend and frontend developer.'], 200)
            ->push(['response' => 'PHP, Vue'], 200)
            ->push(['response' => 'PHP, Vue'], 200)   // job skill extraction
            ->push(['response' => 'Full-stack developer comfortable with PHP and Vue.'], 200),
    ]);

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    expect($candidate->status)->toBe('analyzed');
    expect($candidate->match_score)->not->toBeNull();

    $this->assertDatabaseHas('job_listing_skills', ['name' => 'php']);
    $this->assertDatabaseHas('job_listing_skills', ['name' => 'vue']);
});

test('full pipeline: marks candidate analyzed with empty fields when Ollama is unreachable', function () {
    // No listing skills attached — so match_score returns null (no basis for comparison)
    // when extractSkills returns [] due to the connection failure.
    $job = Job::factory()->create();

    $resume = Resume::factory()->create(['mime_type' => 'application/pdf', 'file_data' => 'placeholder']);
    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->andReturn('PHP developer.');

    // All Ollama HTTP calls throw connection errors
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 7: connection refused');
    });

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    // Pipeline degrades gracefully: status=analyzed, AI fields empty but not crashed
    expect($candidate->status)->toBe('analyzed');
    expect($candidate->match_score)->toBeNull();
    expect($candidate->anonymized_summary)->toBeEmpty();
});

test('full pipeline: marks candidate analyzed when resume has no extractable text', function () {
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['mime_type' => 'application/pdf', 'file_data' => 'placeholder']);
    $candidate = Candidate::factory()->create([
        'job_id'    => $job->id,
        'resume_id' => $resume->id,
        'status'    => 'pending_analysis',
    ]);

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extract')
        ->andReturn(null);

    Http::fake(); // No HTTP should fire

    app(CandidatePipelineService::class)->process($candidate);

    $candidate->refresh();
    expect($candidate->status)->toBe('analyzed');

    Http::assertNothingSent();
});
