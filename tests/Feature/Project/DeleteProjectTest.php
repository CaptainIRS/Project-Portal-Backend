<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Stack;
use App\Models\Status;
use App\Models\Type;
use Laravel\Passport\Passport;

class DeleteProjectsTest extends TestCase
{
    private $project;
    private $users, $developer, $maintainer, $author;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create([
            'status_id' => 1,
            'type_id' => 1
        ]);
        $this->project->stacks()->attach(
            Stack::find(1)->id
        );
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
    public function delete_project_route_is_guarded()
    {
        $this->post('api/projects/1/delete')
            ->assertStatus(401);
    }

    /** @test */
    public function only_authors_can_delete_the_project()
    {
        Passport::actingAs($this->author);
        $this->post('api/projects/1/delete')
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Project deleted successfully!'
            ]);
    }

    /** @test */
    public function maintainers_and_developers_cannot_delete_project()
    {
        Passport::actingAs($this->maintainer);
        $this->post('api/projects/1/delete')
            ->assertStatus(403);

        Passport::actingAs($this->developer);
        $this->post('api/projects/1/delete')
            ->assertStatus(403);
    }

    /** @test */
    public function project_can_be_deleted()
    {
        Passport::actingAs($this->author);
        $this->post('api/projects/1/delete')
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Project deleted successfully!'
            ]);

        $this->assertEmpty(
            $this->author->projects()->get()
        );
        $this->assertEmpty(
            Stack::find(1)->projects()->get()
        );
        $this->assertEmpty(
            Status::find(1)->projects()->get()
        );
        $this->assertEmpty(
            Type::find(1)->projects()->get()
        );
    }

    /** @test */
    public function project_is_only_soft_deleted()
    {
        Passport::actingAs($this->author);
        $this->post('api/projects/1/delete')
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Project deleted successfully!'
            ]);

        $this->assertSoftDeleted('projects', [
            'id' => $this->project->id
        ]);
    }
}
