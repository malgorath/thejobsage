<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legacy job-seeker application record.
 *
 * Retained for database FK integrity. The public-facing application flow was
 * replaced by the Candidate model and the blind screening pipeline. No routes
 * or controllers create new Application records.
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'status',
        'resume_id',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * The user who submitted this application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The job this application is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * The resume submitted with this application.
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
