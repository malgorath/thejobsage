<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A named skill in the global skills dictionary.
 *
 * Skills are attached to resumes via the `resume_skill` pivot table and are
 * used by CandidatePipelineService to calculate match scores against job listings.
 * New skills are created automatically by the pipeline (firstOrCreate) when
 * Ollama extracts a skill name that doesn't yet exist in the dictionary.
 */
class Skill extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
