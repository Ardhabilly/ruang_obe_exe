<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassMember extends Model
{
    protected $fillable = [
        'class_group_id',
        'user_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}