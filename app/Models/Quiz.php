<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'class_group_id',
        'course_module_id',
        'title',
        'slug',
        'type',
        'description',
        'instruction',
        'duration_minutes',
        'max_attempts',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',
        'is_active' => 'boolean',
    ];

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order_number');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}