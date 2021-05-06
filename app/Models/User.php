<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory;

    use Notifiable, HasApiTokens;

    use SoftDeletes;

    protected $table = 'users';
    protected $hidden = ['pivot', 'email', 'password'];
    public $timestamps = true;

    public function projects()
    {
        return $this->belongsToMany('App\Models\Project')
                    ->withPivot('role')
                    ->whereNull('project_user.deleted_at');
    }

    public function sendPasswordResetNotification($token)
    {
        $url = env('FRONTEND_URL') . '/reset_password?token=' . $token;

        $this->notify(new ResetPasswordNotification($this->name, $url));
    }
}
