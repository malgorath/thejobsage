<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;

test('user has many uploaded candidates', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null, 'uploaded_by' => $recruiter->id]);
    Candidate::factory()->count(2)->create([
        'uploaded_by' => $recruiter->id,
        'job_id' => $job->id,
        'resume_id' => $resume->id,
    ]);

    expect($recruiter->uploadedCandidates)->toHaveCount(2);
    expect($recruiter->uploadedCandidates->first())->toBeInstanceOf(Candidate::class);
});

test('candidate belongs to job', function () {
    $job = Job::factory()->create();
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $resume = Resume::factory()->create(['user_id' => null, 'uploaded_by' => $recruiter->id]);
    $candidate = Candidate::factory()->create(['job_id' => $job->id, 'uploaded_by' => $recruiter->id, 'resume_id' => $resume->id]);

    expect($candidate->job)->toBeInstanceOf(Job::class);
    expect($candidate->job->id)->toBe($job->id);
});

test('candidate belongs to uploader', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null, 'uploaded_by' => $recruiter->id]);
    $candidate = Candidate::factory()->create(['uploaded_by' => $recruiter->id, 'job_id' => $job->id, 'resume_id' => $resume->id]);

    expect($candidate->uploader)->toBeInstanceOf(User::class);
    expect($candidate->uploader->id)->toBe($recruiter->id);
});

test('candidate belongs to resume', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null, 'uploaded_by' => $recruiter->id]);
    $candidate = Candidate::factory()->create(['resume_id' => $resume->id, 'uploaded_by' => $recruiter->id, 'job_id' => $job->id]);

    expect($candidate->resume)->toBeInstanceOf(Resume::class);
    expect($candidate->resume->id)->toBe($resume->id);
});

test('job has many candidates', function () {
    $job = Job::factory()->create();
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $resume = Resume::factory()->create(['user_id' => null, 'uploaded_by' => $recruiter->id]);
    Candidate::factory()->count(3)->create(['job_id' => $job->id, 'uploaded_by' => $recruiter->id, 'resume_id' => $resume->id]);

    expect($job->candidates)->toHaveCount(3);
});

test('job belongs to company', function () {
    $company = Company::factory()->create();
    $job = Job::factory()->create(['company_id' => $company->id]);

    expect($job->companyRelation)->toBeInstanceOf(Company::class);
    expect($job->companyRelation->id)->toBe($company->id);
});

test('candidate can be created without uploaded_by for portal submissions', function () {
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null]);
    $token = \Illuminate\Support\Str::uuid()->toString();

    $candidate = Candidate::create([
        'job_id' => $job->id,
        'resume_id' => $resume->id,
        'uploaded_by' => null,
        'candidate_email' => 'portal@example.com',
        'submission_token' => $token,
        'status' => 'pending_analysis',
    ]);

    expect($candidate->uploaded_by)->toBeNull();
    expect($candidate->candidate_email)->toBe('portal@example.com');
    expect($candidate->submission_token)->toBe($token);
    expect($candidate->uploader)->toBeNull();
});

test('candidate stores rejection fields', function () {
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null]);

    $candidate = Candidate::create([
        'job_id' => $job->id,
        'resume_id' => $resume->id,
        'uploaded_by' => null,
        'status' => 'rejected',
        'rejection_stage' => 'screening',
        'rejection_reason' => 'skill_gap',
        'rejection_note' => 'Missing required skills.',
        'skill_gap_summary' => 'The candidate lacks Python experience.',
    ]);

    expect($candidate->rejection_stage)->toBe('screening');
    expect($candidate->rejection_reason)->toBe('skill_gap');
    expect($candidate->rejection_note)->toBe('Missing required skills.');
    expect($candidate->skill_gap_summary)->toBe('The candidate lacks Python experience.');
});

test('job is_closed defaults to false and can be set', function () {
    $job = Job::factory()->create()->fresh();
    expect($job->is_closed)->toBeFalse();

    $job->update(['is_closed' => true, 'closed_at' => now()]);
    $job->refresh();

    expect($job->is_closed)->toBeTrue();
    expect($job->closed_at)->not->toBeNull();
});

test('resume has many skills via pivot', function () {
    $resume = Resume::factory()->create(['user_id' => null]);
    $skill1 = Skill::factory()->create();
    $skill2 = Skill::factory()->create();
    $resume->skills()->attach([$skill1->id, $skill2->id]);

    expect($resume->skills)->toHaveCount(2);
});
