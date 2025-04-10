<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

  protected $fillable = ['name', 'status'];


    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class);
    }

}
