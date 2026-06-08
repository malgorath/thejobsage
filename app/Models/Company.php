<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company entity that can be associated with job listings.
 *
 * The `company` string column on Job holds the display name; `company_id` is
 * an optional FK to this model for structured company management.
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
        'description',
    ];

    /**
     * Job listings posted by this company.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
