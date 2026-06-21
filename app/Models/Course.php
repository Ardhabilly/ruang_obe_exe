<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_number');
    }
}