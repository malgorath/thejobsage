<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobListingSkill;
use Illuminate\Support\Str;

class JobSkillService
{
    public function __construct(private OllamaService $ollamaService)
    {
    }

    /**
     * Extract and attach skills for a job if none exist.
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

