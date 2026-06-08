<?php

use App\Models\Prompt;
use App\Models\User;

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('admin can manage prompts', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    // Index
    $this->get(route('admin.prompts.index'))->assertOk();

    // Create
    $payload = [
        'key' => 'test_prompt',
        'title' => 'Test Prompt',
        'body' => 'Body with {{resume_text}}',
        'temperature' => 0.5,
        'max_tokens' => 256,
    ];

    $this->post(route('admin.prompts.store'), $payload)->assertRedirect(route('admin.prompts.index'));
    $this->assertDatabaseHas('prompts', ['key' => 'test_prompt', 'title' => 'Test Prompt']);

    $prompt = Prompt::where('key', 'test_prompt')->first();

    // Update
    $this->put(route('admin.prompts.update', $prompt), [
        'key' => 'test_prompt',
        'title' => 'Updated Prompt',
        'body' => 'Updated body',
    ])->assertRedirect(route('admin.prompts.index'));

    $this->assertDatabaseHas('prompts', ['key' => 'test_prompt', 'title' => 'Updated Prompt']);

    // Delete
    $this->delete(route('admin.prompts.destroy', $prompt))->assertRedirect(route('admin.prompts.index'));
    $this->assertDatabaseMissing('prompts', ['key' => 'test_prompt']);
});

test('non admin cannot access prompt admin', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('admin.prompts.index'))->assertForbidden();
});
