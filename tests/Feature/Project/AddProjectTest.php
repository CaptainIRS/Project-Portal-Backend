<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use Laravel\Passport\Passport;

class AddProjectsTest extends TestCase
{
    private $existingProject;
    private $newProjectPostData;
    private $users;

    public function setUp(): void
    {
        parent::setUp();

        $this->existingProject = Project::factory()->create();

        $this->newProjectPostData = Project::factory()->make()->toArray();
        $this->newProjectPostData['stacks'] = [1, 2];
        $this->newProjectPostData['status'] = 1;
        $this->newProjectPostData['type'] = 1;
        $this->newProjectPostData['deadline'] = null;

        $this->users = User::factory()->count(3)->create();
    }

    /** @test */
    public function add_project_route_is_guarded()
    {
        $this->post('api/projects/add')
            ->assertStatus(401);
    }

    /** @test */
    public function projects_can_be_added()
    {
        Passport::actingAs($this->users[0]);
        $this->post(
            'api/projects/add',
            $this->newProjectPostData
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project created successfully!',
            ]);
    }

    /** @test */
    public function projects_cannot_be_added_with_invalid_fields()
    {
        Passport::actingAs($this->users[0]);
        $this->post(
            'api/projects/add',
            []
        )->assertStatus(422);
    }

    /** @test */
    public function user_who_added_the_project_becomes_author()
    {
        Passport::actingAs($this->users[0]);
        $this->post(
            'api/projects/add',
            $this->newProjectPostData
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project created successfully!',
            ]);
        $this->assertEquals(
            $this->users[0]->projects()->first()->pivot->role,
            'AUTHOR'
        );
    }

    /** @test */
    public function users_can_be_added_with_projects()
    {
        $this->newProjectPostData['users'] = [
            [
                "id" => $this->users[1]->id,
                "role" => "MAINTAINER"
            ],
            [
                "id" => $this->users[2]->id,
                "role" => "DEVELOPER"
            ]
        ];
        Passport::actingAs($this->users[0]);
        $this->post(
            'api/projects/add',
            $this->newProjectPostData
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project created successfully!',
            ]);
        $this->assertContains(
            2,
            $this->users[1]->projects()->get()->pluck('id')
        );
    }

    /** @test */
    public function new_project_cannot_have_existing_repo_link()
    {
        $this->newProjectPostData['repo_link'] =
            $this->existingProject->repo_link;

        Passport::actingAs($this->users[0]);
        $this->post(
            'api/projects/add',
            $this->newProjectPostData
        )->assertStatus(422)
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'repo_link' => [
                        "The repo link has already been taken."
                    ]
                ]
            ]);
    }
}
