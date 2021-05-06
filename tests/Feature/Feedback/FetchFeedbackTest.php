<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\Feedback;
use Laravel\Passport\Passport;

class FetchFeedbackTest extends TestCase
{
    private $project;
    private $users, $sender, $receiver;

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

        $this->project->feedbacks()->saveMany(
            Feedback::factory()->count(2)->create([
                'project_id' => $this->project,
                'sender_id' => $this->sender,
                'receiver_id' => $this->receiver
            ])
        );
    }

    /** @test */
    public function fetch_feedback_route_is_guarded()
    {
        $this->get('api/projects/1/feedback/get')
            ->assertStatus(401);
    }

    /** @test */
    public function sender_can_fetch_their_feedback()
    {
        Passport::actingAs($this->sender);
        $this->get('api/projects/1/feedback/get')
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'feedbacks_sent',
                    'feedbacks_received'
                ]
            ])->assertJsonCount(
                2,
                'data.feedbacks_sent'
            );
    }

    /** @test */
    public function receiver_can_fetch_their_feedback()
    {
        Passport::actingAs($this->receiver);
        $this->get('api/projects/1/feedback/get')
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'feedbacks_sent',
                    'feedbacks_received'
                ]
            ])->assertJsonCount(
                2,
                'data.feedbacks_received'
            );
    }

    /** @test */
    public function user_cannot_see_others_feedbacks()
    {
        Passport::actingAs($this->users[2]);
        $this->get('api/projects/1/feedback/get')
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'feedbacks_sent',
                    'feedbacks_received'
                ]
            ])->assertJsonCount(
                0,
                'data.feedbacks_sent'
            )->assertJsonCount(
                0,
                'data.feedbacks_received'
            );
    }
}
