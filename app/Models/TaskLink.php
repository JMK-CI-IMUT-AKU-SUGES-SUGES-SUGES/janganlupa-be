<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'task_id',
        'label',
        'url',
        'sort_order',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
