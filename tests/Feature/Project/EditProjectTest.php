<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use Laravel\Passport\Passport;

class EditProjectsTest extends TestCase
{
    private $project;
    private $users, $developer, $maintainer, $author;


    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->users = User::factory()->count(5)->create();

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
    public function edit_project_route_is_guarded()
    {
        $this->post('api/projects/1/edit')
            ->assertStatus(401);
    }

    /** @test */
    public function projects_can_be_edited()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 3;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project edited successfully!'
            ]);

        $this->project->refresh();
        $this->assertEquals(
            1,
            $this->project->type->id
        );

        $this->assertEquals(
            1,
            $this->project->status->id
        );
    }

    /** @test */
    public function project_cannot_be_edited_with_invalid_fields()
    {
        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            []
        )->assertStatus(422);
    }

    /** @test */
    public function authors_and_maintainers_can_edit_their_project()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 3;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project edited successfully!'
            ]);

        Passport::actingAs($this->author);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project edited successfully!'
            ]);
    }

    /** @test */
    public function developers_cannot_edit_their_project()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 3;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;

        Passport::actingAs($this->developer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(403)
            ->assertJson([
                'message' => 'You are not allowed to edit this project!'
            ]);
    }

    /** @test */
    public function users_can_be_added_to_project()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 5;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;
        $data['users'] = [
            [
                'id' => $this->developer->id,
                'role' => 'DEVELOPER'
            ],
            [
                'id' => $this->maintainer->id,
                'role' => 'MAINTAINER'
            ],
            [
                'id' => $this->users[3]->id,
                'role' => 'DEVELOPER'
            ],
            [
                'id' => $this->users[4]->id,
                'role' => 'MAINTAINER'
            ],
        ];

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project edited successfully!'
            ]);

        $this->assertCount(
            5,
            $this->project->users()->get()
        );
    }

    /** @test */
    public function users_roles_in_project_can_be_modified()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 3;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;
        $data['users'] = [
            [
                'id' => $this->maintainer->id,
                'role' => 'MAINTAINER'
            ],
            [
                'id' => $this->developer->id,
                'role' => 'MAINTAINER'
            ],
        ];

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Project edited successfully!'
            ]);

        $this->assertCount(
            2,
            $this->project->users()->wherePivot('role', 'MAINTAINER')->get()
        );
    }

    /** @test */
    public function author_cannot_have_any_other_role()
    {
        $data = $this->project->toArray();
        $data['max_member_count'] = 3;
        $data['stacks'] = [1, 2];
        $data['status'] = 1;
        $data['type'] = 1;
        $data['deadline'] = null;
        $data['users'] = [
            [
                'id' => $this->author->id,
                'role' => 'MAINTAINER'
            ],
        ];

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/edit',
            $data
        )->assertStatus(422)
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'users' => 'Author cannot take any other role'
                ]
            ]);
    }
}
