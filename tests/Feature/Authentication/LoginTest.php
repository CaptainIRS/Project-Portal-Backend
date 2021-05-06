<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->userPassword = 'user_password';
        $this->user = User::factory()->create([
            'password' => bcrypt($this->userPassword)
        ]);
        $this->anotherUser = User::factory()->create();
    }

    /** @test */
    public function login_page_works_and_is_not_guarded()
    {
        $this->post('api/auth/login', [
            'roll_number' => $this->user->roll_number,
            'password' => $this->userPassword
        ])->assertOk();
    }

    /** @test */
    public function login_fails_with_invalid_data()
    {
        $this->post(
            'api/auth/login',
            []
        )->assertJsonValidationErrors([
            'roll_number',
            'password'
        ]);
    }

    /** @test */
    public function login_fails_with_wrong_credentials()
    {
        $this->post('api/auth/login', [
            'roll_number' => $this->user->roll_number,
            'password' => 'some_random_password' /** NOTE */
        ])->assertStatus(401);
    }
}
