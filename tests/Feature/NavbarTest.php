<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    /**
     * Test that job listings link is visible to guests on home page
     */
    public function test_job_listings_link_visible_to_guests_on_home_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Job Listings', false);
        $response->assertSee(route('jobs.index'), false);
    }

    /**
     * Test that job listings link is visible to guests on jobs page
     */
    public function test_job_listings_link_visible_to_guests_on_jobs_page(): void
    {
        $response = $this->get('/jobs');

        $response->assertStatus(200);
        $response->assertSee('Job Listings', false);
        $response->assertSee(route('jobs.index'), false);
    }

    /**
     * Test that authenticated users see job listings link
     */
    public function test_job_listings_link_visible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Job Listings', false);
        $response->assertSee(route('jobs.index'), false);
    }

    /**
     * Test that authenticated users see the account settings link
     */
    public function test_authenticated_users_see_profile_link(): void
    {
        $user = User::factory()->create(['role' => 'recruiter']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Settings', false);
    }

    /**
     * Test that guests do not see authenticated-only nav items
     */
    public function test_guests_do_not_see_authenticated_nav_items(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Profile', false);
        $response->assertDontSee('My Resumes', false);
        $response->assertDontSee('Applications', false);
    }

    /**
     * Test that navbar includes job listings link in HTML structure
     */
    public function test_navbar_includes_job_listings_in_structure(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check that the link is in a nav-item structure
        $response->assertSee('<a class="nav-link', false);
        $response->assertSee('bi-briefcase', false); // Bootstrap icon class
    }
}
