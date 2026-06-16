<?php

use App\Mail\CandidateConfirmationMail;
use App\Mail\CandidateRejectedMail;
use App\Mail\PositionFilledMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
});

test('confirmation mail is queued after candidate accepts the reviewed profile', function () {
    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extractContent')
        ->andReturn('Senior PHP developer with Laravel experience.');

    $this->mock(CandidatePipelineService::class, function ($mock) {
        $mock->shouldReceive('runPipeline')->andReturn([
            'anonymized_text'    => 'Backend developer with strong PHP skills.',
            'skills'             => ['PHP', 'Laravel'],
            'match_score'        => 60,
            'anonymized_summary' => 'Experienced developer.',
        ]);
        $mock->shouldReceive('persistResult')->andReturnUsing(function (Candidate $candidate, array $result) {
            $candidate->update(['status' => 'analyzed', 'match_score' => $result['match_score']]);
        });
    });

    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'portal@example.com',
        'resume' => $file,
    ]);

    // No mail until the candidate explicitly accepts the reviewed profile.
    Mail::assertNothingQueued();

    $this->post(route('portal.review.confirm', $job->id));

    Mail::assertQueued(CandidateConfirmationMail::class, 1);
    Mail::assertQueued(CandidateConfirmationMail::class, function ($mail) {
        return $mail->hasTo('portal@example.com');
    });
});

test('rejection mail is queued when HR rejects candidate with email', function () {
    Http::fake([config('ollama.api_url') => Http::response(['response' => 'Stripped note.'], 200)]);

    $hr = User::factory()->create(['role' => 'hr']);
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null]);
    $candidate = Candidate::factory()->create([
        'job_id' => $job->id,
        'resume_id' => $resume->id,
        'uploaded_by' => null,
        'candidate_email' => 'candidate@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'analyzed',
    ]);

    $this->actingAs($hr)->post(route('hr.candidate.reject', $candidate->id), [
        'rejection_stage' => 'screening',
        'rejection_reason' => 'skill_gap',
        'rejection_note' => 'The candidate lacks the required backend experience for this role.',
    ]);

    Mail::assertQueued(CandidateRejectedMail::class, function ($mail) {
        return $mail->hasTo('candidate@example.com');
    });
});

test('rejection mail is not sent when candidate has no email', function () {
    Http::fake([config('ollama.api_url') => Http::response(['response' => 'Stripped note.'], 200)]);

    $hr = User::factory()->create(['role' => 'hr']);
    $job = Job::factory()->create();
    $resume = Resume::factory()->create(['user_id' => null]);
    $candidate = Candidate::factory()->create([
        'job_id' => $job->id,
        'resume_id' => $resume->id,
        'uploaded_by' => null,
        'candidate_email' => null,
        'status' => 'analyzed',
    ]);

    $this->actingAs($hr)->post(route('hr.candidate.reject', $candidate->id), [
        'rejection_stage' => 'screening',
        'rejection_reason' => 'skill_gap',
        'rejection_note' => 'The candidate lacks the required backend experience for this role.',
    ]);

    Mail::assertNotQueued(CandidateRejectedMail::class);
});

test('position filled mail queued for active candidates with emails', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $job = Job::factory()->create(['is_closed' => false]);

    // Two portal candidates with emails (active)
    $resume1 = Resume::factory()->create(['user_id' => null]);
    $resume2 = Resume::factory()->create(['user_id' => null]);
    Candidate::factory()->create([
        'job_id' => $job->id,
        'resume_id' => $resume1->id,
        'uploaded_by' => null,
        'candidate_email' => 'active1@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'analyzed',
    ]);
    Candidate::factory()->create([
        'job_id' => $job->id,
        'resume_id' => $resume2->id,
        'uploaded_by' => null,
        'candidate_email' => 'active2@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'shortlisted',
    ]);

    // One already rejected — should not get mail
    $resume3 = Resume::factory()->create(['user_id' => null]);
    Candidate::factory()->create([
        'job_id' => $job->id,
        'resume_id' => $resume3->id,
        'uploaded_by' => null,
        'candidate_email' => 'rejected@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'rejected',
    ]);

    // One recruiter-uploaded (no email) — should not get mail
    Candidate::factory()->create([
        'job_id' => $job->id,
        'uploaded_by' => $recruiter->id,
        'candidate_email' => null,
        'status' => 'analyzed',
    ]);

    $this->actingAs($recruiter)
        ->patch(route('recruiter.jobs.close', $job->id));

    Mail::assertQueued(PositionFilledMail::class, 2);
    Mail::assertQueued(PositionFilledMail::class, fn ($m) => $m->hasTo('active1@example.com'));
    Mail::assertQueued(PositionFilledMail::class, fn ($m) => $m->hasTo('active2@example.com'));
    Mail::assertNotQueued(PositionFilledMail::class, fn ($m) => $m->hasTo('rejected@example.com'));
});

test('position filled mail not sent when job already closed', function () {
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $job = Job::factory()->create(['is_closed' => true, 'closed_at' => now()]);

    $response = $this->actingAs($recruiter)
        ->patch(route('recruiter.jobs.close', $job->id));

    $response->assertRedirect();
    $response->assertSessionHas('info');
    Mail::assertNothingQueued();
});
