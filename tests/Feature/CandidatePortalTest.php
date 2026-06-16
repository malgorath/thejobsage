<?php

use App\Mail\CandidateConfirmationMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();

    // Extracted text returned by the mocked extractor.
    $this->rawText = 'Senior PHP developer with Laravel and MySQL experience.';

    // Pipeline result returned by the mocked pipeline (mirrors recruiter flow).
    $this->pipelineResult = [
        'anonymized_text'    => 'Backend developer with strong PHP skills.',
        'skills'             => ['PHP', 'Laravel', 'MySQL'],
        'match_score'        => 75,
        'anonymized_summary' => 'Experienced developer with strong PHP skills.',
    ];

    $this->mock(ResumeTextExtractor::class)
        ->shouldReceive('extractContent')
        ->andReturn($this->rawText);

    $this->mock(CandidatePipelineService::class, function ($mock) {
        $mock->shouldReceive('runPipeline')
            ->andReturn($this->pipelineResult);

        $mock->shouldReceive('persistResult')
            ->andReturnUsing(function (Candidate $candidate, array $result) {
                $candidate->update([
                    'anonymized_text'    => $result['anonymized_text'],
                    'anonymized_summary' => $result['anonymized_summary'],
                    'match_score'        => $result['match_score'],
                    'status'             => 'analyzed',
                ]);
            });
    });
});

test('apply form renders for an open job', function () {
    $job = Job::factory()->create(['is_closed' => false]);

    $response = $this->get(route('portal.apply', $job->id));

    $response->assertStatus(200);
    $response->assertSee($job->title);
    $response->assertSee('Apply for');
});

test('apply form redirects when job is closed', function () {
    $job = Job::factory()->create(['is_closed' => true]);

    $response = $this->get(route('portal.apply', $job->id));

    $response->assertRedirect(route('jobs.index'));
});

// ─── Submission processing + staging for review ───────────────────────────────

test('portal submit processes the resume and redirects to review without creating a candidate', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response->assertRedirect(route('portal.review', $job->id));

    expect(Candidate::count())->toBe(0);
    expect(Resume::count())->toBe(0);
    expect(session('portal_submission_review.job_id'))->toBe($job->id);
    expect(session('portal_submission_review.result.skills'))->toContain('PHP');
});

test('portal submit blocked when job is closed', function () {
    $job = Job::factory()->create(['is_closed' => true]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response->assertRedirect(route('jobs.index'));
    expect(Candidate::count())->toBe(0);
});

test('portal submit validates email and file', function () {
    $job = Job::factory()->create(['is_closed' => false]);

    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'not-an-email',
        'resume' => 'not-a-file',
    ]);

    $response->assertSessionHasErrors(['candidate_email', 'resume']);
});

test('portal submit rejects duplicate email for same job before processing again', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    // First submission, fully accepted.
    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);
    $this->post(route('portal.review.confirm', $job->id));

    // Second submission attempt with the same email.
    $file2 = UploadedFile::fake()->create('resume2.pdf', 100, 'application/pdf');
    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file2,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('info');
    expect(Candidate::where('job_id', $job->id)->count())->toBe(1);
});

// ─── Review screen ──────────────────────────────────────────────────────────

test('review screen shows anonymized profile, no PII', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response = $this->get(route('portal.review', $job->id));

    $response->assertStatus(200);
    $response->assertSee('permanently removed');
    $response->assertSee('PHP');
    $response->assertSee('75%');
    $response->assertSee('Not yet saved');
});

test('review screen redirects to apply form when no staged submission exists', function () {
    $job = Job::factory()->create(['is_closed' => false]);

    $response = $this->get(route('portal.review', $job->id));

    $response->assertRedirect(route('portal.apply', $job->id));
});

// ─── Accepting the reviewed profile ────────────────────────────────────────

test('candidate accepting the processed profile saves the record', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response = $this->post(route('portal.review.confirm', $job->id));

    $response->assertRedirect(route('portal.submitted'));

    $this->assertDatabaseHas('candidates', [
        'job_id' => $job->id,
        'candidate_email' => 'applicant@example.com',
        'status' => 'analyzed',
    ]);

    $candidate = Candidate::where('job_id', $job->id)->first();
    expect($candidate->submission_token)->not->toBeNull();
    expect($candidate->uploaded_by)->toBeNull();
    expect($candidate->match_score)->toBe(75);

    // Session is cleared after acceptance.
    expect(session('portal_submission_review'))->toBeNull();
});

test('accepting the processed profile sends confirmation mail', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    // No mail until the candidate explicitly accepts the reviewed profile.
    Mail::assertNothingQueued();

    $this->post(route('portal.review.confirm', $job->id));

    Mail::assertQueued(CandidateConfirmationMail::class, function ($mail) {
        return $mail->hasTo('applicant@example.com');
    });
});

test('confirming submission without a staged review redirects to apply form', function () {
    $job = Job::factory()->create(['is_closed' => false]);

    $response = $this->post(route('portal.review.confirm', $job->id));

    $response->assertRedirect(route('portal.apply', $job->id));
    expect(Candidate::count())->toBe(0);
});

// ─── Rejecting the reviewed profile ────────────────────────────────────────

test('candidate rejecting the processed profile discards all data with nothing persisted', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response = $this->post(route('portal.review.reject', $job->id));

    $response->assertRedirect(route('jobs.show', $job->id));
    $response->assertSessionHas('info');

    expect(Candidate::count())->toBe(0);
    expect(Resume::count())->toBe(0);
    expect(session('portal_submission_review'))->toBeNull();
    Mail::assertNothingQueued();
});

test('after rejecting, the review screen no longer shows the discarded submission', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);
    $this->post(route('portal.review.reject', $job->id));

    $response = $this->get(route('portal.review', $job->id));

    $response->assertRedirect(route('portal.apply', $job->id));
});

// ─── Status / submitted pages (unchanged) ──────────────────────────────────

test('status page shows anonymized profile via token', function () {
    $job = Job::factory()->create();
    $candidate = Candidate::factory()->create([
        'job_id' => $job->id,
        'uploaded_by' => null,
        'candidate_email' => 'test@example.com',
        'submission_token' => Str::uuid()->toString(),
        'status' => 'analyzed',
        'anonymized_summary' => 'Skilled developer.',
        'match_score' => 80,
    ]);

    $response = $this->get(route('portal.status', $candidate->submission_token));

    $response->assertStatus(200);
    $response->assertSee('Skilled developer.');
    $response->assertSee('80%');
});

test('status page returns 404 for unknown token', function () {
    $response = $this->get(route('portal.status', 'nonexistent-token-xyz'));

    $response->assertStatus(404);
});

test('submitted page renders', function () {
    $response = $this->get(route('portal.submitted'));

    $response->assertStatus(200);
    $response->assertSee('Application Received');
});
