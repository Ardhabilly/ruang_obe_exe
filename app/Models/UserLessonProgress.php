<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLessonProgress extends Model
{
    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'completed',
        'started_at',
        'last_accessed_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}