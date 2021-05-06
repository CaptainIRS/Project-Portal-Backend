<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Feedback;
use Laravel\Passport\Passport;

class EditFeedbackTest extends TestCase
{
    private $project;
    private $existingFeedback, $newFeedbackPostData;
    private $users;

    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->users = User::factory()->count(3)->create();

        $this->sender = $this->users[0];
        $this->receiver = $this->users[1];

        $this->project->users()->syncWithoutDetaching([
            $this->sender->id =>
            ['role' => 'DEVELOPER'],
            $this->receiver->id =>
            ['role' => 'DEVELOPER']
        ]);

        $this->existingFeedback = Feedback::factory()->create([
            'project_id' => $this->project,
            'sender_id' => $this->sender,
            'receiver_id' => $this->receiver
        ]);

        $this->newFeedbackPostData = Feedback::factory()->make()->toArray();
    }

    /** @test */
    public function edit_feedback_route_is_guarded()
    {
        $this->post('api/projects/1/feedback/edit')
            ->assertStatus(401);
    }

    /** @test */
    public function feedback_can_be_edited()
    {
        Passport::actingAs($this->sender);

        $newContent = $this->faker->text;
        $this->newFeedbackPostData['feedback_id'] = $this->existingFeedback->id;
        $this->newFeedbackPostData['content'] = $newContent;

        $this->post(
            'api/projects/1/feedback/edit',
            $this->newFeedbackPostData
        )->assertStatus(200)
        ->assertJson([
            'message' => 'Feedback edited successfully!'
        ]);

        $this->assertCount(
            1,
            $this->project->feedbacks()->get()
        );

        $this->assertEquals(
            $newContent,
            $this->project->feedbacks()->first()->content
        );
    }

    /** @test */
    public function feedback_cannot_be_edited_with_invalid_fields()
    {
        Passport::actingAs($this->sender);
        $this->post(
            'api/projects/1/feedback/edit',
            []
        )->assertStatus(422);
    }

    /** @test */
    public function only_feedback_author_can_edit_feedback()
    {
        Passport::actingAs($this->receiver);

        $newContent = $this->faker->text;
        $this->newFeedbackPostData['feedback_id'] = $this->existingFeedback->id;
        $this->newFeedbackPostData['content'] = $newContent;

        $this->post(
            'api/projects/1/feedback/edit',
            $this->newFeedbackPostData
        )->assertStatus(403)
        ->assertJson([
            'message' => 'Only Creator of a Feedback can edit a Feedback'
        ]);
    }
}
