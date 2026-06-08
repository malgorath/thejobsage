<?php

namespace Tests\Feature\Auth;

use App\Models\User; // Make sure to import your User model
use Illuminate\Foundation\Testing\RefreshDatabase; // Resets DB for each test
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str; // For rate limiting test
use Tests\TestCase; // Needed for throttle key generation

class AuthenticationTest extends TestCase
{
    use RefreshDatabase; // Use this trait to automatically migrate and reset the database

    protected string $loginRoute;

    protected string $dashboardRoute; // Or wherever users are redirected after login

    protected function setUp(): void
    {
        parent::setUp();
        // Define your routes here - adjust if using custom routes
        // Ensure you have a named route 'login' in your routes/web.php or routes/auth.php
        $this->loginRoute = route('login');
        // Adjust '/dashboard' if your post-login redirect path is different
        // It might depend on the 'role' field in your users table eventually.
        $this->dashboardRoute = '/dashboard';
    }

    /** @test */
    public function login_screen_can_be_rendered(): void
    {
        $response = $this->get($this->loginRoute);

        $response->assertStatus(200);
        // Optional: Assert specific view content if needed
        $response->assertSee('Email');
        $response->assertSee('Password');
    }

    /**
     * @test
     * Covers: LOGIN-POS-01, LOGIN-POS-02 (Login via Email)
     */
    public function users_can_authenticate_using_the_login_screen_with_valid_credentials(): void
    {
        // Arrange: Create a user matching the schema
        // The factory will handle 'name' and default 'role' ('job_seeker')
        $user = User::factory()->create([
            'email' => 'test@example.com', // Use a consistent test email
            'password' => Hash::make('ValidPass123'), // Use a consistent test password
        ]);

        // Act: Post credentials to the login route
        $response = $this->post($this->loginRoute, [
            'email' => 'test@example.com',
            'password' => 'ValidPass123', // Use the plain text password here
        ]);

        // Assert: User is authenticated and redirected
        $this->assertAuthenticatedAs($user); // Check if the correct user is logged in
        $response->assertRedirect($this->dashboardRoute); // Check redirection path
    }

    /**
     * @test
     * Covers: LOGIN-NEG-01
     */
    public function users_can_not_authenticate_with_invalid_password(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass123'),
        ]);

        // Act: Post credentials with wrong password
        $response = $this->post($this->loginRoute, [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert: User is not authenticated, redirected back with errors
        $this->assertGuest(); // Ensure no user is logged in
        // Laravel's default behaviour redirects back on auth failure
        $response->assertRedirect(); // Check it redirects back
        $response->assertSessionHasErrors('email'); // Laravel typically puts auth errors under 'email' key for security
        // Ensure the specific password field doesn't have an error unless that's your specific design
        $response->assertSessionDoesntHaveErrors('password');
    }

    /**
     * @test
     * Covers: LOGIN-NEG-02
     */
    public function users_can_not_authenticate_with_non_existent_email(): void
    {
        // Arrange: No user created for this email

        // Act: Post credentials with non-existent email
        $response = $this->post($this->loginRoute, [
            'email' => 'nonexistent@example.com',
            'password' => 'ValidPass123',
        ]);

        // Assert: User is not authenticated, redirected back with errors
        $this->assertGuest();
        $response->assertRedirect();
        $response->assertSessionHasErrors('email'); // Generic error for security
    }

    /**
     * @test
     * Covers: LOGIN-NEG-04 (Validation)
     */
    public function validation_fails_with_empty_email(): void
    {
        // Act: Post with empty email
        $response = $this->post($this->loginRoute, [
            'email' => '',
            'password' => 'ValidPass123',
        ]);

        // Assert: Guest status, validation error for email
        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'The email field is required.']); // Check specific message
        $response->assertSessionDoesntHaveErrors('password'); // Password itself is valid format-wise
    }

    /**
     * @test
     * Covers: LOGIN-NEG-05 (Validation)
     */
    public function validation_fails_with_empty_password(): void
    {
        // Arrange: Need an email to test against, even if it doesn't exist,
        // otherwise the email validation might trigger first.
        // Alternatively, create a user first.
        User::factory()->create(['email' => 'test@example.com']);

        // Act: Post with empty password
        $response = $this->post($this->loginRoute, [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // Assert: Guest status, validation error for password
        $this->assertGuest();
        $response->assertSessionHasErrors(['password' => 'The password field is required.']);
        $response->assertSessionDoesntHaveErrors('email'); // Email itself is valid format-wise
    }

    /**
     * @test
     * Covers: LOGIN-SEC-04 (Rate Limiting / Throttling)
     * Note: Assumes standard Laravel throttle middleware ('throttle:login') is applied to the login route.
     */
    public function login_attempts_are_rate_limited(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create([
            'email' => 'throttle@example.com',
            'password' => Hash::make('ValidPass123'),
        ]);

        // Determine max attempts (default is 5) - check middleware definition (e.g., in routes/auth.php or Kernel.php)
        // Or check config('auth.throttle.max_attempts', 5) if using that.
        $maxAttempts = 5; // Adjust if your config or middleware definition is different

        // Generate the throttle key Laravel uses (email lowercase + IP)
        // Note: In tests, the IP is usually '127.0.0.1'
        $throttleKey = Str::transliterate(Str::lower($user->email).'|127.0.0.1');

        // Act: Simulate failed attempts up to the limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Ensure we clear any previous limiter state just in case for this loop iteration
            // RateLimiter::clear($throttleKey); // Usually not needed with RefreshDatabase, but can be safe

            $response = $this->post($this->loginRoute, [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email'); // Should fail normally
            $this->assertGuest();
        }

        // Assert: The next attempt should be throttled
        $response = $this->post($this->loginRoute, [
            'email' => $user->email,
            'password' => 'wrong-password', // Still wrong password
        ]);

        $response->assertStatus(302); // Check for Too Many Requests status
        $response->assertSessionHasErrors('email'); // Should still show an error message
        // Optional: Check the specific throttle error message if customized
        // $response->assertSee('Too many login attempts.');

        $this->assertGuest();

        // Optional: Check remaining attempts if needed (requires knowing decay seconds)
        // $this->assertTrue(RateLimiter::tooManyAttempts($throttleKey, $maxAttempts));
    }

    // --- Removed Tests ---
    // The following tests were removed as the current database schema (0001_01_01_000000_create_users_table.php)
    // does not include 'locked_at' or 'is_active' fields. Add these tests back if you implement
    // account locking or activation features and update the schema accordingly.

    // public function users_cannot_login_if_account_is_locked(): void { ... }
    // public function users_cannot_login_if_account_is_inactive(): void { ... }

}
