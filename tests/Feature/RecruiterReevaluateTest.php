<?php

use App\Models\Candidate;
use App\Models\Job;
use App\Models\JobListingSkill;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;
use App\Services\CandidatePipelineService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['ollama.api_type' => 'ollama']);

    $this->recruiter = User::factory()->create(['role' => 'recruiter']);

    $this->job = Job::factory()->create();
    $phpSkill  = JobListingSkill::firstOrCreate(['name' => 'php']);
    $this->job->listingSkills()->attach($phpSkill->id);

    $this->resume    = Resume::factory()->create(['mime_type' => 'application/pdf']);
    $this->candidate = Candidate::factory()->create([
        'job_id'           => $this->job->id,
        'resume_id'        => $this->resume->id,
        'status'           => 'analyzed',
        'match_score'      => 40,
        'anonymized_text'  => 'Experienced PHP and Laravel developer.',
        'anonymized_summary' => 'Old summary.',
    ]);
});

test('reevaluate recalculates match score from current job listing skills', function () {
    // Attach PHP skill to resume so overlap becomes 100%
    $phpCandidateSkill = Skill::firstOrCreate(['name' => 'php']);
    $this->resume->skills()->syncWithoutDetaching([$phpCandidateSkill->id]);

    Http::fake([
        config('ollama.api_url') => Http::response(['response' => 'Refreshed summary.'], 200),
    ]);

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $this->candidate->refresh();
    expect($this->candidate->match_score)->toBe(100);
});

test('reevaluate regenerates summary from stored anonymized_text', function () {
    Http::fake([
        config('ollama.api_url') => Http::response(['response' => 'Updated professional summary.'], 200),
    ]);

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $this->candidate->refresh();
    expect($this->candidate->anonymized_summary)->toBe('Updated professional summary.');
});

test('reevaluate does not overwrite summary when Ollama is unreachable', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 7');
    });

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $this->candidate->refresh();
    // Original summary preserved when regeneration fails
    expect($this->candidate->anonymized_summary)->toBe('Old summary.');
});

test('reevaluate with no anonymized_text still updates score', function () {
    $this->candidate->update(['anonymized_text' => null]);

    $phpCandidateSkill = Skill::firstOrCreate(['name' => 'php']);
    $this->resume->skills()->syncWithoutDetaching([$phpCandidateSkill->id]);

    Http::fake(); // No HTTP should fire (no text to summarise)

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $this->candidate->refresh();
    expect($this->candidate->match_score)->toBe(100);

    Http::assertNothingSent();
});

test('reevaluate score reflects updated job skills', function () {
    // Initially candidate has PHP only; job now adds Vue
    $phpCandidateSkill = Skill::firstOrCreate(['name' => 'php']);
    $this->resume->skills()->syncWithoutDetaching([$phpCandidateSkill->id]);

    $vueListingSkill = JobListingSkill::firstOrCreate(['name' => 'vue']);
    $this->job->listingSkills()->syncWithoutDetaching([$vueListingSkill->id]);

    Http::fake([
        config('ollama.api_url') => Http::response(['response' => 'Good summary.'], 200),
    ]);

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $this->candidate->refresh();
    // php is 1 of 2 job skills = 50%
    expect($this->candidate->match_score)->toBe(50);
});

test('reevaluate redirects with success flash', function () {
    Http::fake([config('ollama.api_url') => Http::response(['response' => 'New summary.'], 200)]);

    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id));

    $response->assertRedirect(route('recruiter.jobs.show', $this->candidate->job_id));
    $response->assertSessionHas('success');
});

test('guest cannot re-evaluate', function () {
    $this->post(route('recruiter.candidate.reevaluate', $this->candidate->id))
        ->assertRedirect(route('login'));
});

test('hr cannot re-evaluate via recruiter route', function () {
    $hr = User::factory()->create(['role' => 'hr']);

    $this->actingAs($hr)
        ->post(route('recruiter.candidate.reevaluate', $this->candidate->id))
        ->assertStatus(403);
});
