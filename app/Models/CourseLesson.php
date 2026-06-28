<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    protected $fillable = [
        'course_module_id',
        'title',
        'slug',
        'estimated_minutes',
        'order_number',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function progress()
    {
        return $this->hasMany(UserLessonProgress::class);
    }
}