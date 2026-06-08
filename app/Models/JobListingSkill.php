<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An AI-extracted skill requirement for a job listing.
 *
 * Distinct from the global Skill model — these are job-side keywords extracted
 * from job descriptions by JobSkillService and stored in `job_listing_skills`.
 * The pivot table `job_job_listing_skill` links them to Job models.
 * Used by CandidatePipelineService to compute skill-overlap match scores.
 */
class JobListingSkill extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Jobs that require this skill.
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_job_listing_skill');
    }
}
