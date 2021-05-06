<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AddStackTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->newStackData = [
            'name' => 'Test Stack'
        ];
    }

    /** @test */
    public function add_stack_route_is_guarded()
    {
        $this->post('api/stacks/add')
            ->assertStatus(401);
    }

    /** @test */
    public function stacks_can_be_added()
    {
        Passport::actingAs($this->user);
        $this->post(
            'api/stacks/add',
            $this->newStackData
        )->assertStatus(200)
            ->assertJson([
                'message' => 'Stack added successfully!',
            ]);
    }

    /** @test */
    public function stack_cannot_be_added_with_invalid_fields()
    {
        Passport::actingAs($this->user);
        $this->post(
            'api/stacks/add',
            []
        )->assertStatus(422);
    }
}
