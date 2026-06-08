<?php

use App\Mail\CandidateConfirmationMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\CandidatePipelineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
    // Mock the pipeline so it doesn't call Ollama
    $this->mock(CandidatePipelineService::class, function ($mock) {
        $mock->shouldReceive('process')->andReturnUsing(function (Candidate $candidate) {
            $candidate->update([
                'status' => 'analyzed',
                'match_score' => 75,
                'anonymized_summary' => 'Experienced developer with strong PHP skills.',
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

test('portal submit creates candidate with email and token', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    $response->assertRedirect(route('portal.submitted'));

    $this->assertDatabaseHas('candidates', [
        'job_id' => $job->id,
        'candidate_email' => 'applicant@example.com',
        'status' => 'analyzed',
    ]);

    $candidate = Candidate::where('job_id', $job->id)->first();
    expect($candidate->submission_token)->not->toBeNull();
    expect($candidate->uploaded_by)->toBeNull();
});

test('portal submit sends confirmation mail', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    Mail::assertQueued(CandidateConfirmationMail::class, function ($mail) {
        return $mail->hasTo('applicant@example.com');
    });
});

test('portal submit rejects duplicate email for same job', function () {
    $job = Job::factory()->create(['is_closed' => false]);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    // First submission
    $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file,
    ]);

    // Duplicate
    $file2 = UploadedFile::fake()->create('resume2.pdf', 100, 'application/pdf');
    $response = $this->post(route('portal.submit', $job->id), [
        'candidate_email' => 'applicant@example.com',
        'resume' => $file2,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('info');

    expect(Candidate::where('job_id', $job->id)->count())->toBe(1);
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
