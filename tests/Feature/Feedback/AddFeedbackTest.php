<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Feedback;
use Laravel\Passport\Passport;

class AddFeedbackTest extends TestCase
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

        $this->newFeedbackPostData = Feedback::factory()->make()->toArray();
    }

    /** @test */
    public function add_feedback_route_is_guarded()
    {
        $this->post('api/projects/1/feedback/add')
            ->assertStatus(401);
    }

    /** @test */
    public function feedback_can_be_added_to_project()
    {
        $sender = $this->users[0];
        $receiver = $this->users[1];

        $this->project->users()->syncWithoutDetaching([
            $sender->id =>
            ['role' => 'DEVELOPER'],
            $receiver->id =>
            ['role' => 'DEVELOPER']
        ]);

        Passport::actingAs($sender);

        $this->newFeedbackPostData['receiver_id'] = $receiver->id;

        $this->post(
            'api/projects/1/feedback/add',
            $this->newFeedbackPostData
        )->assertStatus(200)
        ->assertJson([
            'message' => 'Feedback added successfully!'
        ]);

        $this->assertCount(
            1,
            $this->project->feedbacks()->get()
        );
    }

    /** @test */
    public function only_project_members_can_add_feedback()
    {
        $sender = $this->users[0];
        $receiver = $this->users[1];

        Passport::actingAs($sender);

        $this->newFeedbackPostData['receiver_id'] = $receiver->id;

        $this->post(
            'api/projects/1/feedback/add',
            $this->newFeedbackPostData
        )->assertStatus(403)
        ->assertJson([
            'message' => 'Only project members can add feedback'
        ]);
    }

    /** @test */
    public function only_project_members_can_receive_feedback()
    {
        $sender = $this->users[0];
        $receiver = $this->users[1];

        $this->project->users()->syncWithoutDetaching([
            $sender->id =>
            ['role' => 'DEVELOPER'],
        ]);

        Passport::actingAs($sender);

        $this->newFeedbackPostData['receiver_id'] = $receiver->id;

        $this->post(
            'api/projects/1/feedback/add',
            $this->newFeedbackPostData
        )->assertStatus(403)
        ->assertJson([
            'message' => 'Only project members can receive feedback'
        ]);
    }

    /** @test */
    public function feedback_cannot_be_added_to_self()
    {
        $sender = $this->users[0];

        $this->project->users()->syncWithoutDetaching([
            $sender->id =>
            ['role' => 'DEVELOPER'],
        ]);

        Passport::actingAs($sender);

        $this->newFeedbackPostData['receiver_id'] = $sender->id;

        $this->post(
            'api/projects/1/feedback/add',
            $this->newFeedbackPostData
        )->assertStatus(403)
        ->assertJson([
            'message' => 'You can\'t add feedback to yourself'
        ]);
    }

}
