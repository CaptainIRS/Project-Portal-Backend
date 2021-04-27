<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        if (env('REGISTRATION_ENABLED') === false) {
            return response()->json([
                'message' => 'Registration has been disabled. Contact admin.'
            ], 400);
        }

        $data = $request->validated();

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->roll_number = $data['roll_number'];
        $user->github_handle = $data['github_handle'];

        \DB::transaction(function () use ($user) {
            $user->save();
        });

        if ($user->exists) {
            return response()->json([
                'message' => 'Registration Successful'
            ], 200);
        } else {
            return response()->json([
                'message' => 'User could not be created'
            ], 503);
        }
    }

    public function login(LoginRequest $request)
    {

        $loginData = $request->validated();

        if (!auth()->attempt($loginData)) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        return response(['message' => 'Login Successful', 'user' => auth()->user(), 'access_token' => $accessToken]);
    }

    public function forgot_password(ForgotPasswordRequest $request)
    {
        $request->validated();
        
        $user = User::where('roll_number', $request->roll_number)->first();
        if ($user && $user->email === $request->email) {
            $lastRetry = DB::table('password_resets')->where('email', $request->email)->first();
            if ($lastRetry) {
                $timestamp = $lastRetry->created_at;
                $difference = Carbon::now()->timestamp - strtotime($timestamp);
                if ($difference < 60 * 60) {
                    $timeRemaining = 60 - round($difference / 60);
                    return response()->json([
                        'message' => 
                            'No more than 1 reset attempt is allowed per hour. Try again after '
                            . $timeRemaining . ' minutes'
                    ], 429);
                }
            }
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['The entered data is incorrect.'],
                    'roll_number' => ['The entered data is incorrect.']
                ]
            ], 422);
        }
    
        $status = Password::sendResetLink(
            $request->only('email')
        );
    
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset email sent successfully. Please check your inbox.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Password reset request could not be processed. Try again later.'
            ], 503);
        }
    }

    public function reset_password(ResetPasswordRequest $request)
    {
        $request->validated();
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ]);
    
                $user->save();
    
                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Your password has been updated!'
            ], 200);
        } else if ($status == Password::INVALID_USER) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['The entered email is incorrect.'],
                ]
            ], 422);
        } else if ($status == Password::INVALID_TOKEN) {
            return response()->json([
                'message' => 'Token is invalid.'
            ], 503);
        } else {
            return response()->json([
                'message' => 'Password reset request could not be processed. Try again later.'
            ], 503);
        }
    }
}
