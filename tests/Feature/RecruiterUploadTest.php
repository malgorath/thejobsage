<?php

use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->recruiter = User::factory()->create(['role' => 'recruiter']);
    $this->job       = Job::factory()->create();

    // Extracted text returned by mocked extractor
    $this->rawText = 'Senior PHP developer with Laravel and MySQL experience.';

    // Pipeline result returned by mocked pipeline
    $this->pipelineResult = [
        'anonymized_text'    => 'Backend developer with strong PHP skills.',
        'skills'             => ['PHP', 'Laravel', 'MySQL'],
        'match_score'        => 80,
        'anonymized_summary' => 'A strong backend engineer.',
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

// ─── Upload with preview (default) ───────────────────────────────────────────

test('upload with review_before_saving stores result in session and redirects to preview', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), [
            'resume'               => $file,
            'review_before_saving' => '1',
        ]);

    $response->assertRedirect(route('recruiter.upload.preview', $this->job));

    expect(session('upload_preview'))->not->toBeNull();
    expect(session('upload_preview.job_id'))->toBe($this->job->id);
    expect(session('upload_preview.result.skills'))->toContain('PHP');
});

test('upload with review_before_saving does not create candidate record yet', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), [
            'resume'               => $file,
            'review_before_saving' => '1',
        ]);

    expect(Candidate::count())->toBe(0);
    expect(Resume::count())->toBe(0);
});

// ─── Upload without preview ───────────────────────────────────────────────────

test('upload without review_before_saving saves candidate immediately', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), [
            'resume'               => $file,
            'review_before_saving' => '0',
        ]);

    $response->assertRedirect(route('recruiter.jobs.show', $this->job));
    expect(Candidate::count())->toBe(1);
    expect(Resume::count())->toBe(1);
});

test('upload without review stores no file_data in the database', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), [
            'resume'               => $file,
            'review_before_saving' => '0',
        ]);

    $fileData = DB::table('resumes')->value('file_data');
    expect($fileData)->toBeNull();
});

// ─── Preview page ─────────────────────────────────────────────────────────────

test('preview page renders staged data', function () {
    session([
        'upload_preview' => [
            'job_id'          => $this->job->id,
            'filename'        => 'test-resume.pdf',
            'mime_type'       => 'application/pdf',
            'candidate_email' => 'test@example.com',
            'result'          => $this->pipelineResult,
        ],
    ]);

    $response = $this->actingAs($this->recruiter)
        ->get(route('recruiter.upload.preview', $this->job));

    $response->assertOk();
    $response->assertSee('Review Extracted Data');
    $response->assertSee('80%');
    $response->assertSee('PHP');
    $response->assertSee('A strong backend engineer.');
    $response->assertSee('test-resume.pdf');
});

test('preview page redirects when session is empty', function () {
    $response = $this->actingAs($this->recruiter)
        ->get(route('recruiter.upload.preview', $this->job));

    $response->assertRedirect(route('recruiter.upload.form', $this->job));
});

test('preview page redirects when staged job does not match', function () {
    $otherJob = Job::factory()->create();
    session([
        'upload_preview' => ['job_id' => $otherJob->id, 'result' => $this->pipelineResult],
    ]);

    $response = $this->actingAs($this->recruiter)
        ->get(route('recruiter.upload.preview', $this->job));

    $response->assertRedirect(route('recruiter.upload.form', $this->job));
});

// ─── Confirm ──────────────────────────────────────────────────────────────────

test('confirm creates candidate and resume from session and clears session', function () {
    session([
        'upload_preview' => [
            'job_id'          => $this->job->id,
            'filename'        => 'confirmed-resume.pdf',
            'mime_type'       => 'application/pdf',
            'candidate_email' => 'candidate@example.com',
            'result'          => $this->pipelineResult,
        ],
    ]);

    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload.confirm', $this->job));

    $response->assertRedirect(route('recruiter.jobs.show', $this->job));

    expect(Candidate::count())->toBe(1);
    expect(Resume::count())->toBe(1);
    expect(session('upload_preview'))->toBeNull();

    $this->assertDatabaseHas('resumes', ['filename' => 'confirmed-resume.pdf']);
    $this->assertDatabaseHas('candidates', [
        'job_id'          => $this->job->id,
        'candidate_email' => 'candidate@example.com',
        'status'          => 'analyzed',
    ]);
});

test('confirm stores no file_data in the database', function () {
    session([
        'upload_preview' => [
            'job_id'          => $this->job->id,
            'filename'        => 'test.pdf',
            'mime_type'       => 'application/pdf',
            'candidate_email' => null,
            'result'          => $this->pipelineResult,
        ],
    ]);

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload.confirm', $this->job));

    $fileData = DB::table('resumes')->value('file_data');
    expect($fileData)->toBeNull();
});

test('confirm redirects to upload form when session is missing', function () {
    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload.confirm', $this->job));

    $response->assertRedirect(route('recruiter.upload.form', $this->job));
    expect(Candidate::count())->toBe(0);
});

// ─── Discard ──────────────────────────────────────────────────────────────────

test('discard clears session and saves nothing', function () {
    session([
        'upload_preview' => [
            'job_id'  => $this->job->id,
            'result'  => $this->pipelineResult,
        ],
    ]);

    $response = $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload.discard', $this->job));

    $response->assertRedirect(route('recruiter.upload.form', $this->job));
    expect(Candidate::count())->toBe(0);
    expect(session('upload_preview'))->toBeNull();
});

// ─── Access control ───────────────────────────────────────────────────────────

test('guest cannot upload', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->post(route('recruiter.upload', $this->job), ['resume' => $file])
        ->assertRedirect(route('login'));
});

test('hr role cannot upload', function () {
    $hr   = User::factory()->create(['role' => 'hr']);
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->actingAs($hr)
        ->post(route('recruiter.upload', $this->job), ['resume' => $file])
        ->assertStatus(403);
});

test('upload requires a file', function () {
    $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), [])
        ->assertSessionHasErrors(['resume']);
});

test('upload rejects non-pdf non-docx file', function () {
    $file = UploadedFile::fake()->create('resume.txt', 10, 'text/plain');

    $this->actingAs($this->recruiter)
        ->post(route('recruiter.upload', $this->job), ['resume' => $file])
        ->assertSessionHasErrors(['resume']);
});
