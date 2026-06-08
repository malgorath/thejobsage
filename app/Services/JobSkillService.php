<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobListingSkill;
use Illuminate\Support\Str;

/**
 * Extracts required skills from a job description via Ollama and attaches
 * them to the job's listing-skills pivot table.
 *
 * Skills are extracted lazily — the method is a no-op when skills already
 * exist, so it is safe to call on every page view.
 */
class JobSkillService
{
    public function __construct(private OllamaService $ollamaService) {}

    /**
     * Extract skills from the job description and attach them to the job.
     *
     * Skips silently when:
     *  - the job already has listing skills attached
     *  - the job description is empty
     *  - Ollama returns an error or is unreachable
     *
     * @param  Job  $job  The job whose listing skills should be populated.
     */
    public function extractAndAttach(Job $job): void
    {
        if ($job->listingSkills()->exists()) {
            return;
        }

        $description = $job->description ?? '';
        if (trim($description) === '') {
            return;
        }

        [$prompt, $config] = $this->ollamaService->promptPayload(
            'job_skill_extraction',
            ['job_description' => $description],
            "Extract concise skill keywords from the following job description. Return a comma separated list of skill names only (no sentences, no extra text). Keep them lowercase and trim whitespace:\n\n{{job_description}}"
        );

        try {
            $response = $this->ollamaService->postToOllama($prompt, $config);
        } catch (\Throwable) {
            return;
        }

        if (! $response->successful()) {
            return;
        }

        $raw = $response->json()['response'] ?? '';
        $skills = array_filter(array_map(
            fn ($s) => Str::of($s)->lower()->trim()->value(),
            explode(',', $raw)
        ));

        if (empty($skills)) {
            return;
        }

        $skillIds = [];
        foreach ($skills as $name) {
            $skill = JobListingSkill::firstOrCreate(['name' => $name]);
            $skillIds[] = $skill->id;
        }

        if ($skillIds) {
            $job->listingSkills()->syncWithoutDetaching($skillIds);
        }
    }
}
