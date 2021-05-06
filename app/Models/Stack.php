<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stack extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    protected $table = 'stacks';
    protected $hidden = ['pivot'];
    public $timestamps = false;

    public function projects()
    {
        return $this->belongsToMany('App\Models\Project')
                    ->whereNull('project_stack.deleted_at');
    }

}
