<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'description',
        'order_number',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class)->orderBy('order_number');
    }
   
    public function quizzes()
    {
        return $this->hasMany(\App\Models\Quiz::class);
    }
}