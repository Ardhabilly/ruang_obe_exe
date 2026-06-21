<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'question_data',
        'answer_key',
        'accepted_answers',
        'explanation',
        'points',
        'order_number',
        'is_required',
    ];

    protected $casts = [
        'question_data' => 'array',
        'answer_key' => 'array',
        'accepted_answers' => 'array',
        'points' => 'integer',
        'order_number' => 'integer',
        'is_required' => 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function responses()
    {
        return $this->hasMany(QuizResponse::class);
    }
}