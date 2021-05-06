<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Feedback;
use Laravel\Passport\Passport;

class AddReviewTest extends TestCase
{
    private $project;
    private $newFeedbackPostData;
    private $users;

    use WithFaker;

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

        $this->review = $this->faker->text;
        $this->reviewPostData = [
            'review' => $this->review
        ];
    }

    /** @test */
    public function add_review_route_is_guarded()
    {
        $this->post('api/projects/1/review')
            ->assertStatus(401);
    }

    /** @test */
    public function review_can_be_added()
    {
        Passport::actingAs($this->author);
        $this->post(
            'api/projects/1/review',
            $this->reviewPostData
        )->assertStatus(200)
        ->assertJson([
            'message' => 'Review added successfully!'
        ]);
    }

    /** @test */
    public function review_cannot_be_added_with_invalid_fields()
    {
        Passport::actingAs($this->author);
        $this->post(
            'api/projects/1/review',
            []
        )->assertStatus(422);
    }

    /** @test */
    public function review_can_be_added_by_authors_and_maintainers_only()
    {
        Passport::actingAs($this->author);
        $this->post(
            'api/projects/1/review',
            $this->reviewPostData
        )->assertStatus(200)
        ->assertJson([
            'message' => 'Review added successfully!'
        ]);

        $this->project->refresh();
        $this->assertEquals(
            $this->review,
            $this->project->review
        );

        Passport::actingAs($this->maintainer);
        $this->post(
            'api/projects/1/review',
            $this->reviewPostData
        )->assertStatus(200)
        ->assertJson([
            'message' => 'Review added successfully!'
        ]);

        Passport::actingAs($this->developer);
        $this->post(
            'api/projects/1/review',
            $this->reviewPostData
        )->assertStatus(403)
        ->assertJson([
            'message' => 'Only authors or maintainers are allowed to add reviews'
        ]);
    }
}
