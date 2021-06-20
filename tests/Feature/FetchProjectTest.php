<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use Laravel\Passport\Passport;

class FetchProjectTest extends TestCase
{

    private $project;
    private $users, $developer, $maintainer, $author;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->users = User::factory()->count(3)->create();

        $this->developer = $this->users[0];
        $this->maintainer = $this->users[1];
        $this->author = $this->users[2];

        $this->project->users()->syncWithoutDetaching([
            $this->developer->id =>
            ['role' => 'DEVELOPER'],
            $this->maintainer->id =>
            ['role' => 'MAINTAINER'],
            $this->author->id =>
            ['role' => 'AUTHOR']
        ]);
    }

    /** @test */
    public function fetch_project_routes_are_guarded()
    {
        $this->get('api/projects/all')
            ->assertStatus(401);
        $this->get('api/projects/1')
            ->assertStatus(401);
    }

    /** @test */
    public function all_projects_can_be_fetched()
    {
        Passport::actingAs($this->developer);
        $this->get('api/projects/all')
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'projects' => [
                        '*' =>  [
                            'stacks',
                            'status',
                            'type'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function select_project_can_be_fetched()
    {
        Passport::actingAs($this->developer);
        $this->get('api/projects/1')
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project' => [
                        '*' =>  [
                            'stacks',
                            'status',
                            'users',
                            'type'
                        ]
                    ]
                ]
            ]);
    }
}
