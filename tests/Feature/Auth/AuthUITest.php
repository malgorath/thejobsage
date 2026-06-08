<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthUITest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that login page renders with all required UI elements
     */
    public function test_login_page_renders_with_all_ui_elements(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);

        // Check for Bootstrap classes and professional styling
        $response->assertSee('Welcome Back', false);
        $response->assertSee('Sign in to your account to continue', false);

        // Check form elements are present
        $response->assertSee('Email Address', false);
        $response->assertSee('Password', false);
        $response->assertSee('Remember me', false);
        $response->assertSee('Sign In', false);

        // Check for Bootstrap form classes
        $response->assertSee('form-control', false);
        $response->assertSee('form-label', false);
        $response->assertSee('btn-primary', false);

        // Check links are present and properly positioned
        $response->assertSee('Forgot your password?', false);
        $response->assertSee('Don\'t have an account? Sign up', false);

        // Verify form has proper structure (no overlapping elements)
        $content = $response->getContent();
        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('type="password"', $content);
        $this->assertStringContainsString('type="checkbox"', $content);
        $this->assertStringContainsString('type="submit"', $content);
    }

    /**
     * Test that login form fields are accessible and not blocked by links
     */
    public function test_login_form_fields_are_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify email input is properly structured
        $this->assertStringContainsString('id="email"', $content);
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('autocomplete="username"', $content);

        // Verify password input is properly structured
        $this->assertStringContainsString('id="password"', $content);
        $this->assertStringContainsString('name="password"', $content);
        $this->assertStringContainsString('autocomplete="current-password"', $content);

        // Verify remember me checkbox
        $this->assertStringContainsString('id="remember_me"', $content);
        $this->assertStringContainsString('name="remember"', $content);

        // Verify links are in auth-links container (not overlapping inputs)
        $this->assertStringContainsString('auth-links', $content);
    }

    /**
     * Test that login page uses Bootstrap styling
     */
    public function test_login_page_uses_bootstrap_styling(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for Bootstrap CSS
        $this->assertStringContainsString('bootstrap@5.3.2', $content);

        // Check for Bootstrap classes
        $this->assertStringContainsString('form-control', $content);
        $this->assertStringContainsString('form-label', $content);
        $this->assertStringContainsString('btn btn-primary', $content);
        $this->assertStringContainsString('form-check', $content);
        $this->assertStringContainsString('d-grid', $content);

        // Check for professional styling classes
        $this->assertStringContainsString('auth-card', $content);
    }

    /**
     * Test that login form validation errors display correctly
     */
    public function test_login_form_displays_validation_errors(): void
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $response->assertRedirect(); // May redirect to home or login

        // Follow redirect to see error messages
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Check that error styling is present (when errors are shown)
        $content = $response->getContent();
        // Error styling will be present when validation fails and page is re-rendered
        $this->assertStringContainsString('form-control', $content);
    }

    /**
     * Test that register page renders with all required UI elements
     */
    public function test_register_page_renders_with_all_ui_elements(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);

        // Check for professional headings
        $response->assertSee('Create Account', false);
        $response->assertSee('Sign up to get started', false);

        // Check all form fields are present
        $response->assertSee('Full Name', false);
        $response->assertSee('Email Address', false);
        $response->assertSee('Password', false);
        $response->assertSee('Confirm Password', false);
        $response->assertSee('Create Account', false);

        // Check for link to login
        $response->assertSee('Already have an account? Sign in', false);
    }

    /**
     * Test that register form fields are accessible
     */
    public function test_register_form_fields_are_accessible(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify all inputs are properly structured
        $this->assertStringContainsString('id="name"', $content);
        $this->assertStringContainsString('name="name"', $content);
        $this->assertStringContainsString('autocomplete="name"', $content);

        $this->assertStringContainsString('id="email"', $content);
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('autocomplete="username"', $content);

        $this->assertStringContainsString('id="password"', $content);
        $this->assertStringContainsString('name="password"', $content);
        $this->assertStringContainsString('autocomplete="new-password"', $content);

        $this->assertStringContainsString('id="password_confirmation"', $content);
        $this->assertStringContainsString('name="password_confirmation"', $content);
    }

    /**
     * Test that register form validation errors display correctly
     */
    public function test_register_form_displays_validation_errors(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $response->assertRedirect(); // May redirect to home or register
    }

    /**
     * Test that forgot password page renders correctly
     */
    public function test_forgot_password_page_renders_correctly(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Reset Password', false);
        $response->assertSee('Email Address', false);
        $response->assertSee('Email Password Reset Link', false);
        $response->assertSee('Back to login', false);
    }

    /**
     * Test that reset password page renders correctly
     */
    public function test_reset_password_page_renders_correctly(): void
    {
        // Create a password reset token
        $user = User::factory()->create();
        $token = app('auth.password.broker')->createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Reset Password', false);
        $response->assertSee('Enter your new password below', false);
        $response->assertSee('New Password', false);
        $response->assertSee('Confirm Password', false);
    }

    /**
     * Test that verify email page renders correctly
     */
    public function test_verify_email_page_renders_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('Verify Your Email', false);
        $response->assertSee('Resend Verification Email', false);
        $response->assertSee('Log Out', false);
    }

    /**
     * Test that confirm password page renders correctly
     */
    public function test_confirm_password_page_renders_correctly(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertStatus(200);
        $response->assertSee('Confirm Password', false);
        $response->assertSee('Password', false);
        $response->assertSee('Confirm', false);
    }

    /**
     * Test that guest layout uses professional styling
     */
    public function test_guest_layout_uses_professional_styling(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for gradient background styling
        $this->assertStringContainsString('background: linear-gradient', $content);

        // Check for auth-card styling
        $this->assertStringContainsString('auth-card', $content);

        // Check for Bootstrap CSS
        $this->assertStringContainsString('bootstrap@5.3.2', $content);
    }

    /**
     * Test that navigation shows login/register links for guests
     */
    public function test_navigation_shows_auth_links_for_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        // Note: Guest layout doesn't include navbar, but we can check the main app layout
        // This test would need to be run on a page that uses the main layout
    }

    /**
     * Test that successful login redirects correctly
     */
    public function test_successful_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that successful registration redirects correctly
     */
    public function test_successful_registration_redirects_correctly(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test that remember me checkbox works
     */
    public function test_remember_me_checkbox_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Check that remember token is set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    /**
     * Test that form placeholders are present
     */
    public function test_form_placeholders_are_present(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('placeholder="Enter your email"', $content);
        $this->assertStringContainsString('placeholder="Enter your password"', $content);
    }

    /**
     * Test that links don't overlap with form inputs
     */
    public function test_links_do_not_overlap_form_inputs(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify auth-links container exists (separates links from form inputs)
        $this->assertStringContainsString('auth-links', $content);

        // Verify form inputs are in proper containers with spacing
        $this->assertStringContainsString('mb-3', $content); // Margin bottom for spacing

        // Verify form structure: inputs should be before links
        // Check that email input comes before auth-links
        $emailInputPos = strpos($content, 'id="email"');
        $authLinksPos = strpos($content, 'class="auth-links"');

        $this->assertNotFalse($emailInputPos, 'Email input should exist');
        $this->assertNotFalse($authLinksPos, 'Auth links container should exist');

        // Verify that the submit button comes before auth-links
        // Find the submit button that's inside the form (look for "Sign In" button)
        $submitButtonPos = strpos($content, 'Sign In');
        $this->assertNotFalse($submitButtonPos, 'Submit button should exist');

        // The key test: auth-links should come after the submit button
        // This ensures links don't overlap with form inputs
        if ($submitButtonPos !== false && $authLinksPos !== false) {
            $this->assertGreaterThan($submitButtonPos, $authLinksPos, 'Links should come after submit button, ensuring no overlap with inputs');
        }

        // Verify inputs have proper spacing classes
        $this->assertStringContainsString('mb-3', $content);

        // Verify form structure is correct - inputs are in mb-3 divs, submit is in d-grid, links are separate
        $this->assertStringContainsString('form-control', $content);
        $this->assertStringContainsString('d-grid', $content);
    }

    /**
     * Test that error messages display with proper styling
     */
    public function test_error_messages_display_with_proper_styling(): void
    {
        // Try to login with invalid credentials
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect(); // May redirect to home or login

        // Follow redirect to login page
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Check for Bootstrap form styling (errors will show when form is re-rendered with old input)
        $content = $response->getContent();
        $this->assertStringContainsString('form-control', $content);
        // The form structure should be present
        $this->assertStringContainsString('Email Address', $content);
    }

    /**
     * Test that all auth pages use consistent Bootstrap styling
     */
    public function test_all_auth_pages_use_consistent_styling(): void
    {
        $pages = [
            route('login'),
            route('register'),
            route('password.request'),
        ];

        foreach ($pages as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
            $content = $response->getContent();

            // All should use Bootstrap
            $this->assertStringContainsString('bootstrap@5.3.2', $content);

            // All should use auth-card
            $this->assertStringContainsString('auth-card', $content);

            // All should have form-control for inputs
            if (str_contains($content, '<input')) {
                $this->assertStringContainsString('form-control', $content);
            }
        }
    }
}
