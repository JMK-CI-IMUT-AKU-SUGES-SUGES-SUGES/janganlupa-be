<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'deadline_date',
        'created_by_user_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->withPivot('role', 'joined_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function links()
    {
        return $this->hasMany(ProjectLink::class);
    }

    public function messages()
    {
        return $this->hasMany(ProjectMessage::class);
    }
}
