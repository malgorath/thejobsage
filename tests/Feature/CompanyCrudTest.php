<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class CompanyCrudTest extends TestCase
{
    public function test_admin_can_view_companies_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Company::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get(route('admin.companies.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'Tech Corp',
            'website' => 'https://techcorp.com',
            'description' => 'A technology company',
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'Tech Corp',
        ]);
    }

    public function test_admin_can_update_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.companies.update', $company), [
            'name' => 'Updated Company',
            'website' => 'https://updated.com',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
        ]);
    }

    public function test_admin_can_delete_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.companies.destroy', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_non_admin_cannot_access_companies(): void
    {
        $user = User::factory()->create(['role' => 'job_seeker']);

        $response = $this->actingAs($user)->get(route('admin.companies.index'));

        $response->assertStatus(403);
    }
}
