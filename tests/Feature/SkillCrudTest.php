<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class SkillCrudTest extends TestCase
{
    public function test_admin_can_view_skills_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Create skills with unique names to avoid constraint violations
        for ($i = 0; $i < 5; $i++) {
            Skill::factory()->create(['name' => 'Skill'.microtime(true).$i]);
        }

        $response = $this->actingAs($admin)->get(route('admin.skills.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_skill(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $skillName = 'PHP'.microtime(true); // Unique skill name

        $response = $this->actingAs($admin)->post(route('admin.skills.store'), [
            'name' => $skillName,
        ]);

        $response->assertRedirect(route('admin.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('skills', [
            'name' => $skillName,
        ]);
    }

    public function test_admin_can_update_skill(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $skill = Skill::factory()->create(['name' => 'PHP'.microtime(true)]);
        $newName = 'JavaScript'.microtime(true); // Unique name

        $response = $this->actingAs($admin)->put(route('admin.skills.update', $skill), [
            'name' => $newName,
        ]);

        $response->assertRedirect(route('admin.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'name' => $newName,
        ]);
    }

    public function test_admin_can_delete_skill(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $skill = Skill::factory()->create(['name' => 'DeleteSkill'.microtime(true)]);

        $response = $this->actingAs($admin)->delete(route('admin.skills.destroy', $skill));

        $response->assertRedirect(route('admin.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_non_admin_cannot_access_skills(): void
    {
        $user = User::factory()->create(['role' => 'job_seeker']);

        $response = $this->actingAs($user)->get(route('admin.skills.index'));

        $response->assertStatus(403);
    }
}
