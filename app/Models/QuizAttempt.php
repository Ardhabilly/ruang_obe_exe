<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'class_group_id',
        'user_id',
        'attempt_number',
        'raw_score',
        'score',
        'max_score',
        'correct_answers',
        'total_questions',
        'duration_seconds',
        'is_passed',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'raw_score' => 'integer',
        'score' => 'integer',
        'max_score' => 'integer',
        'correct_answers' => 'integer',
        'total_questions' => 'integer',
        'duration_seconds' => 'integer',
        'is_passed' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responses()
    {
        return $this->hasMany(QuizResponse::class);
    }
}
