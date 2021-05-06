<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class RegisterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function register_works_and_is_unguarded()
    {
        $this->post('api/auth/register', [
            'name' => 'Test',
            'roll_number' => 100000000,
            'email' => 'test@test.com',
            'github_handle' => 'Test',
            'password' => 'test',
            'password_confirmation' => 'test'
        ])->assertOk();
        assertEquals(User::all()->first()->name, 'Test');
    }

    /** @test */
    public function register_fails_with_invalid_data()
    {
        $this->post(
            'api/auth/register',
            []
        )->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'roll_number',
            'github_handle'
        ]);
    }
}
