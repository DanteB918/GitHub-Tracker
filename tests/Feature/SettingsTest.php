<?php

namespace Tests\Feature;


use App\Models\GithubApi;
use Mockery;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/')
            ->assertOk();
    }
    public function test_able_to_create_and_delete_githubapi_model()
    {
        $model = GithubApi::factory()->create();

        $this->assertDatabaseHas('github_api', [
            'token' => $model->token,
        ]);

        $model->delete();

        $this->assertDatabaseMissing('github_api', [
            'token' => $model->token,
        ]);
    }
}
