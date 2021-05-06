<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'projects';
    protected $hidden = ['pivot', 'type_id', 'status_id'];
    protected $fillable = [
        'name', 'description', 'deadline', 'review', 'max_member_count', 'repo_link'
    ];
    public $timestamps = true;

    public function stacks()
    {
        return $this->belongsToMany('App\Models\Stack')
                    ->whereNull('project_stack.deleted_at');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\Status');
    }

    public function feedbacks()
    {
        return $this->hasMany('App\Models\Feedback');
    }

    public function type()
    {
        return $this->belongsTo('App\Models\Type');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User')
                    ->withPivot('role')
                    ->whereNull('project_user.deleted_at');
    }

}
