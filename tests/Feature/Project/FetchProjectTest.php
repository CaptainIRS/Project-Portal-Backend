<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Feedback;
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
                            'feedbacks',
                            'stacks',
                            'status',
                            'users',
                            'type'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function feedbacks_of_logged_in_user_are_fetched_with_project()
    {
        $this->project->feedbacks()->saveMany(
            Feedback::factory()->count(3)->create([
                'sender_id' => $this->developer,
                'receiver_id' => $this->maintainer,
                'project_id' => $this->project
            ])
        );
        $this->project->feedbacks()->saveMany(
            Feedback::factory()->count(2)->create([
                'sender_id' => $this->author,
                'receiver_id' => $this->maintainer,
                'project_id' => $this->project->id
            ])
        );

        Passport::actingAs($this->developer);
        $this->get('api/projects/1')
            ->assertStatus(200)
            ->assertJsonCount(
                3,
                'data.project.0.feedbacks.*'
            );
        Passport::actingAs($this->maintainer);
        $this->get('api/projects/1')
            ->assertStatus(200)
            ->assertJsonCount(
                5,
                'data.project.0.feedbacks.*'
            );
        Passport::actingAs($this->author);
        $this->get('api/projects/1')
            ->assertStatus(200)
            ->assertJsonCount(
                2,
                'data.project.0.feedbacks.*'
            );
    }
}
