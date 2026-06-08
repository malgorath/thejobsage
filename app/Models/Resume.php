<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stores a single uploaded resume file and its extracted skills.
 *
 * `file_data` is a binary BLOB column hidden from serialization.
 * `user_id` is nullable — recruiter-uploaded and portal-submitted resumes
 * have no associated user account.
 * `uploaded_by` tracks which recruiter stored the file (null for portal uploads).
 */
class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uploaded_by',
        'filename',
        'mime_type',
        'file_data',
        'is_primary',
    ];

    protected $hidden = [
        'file_data',
    ];

    /**
     * The recruiter who uploaded this resume (null for portal self-submissions).
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Skills extracted from this resume's anonymized text via the resume_skill pivot.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /**
     * Candidates associated with this resume (typically one, but the model allows many).
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
