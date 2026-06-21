<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'practice_key',
        'title',
        'type',
        'answers',
        'feedback',
        'score',
        'max_score',
        'is_completed',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'feedback' => 'array',
        'is_completed' => 'boolean',
        'submitted_at' => 'datetime',
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