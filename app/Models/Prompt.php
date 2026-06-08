<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An editable Ollama prompt template stored in the database.
 *
 * Prompts are keyed (e.g. `pii_strip`, `candidate_summary`, `skill_gap_summary`)
 * and resolved by OllamaService::promptPayload() at runtime. The `config` JSON
 * column holds per-prompt model parameters that override the service defaults
 * (temperature, top_p, top_k, num_ctx, etc.).
 *
 * Prompt bodies use `{{variable_name}}` placeholders rendered by
 * OllamaService::renderTemplate().
 */
class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'body',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
