<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition()
    {
        return [
            'project_id' => function () {
                return User::factory()->make()->id;
            },
            'sender_id' => User::inRandomOrder()->value('id'),
            'receiver_id' => User::inRandomOrder()->value('id'),
            'content' => $this->faker->text
        ];
    }
}
