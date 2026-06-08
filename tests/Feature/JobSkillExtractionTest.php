<?php

use App\Models\Job;
use App\Models\JobListingSkill;
use App\Models\User;
use App\Services\JobSkillService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->admin);
});

test('extracts job skills on create', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([
            'response' => 'php, laravel, mysql',
        ], 200),
    ]);

    $job = Job::factory()->create([
        'description' => 'We need PHP, Laravel, MySQL experience',
    ]);

    app(JobSkillService::class)->extractAndAttach($job);

    $this->assertDatabaseHas('job_listing_skills', ['name' => 'php']);
    $this->assertDatabaseHas('job_listing_skills', ['name' => 'laravel']);
    $this->assertDatabaseHas('job_listing_skills', ['name' => 'mysql']);
    $this->assertCount(3, $job->fresh()->listingSkills);
});

test('extracts job skills on first view when missing', function () {
    Http::fake([
        config('ollama.api_url') => Http::response([
            'response' => 'react, node',
        ], 200),
    ]);

    $job = Job::factory()->create([
        'description' => 'React and Node developer',
    ]);

    // No skills yet
    $this->assertTrue($job->listingSkills()->count() === 0);

    $this->get(route('jobs.show', $job->id))->assertOk();

    $this->assertDatabaseHas('job_listing_skills', ['name' => 'react']);
    $this->assertDatabaseHas('job_listing_skills', ['name' => 'node']);
});

test('shows job skills on job detail', function () {
    $job = Job::factory()->create([
        'description' => 'JS dev',
    ]);

    $skill = JobListingSkill::factory()->create(['name' => 'javascript']);
    $job->listingSkills()->attach($skill->id);

    $this->get(route('jobs.show', $job->id))
        ->assertOk()
        ->assertSee('Skills')
        ->assertSee('javascript');
});
