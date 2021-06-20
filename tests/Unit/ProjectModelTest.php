<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;

class ProjectModelTest extends TestCase
{
    /**
     * Test all relations of a Project
     *
     */

    private $project;
    private $users;

    public function setUp(): void
    {

        parent::setUp();

        $this->project = Project::factory()->create();
        $this->users = User::factory()->count(4)->create();
    }

    /** @test */
    public function project_can_have_users()
    {

        $this->project->users()->syncWithoutDetaching([
            $this->users[0]->id =>
            ['role' => 'MAINTAINER'],
            $this->users[1]->id =>
            ['role' => 'DEVELOPER']
        ]);
        $firstUserRole = $this->project->users()->first()->pivot->role;
        $this->assertEquals($firstUserRole, 'MAINTAINER');
    }

    /** @test */
    public function roles_can_be_updated_for_project_members()
    {

        $this->project->users()->syncWithoutDetaching([
            $this->users[1]->id =>
            ['role' => 'MAINTAINER']
        ]);
        $response = $this->project->users()->syncWithoutDetaching([
            $this->users[1]->id =>
            ['role' => 'DEVELOPER']
        ]);
        $this->assertCount(1, $response['updated']);
        $this->assertEquals($this->project->users()->first()->pivot->role, 'DEVELOPER');
    }


    /** @test */
    public function no_duplicate_user_role_pair_per_project()
    {

        $this->project->users()->syncWithoutDetaching([
            $this->users[0]->id =>
            ['role' => 'AUTHOR'],
            $this->users[1]->id =>
            ['role' => 'MAINTAINER'],
            $this->users[1]->id =>
            ['role' => 'MAINTAINER'],
            $this->users[1]->id =>
            ['role' => 'DEVELOPER']
        ]);
        $this->assertCount(2, $this->project->users()->get());
    }
}
