<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function user_can_request_reset_with_correct_email()
    {
        $user = User::factory()->create();
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset email sent successfully. Please check your inbox.'
            ]);

        // Verify email is sent to user
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /** @test */
    public function fails_if_roll_number_and_email_mismatch()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $anotherUser->email
        ])->assertStatus(422);

        // Verify nothing is sent to anyone
        Notification::assertNothingSent();
    }

    /** @test */
    public function only_vague_info_is_given_on_wrong_data()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $expectedError = [
            'message' => 'The given data was invalid.',
            'errors' => [
                'email' => [
                    'The entered data is incorrect.'
                ],
                'roll_number' => [
                    'The entered data is incorrect.'
                ]
            ]
        ];

        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $anotherUser->email
            /** NOTE */
        ])->assertStatus(422)
            ->assertExactJson($expectedError);

        $this->post('api/auth/forgot_password', [
            'roll_number' => $anotherUser->roll_number,
            /** NOTE */
            'email' => $user->email
        ])->assertStatus(422)
            ->assertExactJson($expectedError);
    }

    /** @test */
    public function email_contains_only_frontend_url_in_link()
    {
        $frontendUrl = env('FRONTEND_URL');

        $user = User::factory()->create();
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertOk();

        Notification::assertSentTo(
            $user,
            function (ResetPasswordNotification $notification, $channels) use ($user, $frontendUrl) {
                $mail = $notification->toMail($user);
                return Str::contains($mail->actionUrl, $frontendUrl);
            }
        );
    }

    /** @test */
    public function email_contains_correct_reset_link()
    {
        $user = User::factory()->create();
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertOk();

        $hashedToken = DB::table('password_resets')->first()->token;

        Notification::assertSentTo(
            $user,
            function (ResetPasswordNotification $notification, $channels) use ($user, $hashedToken) {
                $mail = $notification->toMail($user);
                $mailedToken = explode('token=', $mail->actionUrl)[1];
                return Hash::check($mailedToken, $hashedToken);
            }
        );
    }

    /** @test */
    public function requests_should_die_if_user_is_already_logged_in()
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertStatus(302);
    }

    /** @test */
    public function user_can_perform_reset_with_correct_email()
    {
        $oldPassword = 'old_password';
        $newPassword = 'new_password';
        $user = User::factory()->create([
            'password' => bcrypt($oldPassword)
        ]);
        $token = Password::broker()->createToken($user);

        $this->from('api/auth/forgot_password', [
            'token' => $token
        ])->post('api/auth/reset_password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ])->assertStatus(200);

        $user->refresh();

        // Verify that database records have changed
        $this->assertFalse(Hash::check($oldPassword, $user->password));
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    /** @test */
    public function password_updation_fails_with_email_of_other_users()
    {
        $oldPassword = 'old_password';
        $newPassword = 'new_password';
        $user = User::factory()->create([
            'password' => bcrypt($oldPassword)
        ]);
        $token = Password::broker()->createToken($user);
        $anotherUser = User::factory()->create();

        $this->from('api/auth/forgot_password', [
            'token' => $token
        ])->post('api/auth/reset_password', [
            'token' => $token,
            'email' => $anotherUser->email,
            /** NOTE */
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ])->assertStatus(503)
            ->assertJson([
                'message' => 'Token is invalid.'
            ]);

        $user->refresh();

        // Verify nothing has changed
        $this->assertTrue(Hash::check($oldPassword, $user->password));
    }

    /** @test */
    public function password_updation_fails_with_wrong_email()
    {
        $oldPassword = 'old_password';
        $newPassword = 'new_password';
        $user = User::factory()->create([
            'password' => bcrypt($oldPassword)
        ]);
        $token = Password::broker()->createToken($user);

        $this->from('api/auth/forgot_password', [
            'token' => $token
        ])->post('api/auth/reset_password', [
            'token' => $token,
            'email' => 'hello@hello.com',
            /** NOTE */
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ])->assertStatus(422)
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['The entered email is incorrect.']
                ]
            ]);

        $user->refresh();

        // Verify nothing has changed
        $this->assertTrue(Hash::check($oldPassword, $user->password));
    }

    /** @test */
    public function only_one_reset_attempt_is_allowed_per_hour()
    {
        $user = User::factory()->create();

        Carbon::setTestNow(now()->parse('Jan 1, 2021 01:00 am'));
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertOk();

        // Time travel +59 minutes
        Carbon::setTestNow(now()->parse('Jan 1, 2021 01:59 am'));
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertStatus(429)
            ->assertJson([
                'message' =>
                'We have sent a reset email to you recently. Please check your inbox.'
            ]);

        // Time travel +61 minutes
        Carbon::setTestNow(now()->parse('Jan 1, 2021 02:01 am'));
        $this->post('api/auth/forgot_password', [
            'roll_number' => $user->roll_number,
            'email' => $user->email
        ])->assertOk();
    }
}
