<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => 'recruiter',
        ]);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Total Users');
        $response->assertSee('Total Jobs');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_all_users(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee($this->adminUser->name);
        $response->assertSee($this->regularUser->name);
    }

    public function test_regular_user_cannot_view_admin_users_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_jobs(): void
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.jobs.index'));

        $response->assertStatus(200);
        $response->assertSee('Job Management');
        $response->assertSee($job->title);
    }

    public function test_admin_can_view_all_candidates(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.candidates.index'));

        $response->assertStatus(200);
        $response->assertSee('Candidate');
    }

    public function test_admin_can_create_job(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('jobs.create'));

        $response->assertStatus(200);
        $response->assertSee('Post New Job');
    }

    public function test_regular_user_cannot_create_job(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('jobs.create'));

        $response->assertStatus(403);
    }

    public function test_admin_can_store_job(): void
    {
        $jobData = [
            'title' => 'Test Job',
            'company' => 'Test Company',
            'description' => 'Test job description',
            'location' => 'Test Location',
            'requirements' => "Requirement 1\nRequirement 2",
        ];

        $response = $this->actingAs($this->adminUser)->post(route('jobs.store'), $jobData);

        $response->assertRedirect(route('jobs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('jobListings', [
            'title' => 'Test Job',
            'company' => 'Test Company',
        ]);
    }

    public function test_regular_user_cannot_store_job(): void
    {
        $jobData = [
            'title' => 'Test Job',
            'company' => 'Test Company',
            'description' => 'Test job description',
            'location' => 'Test Location',
        ];

        $response = $this->actingAs($this->regularUser)->post(route('jobs.store'), $jobData);

        $response->assertStatus(403);
    }

    public function test_admin_can_edit_job(): void
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->adminUser)->get(route('jobs.edit', $job->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Job Listing');
        $response->assertSee($job->title);
    }

    public function test_regular_user_cannot_edit_job(): void
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->regularUser)->get(route('jobs.edit', $job->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_job(): void
    {
        $job = Job::factory()->create([
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('jobs.update', $job->id), [
            'title' => 'Updated Title',
            'company' => $job->company,
            'description' => $job->description,
            'location' => $job->location,
            'requirements' => '',
        ]);

        $response->assertRedirect(route('jobs.show', $job->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('jobListings', [
            'id' => $job->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_delete_job(): void
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->adminUser)->delete(route('jobs.destroy', $job->id));

        $response->assertRedirect(route('jobs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('jobListings', ['id' => $job->id]);
    }

    public function test_regular_user_cannot_delete_job(): void
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->regularUser)->delete(route('jobs.destroy', $job->id));

        $response->assertStatus(403);
    }

    public function test_job_index_shows_post_button_only_for_admin(): void
    {
        // Admin should see the button
        $response = $this->actingAs($this->adminUser)->get(route('jobs.index'));
        $response->assertStatus(200);
        $response->assertSee('Post New Job');

        // Regular user should not see the button
        $response = $this->actingAs($this->regularUser)->get(route('jobs.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Post New Job');
    }

    public function test_job_show_shows_admin_actions_only_for_admin(): void
    {
        $job = Job::factory()->create();

        // Admin should see edit/delete buttons
        $response = $this->actingAs($this->adminUser)->get(route('jobs.show', $job->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Job');
        $response->assertSee('Delete Job');

        // Regular user should not see admin actions
        $response = $this->actingAs($this->regularUser)->get(route('jobs.show', $job->id));
        $response->assertStatus(200);
        $response->assertDontSee('Edit Job');
        $response->assertDontSee('Delete Job');
    }

    public function test_navbar_shows_admin_panel_link_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('jobs.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin Panel');
    }

    public function test_navbar_does_not_show_admin_panel_for_regular_user(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('jobs.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Admin Panel');
    }

    public function test_user_is_admin_helper_method(): void
    {
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->regularUser->isAdmin());
    }
}
