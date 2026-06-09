<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single candidate in the blind screening pipeline.
 *
 * A Candidate is created either by a recruiter upload or by a portal submission.
 * Portal candidates have a submission_token (UUID) for status tracking and an
 * uploaded_by of null. Recruiter-uploaded candidates have uploaded_by set.
 *
 * @property int|null $uploaded_by Null for portal self-submissions.
 * @property string|null $candidate_email Used for all three notification mailables.
 * @property string|null $submission_token UUID; null for recruiter-uploaded candidates.
 * @property string|null $anonymized_text PII-stripped resume text used for re-evaluation; never exposes raw data.
 * @property string|null $anonymized_summary LLM-generated; no PII.
 * @property int|null $match_score 0–100 skill overlap percentage, or null if no job skills exist.
 * @property string $status pending_analysis|analyzed|shortlisted|rejected
 * @property string|null $rejection_stage screening|interview
 * @property string|null $rejection_reason skill_gap|experience_level|culture_fit|overqualified|other
 * @property string|null $rejection_note PII-stripped HR free-text, emailed to candidate.
 * @property string|null $skill_gap_summary LLM-generated gap explanation, emailed to candidate.
 */
class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'resume_id',
        'uploaded_by',
        'candidate_email',
        'submission_token',
        'anonymized_text',
        'anonymized_summary',
        'match_score',
        'status',
        'rejection_stage',
        'rejection_reason',
        'rejection_note',
        'skill_gap_summary',
    ];

    /**
     * The job this candidate applied for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * The resume record containing the original file binary.
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * The recruiter who uploaded this candidate (null for portal submissions).
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Skills extracted from the anonymized resume text, proxied via the resume_skill pivot.
     *
     * Returns an empty collection when no resume is attached rather than throwing.
     */
    public function skills()
    {
        return $this->resume ? $this->resume->skills() : collect();
    }

    /**
     * Whether the candidate has been through the analysis pipeline.
     *
     * Returns false only while the pipeline hasn't run yet (status = pending_analysis).
     */
    public function isAnalyzed(): bool
    {
        return $this->status !== 'pending_analysis';
    }
}
