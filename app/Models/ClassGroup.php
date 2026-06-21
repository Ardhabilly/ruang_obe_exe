<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    protected $fillable = [
        'dosen_id',
        'name',
        'description',
        'token',
        'kkm',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'kkm' => 'integer',
    ];

    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function members()
    {
        return $this->hasMany(ClassMember::class);
    }

    public function mahasiswa()
    {
        return $this->belongsToMany(User::class, 'class_members', 'class_group_id', 'user_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function quizzes()
    {
        return $this->hasMany(\App\Models\Quiz::class);
    }
}