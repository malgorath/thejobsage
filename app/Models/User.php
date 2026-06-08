<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Platform user with one of three roles: admin, recruiter, or hr.
 *
 * Admins manage users, jobs, and have full access.
 * Recruiters upload resumes and access raw resume files.
 * HR users view only anonymized candidate profiles — never raw files.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Candidates uploaded by this recruiter.
     */
    public function uploadedCandidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'uploaded_by');
    }

    /**
     * Whether this user has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Whether this user has the recruiter role.
     */
    public function isRecruiter(): bool
    {
        return $this->role === 'recruiter';
    }

    /**
     * Whether this user has the hr role.
     */
    public function isHr(): bool
    {
        return $this->role === 'hr';
    }
}
