<?php

use App\Mail\CandidateRejectedMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\JobListingSkill;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
    $this->hr = User::factory()->create(['role' => 'hr']);
    $this->job = Job::factory()->create();
    // Attach listing skills so generateSkillGapSummary receives non-empty job skills
    $skills = JobListingSkill::factory()->count(3)->create();
    $this->job->listingSkills()->attach($skills->pluck('id'));
    $this->resume = Resume::factory()->create(['user_id' => null]);
    $this->candidate = Candidate::factory()->create([
        'job_id' => $this->job->id,
        'resume_id' => $this->resume->id,
        'uploaded_by' => null,
        'candidate_email' => 'test@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'analyzed',
    ]);
});

test('reject form renders for an analyzed candidate', function () {
    $response = $this->actingAs($this->hr)
        ->get(route('hr.candidate.reject.form', $this->candidate->id));

    $response->assertStatus(200);
    $response->assertSee('Reject Candidate');
    $response->assertSee('rejection_stage');
    $response->assertSee('rejection_reason');
    $response->assertSee('rejection_note');
});

test('reject form redirects if candidate already rejected', function () {
    $this->candidate->update(['status' => 'rejected']);

    $response = $this->actingAs($this->hr)
        ->get(route('hr.candidate.reject.form', $this->candidate->id));

    $response->assertRedirect(route('hr.jobs.show', $this->candidate->job_id));
});

test('reject requires all three fields', function () {
    $response = $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), []);

    $response->assertSessionHasErrors(['rejection_stage', 'rejection_reason', 'rejection_note']);
});

test('reject note must be at least 20 characters', function () {
    $response = $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), [
            'rejection_stage' => 'screening',
            'rejection_reason' => 'skill_gap',
            'rejection_note' => 'Too short.',
        ]);

    $response->assertSessionHasErrors(['rejection_note']);
});

test('reject stage must be screening or interview', function () {
    $response = $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), [
            'rejection_stage' => 'invalid_stage',
            'rejection_reason' => 'skill_gap',
            'rejection_note' => 'The candidate does not meet the technical requirements for this position.',
        ]);

    $response->assertSessionHasErrors(['rejection_stage']);
});

test('successful rejection sets all fields and sends mail', function () {
    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'The candidate lacks backend skills required for this role.'], 200)
            ->push(['response' => 'Candidate is missing Python and AWS experience.'], 200),
    ]);

    $response = $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), [
            'rejection_stage' => 'screening',
            'rejection_reason' => 'skill_gap',
            'rejection_note' => 'John Smith does not have enough Python experience for this backend role.',
        ]);

    $response->assertRedirect(route('hr.jobs.show', $this->candidate->job_id));

    $this->candidate->refresh();
    expect($this->candidate->status)->toBe('rejected');
    expect($this->candidate->rejection_stage)->toBe('screening');
    expect($this->candidate->rejection_reason)->toBe('skill_gap');
    expect($this->candidate->rejection_note)->toBe('The candidate lacks backend skills required for this role.');
    expect($this->candidate->skill_gap_summary)->toBe('Candidate is missing Python and AWS experience.');

    Mail::assertQueued(CandidateRejectedMail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});

test('rejection note is pii-stripped before storing', function () {
    Http::fake([
        config('ollama.api_url') => Http::sequence()
            ->push(['response' => 'The candidate lacks experience in required areas.'], 200)
            ->push(['response' => 'Missing key technical skills.'], 200),
    ]);

    $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), [
            'rejection_stage' => 'interview',
            'rejection_reason' => 'experience_level',
            'rejection_note' => 'Jane Doe from ACME Corp did not demonstrate sufficient leadership.',
        ]);

    $this->candidate->refresh();
    // The stored note should be the Ollama-stripped version, not the original
    expect($this->candidate->rejection_note)->toBe('The candidate lacks experience in required areas.');
    expect($this->candidate->rejection_note)->not->toContain('Jane Doe');
});

test('rejection without candidate email does not queue mail', function () {
    Http::fake([config('ollama.api_url') => Http::response(['response' => 'Stripped.'], 200)]);

    $this->candidate->update(['candidate_email' => null]);

    $this->actingAs($this->hr)
        ->post(route('hr.candidate.reject', $this->candidate->id), [
            'rejection_stage' => 'screening',
            'rejection_reason' => 'other',
            'rejection_note' => 'The candidate did not meet the requirements for this position.',
        ]);

    Mail::assertNotQueued(CandidateRejectedMail::class);
});

test('hr updateStatus only accepts shortlisted', function () {
    $response = $this->actingAs($this->hr)
        ->patch(route('hr.candidate.status', $this->candidate->id), [
            'status' => 'rejected',
        ]);

    $response->assertSessionHasErrors(['status']);
    $this->candidate->refresh();
    expect($this->candidate->status)->toBe('analyzed');
});

test('hr can shortlist via updateStatus', function () {
    $response = $this->actingAs($this->hr)
        ->patch(route('hr.candidate.status', $this->candidate->id), [
            'status' => 'shortlisted',
        ]);

    $response->assertRedirect();
    $this->candidate->refresh();
    expect($this->candidate->status)->toBe('shortlisted');
});

test('guest cannot access rejection form', function () {
    $response = $this->get(route('hr.candidate.reject.form', $this->candidate->id));

    $response->assertRedirect(route('login'));
});

test('recruiter cannot access hr rejection form', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);

    $response = $this->actingAs($recruiter)
        ->get(route('hr.candidate.reject.form', $this->candidate->id));

    $response->assertStatus(403);
});
