<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function classGroupsAsDosen()
    {
        return $this->hasMany(\App\Models\ClassGroup::class, 'dosen_id');
    }

    public function classMemberships()
    {
        return $this->hasMany(\App\Models\ClassMember::class);
    }

    public function joinedClassGroups()
    {
        return $this->belongsToMany(\App\Models\ClassGroup::class, 'class_members', 'user_id', 'class_group_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
    public function lessonProgress()
    {
        return $this->hasMany(\App\Models\UserLessonProgress::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(\App\Models\QuizAttempt::class);
    }
}
