<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResponse extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'quiz_question_id',
        'response_value',
        'canvas_data',
        'is_marked_doubtful',
        'is_answered',
        'is_correct',
        'points_earned',
        'feedback',
    ];

    protected $casts = [
        'response_value' => 'array',
        'is_marked_doubtful' => 'boolean',
        'is_answered' => 'boolean',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}