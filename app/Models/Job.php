<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a job listing on the platform.
 *
 * The underlying table is `jobListings` (legacy name preserved to avoid a
 * data migration). Jobs have AI-extracted listing skills attached via a pivot,
 * and candidates are scored against those skills by the pipeline.
 *
 * @property bool $is_closed True after the recruiter marks the position filled.
 * @property \Illuminate\Support\Carbon|null $closed_at Timestamp when the position was closed.
 * @property array|null $requirements JSON array of plain-text requirement strings.
 */
class Job extends Model
{
    use HasFactory;

    protected $table = 'jobListings';

    protected $fillable = [
        'title',
        'company',
        'description',
        'location',
        'requirements',
        'company_id',
        'is_closed',
        'closed_at',
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * The company entity associated with this listing.
     */
    public function companyRelation(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Legacy applications relationship (retained for FK integrity).
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    /**
     * AI-extracted required skills for this listing, stored via pivot.
     *
     * Extracted lazily on first JobController::show() by JobSkillService.
     */
    public function listingSkills(): BelongsToMany
    {
        return $this->belongsToMany(JobListingSkill::class, 'job_job_listing_skill');
    }

    /**
     * All candidates submitted or uploaded for this listing.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'job_id');
    }
}
