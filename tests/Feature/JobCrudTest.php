<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Tests\TestCase;

class JobCrudTest extends TestCase
{
    public function test_admin_can_access_job_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Company::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('jobs.create'));

        $response->assertStatus(200);
        $response->assertSee('Post New Job');
    }

    public function test_non_admin_cannot_access_job_create_page(): void
    {
        $user = User::factory()->create(['role' => 'job_seeker']);

        $response = $this->actingAs($user)->get(route('jobs.create'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_job_create_page(): void
    {
        $response = $this->get(route('jobs.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_create_job(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('jobs.store'), [
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
            'description' => 'We are looking for a software engineer',
            'location' => 'Remote',
            'requirements' => "PHP\nLaravel\nMySQL",
        ]);

        $response->assertRedirect(route('jobs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jobListings', [
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
        ]);
    }

    public function test_admin_can_edit_job(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $job = Job::factory()->create();
        Company::factory()->create();

        $response = $this->actingAs($admin)->get(route('jobs.edit', $job));

        $response->assertStatus(200);
        $response->assertSee($job->title);
    }

    public function test_admin_can_update_job(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $job = Job::factory()->create();

        $response = $this->actingAs($admin)->put(route('jobs.update', $job), [
            'title' => 'Updated Title',
            'company' => 'Updated Company',
            'description' => 'Updated description',
            'location' => 'Updated Location',
            'requirements' => 'New requirement',
        ]);

        $response->assertRedirect(route('jobs.show', $job));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jobListings', [
            'id' => $job->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_delete_job(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $job = Job::factory()->create();

        $response = $this->actingAs($admin)->delete(route('jobs.destroy', $job));

        $response->assertRedirect(route('jobs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('jobListings', ['id' => $job->id]);
    }

    public function test_non_admin_cannot_create_job(): void
    {
        $user = User::factory()->create(['role' => 'job_seeker']);

        $response = $this->actingAs($user)->post(route('jobs.store'), [
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
            'description' => 'We are looking for a software engineer',
            'location' => 'Remote',
        ]);

        $response->assertStatus(403);
    }

    public function test_anyone_can_view_jobs_list(): void
    {
        Job::factory()->count(5)->create();

        $response = $this->get(route('jobs.index'));

        $response->assertStatus(200);
    }

    public function test_anyone_can_view_job_details(): void
    {
        $job = Job::factory()->create();

        $response = $this->get(route('jobs.show', $job));

        $response->assertStatus(200);
        $response->assertSee($job->title);
    }
}
